<?php

namespace App\Services;

use App\Models\CylinderTypeModel;
use App\Models\CustomerModel;
use App\Models\GasRateModel;
use Config\Database;
use RuntimeException;

class SalesService
{
    protected $db;
    protected $types;
    protected $customers;
    protected $rates;
    protected $cash;
    protected $cylinders;
    protected array $inventoryLocks = [];

    public function __construct()
    {
        $this->db=Database::connect();
        $this->types=new CylinderTypeModel();
        $this->customers=new CustomerModel();
        $this->rates=new GasRateModel();
        $this->cash=new CashService();
        $this->cylinders=new CylinderUnitService();
    }

    public function post(array $payload,int $userId,int $locationId): array
    {
        $customerId=!empty($payload['customer_id'])?(int)$payload['customer_id']:null;
        $lines=is_array($payload['lines']??null)?$payload['lines']:[];
        $payments=is_array($payload['payments']??null)?$payload['payments']:[];
        if(!$lines) throw new RuntimeException('At least one sale line is required.');
        if(!$payments) throw new RuntimeException('At least one payment is required.');

        $transactionAt=str_replace('T',' ',trim((string)($payload['transaction_at']??date('Y-m-d H:i:s'))));
        $notes=trim((string)($payload['notes']??''))?:null;
        $customer=$customerId?$this->customers->find($customerId):null;
        if($customerId && !$customer) throw new RuntimeException('Customer not found.');
        if($customerId && !(int)$customer['is_active']) throw new RuntimeException('Customer is inactive.');

        $prepared=[];$subtotal=0;$totalKg=0;$customRate=false;$inventory=[];
        foreach($lines as $i=>$line){
            $type=(string)($line['line_type']??'');
            if(!$customerId && in_array($type,['cylinder_exchange','empty_intake'],true)) throw new RuntimeException('Walk-in sales cannot include cylinder returns.');
            $qty=(float)($line['quantity']??0);
            $typeId=isset($line['cylinder_type_id'])&&$line['cylinder_type_id']!==''?(int)$line['cylinder_type_id']:null;
            $rate=(float)($line['rate']??0);
            if($qty<=0) throw new RuntimeException('Line '.($i+1).' quantity must be greater than zero.');
            $gasKg=0;$standardRate=null;$emptyReceived=(float)($line['empty_cylinder_received']??0);

            if(in_array($type,['filled_cylinder','cylinder_exchange'],true)){
                if(!$typeId) throw new RuntimeException('Cylinder type is required for line '.($i+1).'.');
                $ct=$this->types->find($typeId);
                if(!$ct || !(int)$ct['is_active']) throw new RuntimeException('Invalid or inactive cylinder type.');
                if(floor($qty)!==$qty) throw new RuntimeException('Cylinder quantity must be a whole number.');
                $standardRate=$this->rates->currentCylinderRate($typeId,$transactionAt);
                if($standardRate===null) throw new RuntimeException('No effective package rate exists for '.$ct['name'].'.');
                if($rate<=0) $rate=$standardRate;
                $emptyReceived=(float)($line['empty_cylinder_received']??0);
                if($emptyReceived<0 || floor($emptyReceived)!==$emptyReceived) throw new RuntimeException('Empty cylinder quantity must be a whole number.');
                if($type==='cylinder_exchange' && $emptyReceived<=0) $emptyReceived=$qty;
                if($type==='filled_cylinder' && $emptyReceived>0) throw new RuntimeException('Empty cylinder intake must use cylinder exchange.');
                $available=$this->cylinders->available($locationId,$typeId,'filled');
                if(count($available)<(int)$qty) throw new RuntimeException('Insufficient filled cylinders of '.$ct['name'].'.');
                $selected=array_slice($available,0,(int)$qty);
                $gasKg=0;
                foreach($selected as $unit){
                    $gas=(float)$unit['gas_weight_kg']; $gasKg+=$gas;
                    $inventory[]=['line_no'=>$i+1,'type'=>'gas_kg','cylinder_type_id'=>null,'quantity'=>$gas,'direction'=>'out','unit_id'=>(int)$unit['id']];
                    $inventory[]=['line_no'=>$i+1,'type'=>'filled_cylinder','cylinder_type_id'=>$typeId,'quantity'=>1,'direction'=>'out','unit_id'=>(int)$unit['id']];
                }
                if($emptyReceived>0){
                    for($n=0;$n<(int)$emptyReceived;$n++) $inventory[]=['line_no'=>$i+1,'type'=>'empty_cylinder','cylinder_type_id'=>$typeId,'quantity'=>1,'direction'=>'in','unit_id'=>null];
                }
            }elseif($type==='refill_kg'){
                $standardRate=$this->rates->currentKgRate($transactionAt);
                if($standardRate===null) throw new RuntimeException('No effective gas/kg rate exists.');
                $gasKg=(float)($line['gas_weight_kg']??$qty);
                if($gasKg<=0) throw new RuntimeException('Refill KG must be greater than zero.');
                $rate=$rate>0?$rate:$standardRate;
                $inventory[]=['line_no'=>$i+1,'type'=>'gas_kg','cylinder_type_id'=>null,'quantity'=>$gasKg,'direction'=>'out'];
            }elseif($type==='empty_intake' || $type==='empty_sale'){
                if(!$typeId) throw new RuntimeException('Cylinder type is required for line '.($i+1).'.');
                $ct=$this->types->find($typeId);
                if(!$ct || !(int)$ct['is_active']) throw new RuntimeException('Invalid or inactive cylinder type.');
                if(floor($qty)!==$qty) throw new RuntimeException('Cylinder quantity must be a whole number.');
                if($rate<0) throw new RuntimeException('Rate cannot be negative.');
                $standardRate=$rate;
                if($type==='empty_sale'){
                    $available=$this->cylinders->available($locationId,$typeId,'empty');
                    if(count($available)<(int)$qty) throw new RuntimeException('Insufficient empty cylinders of '.$ct['name'].'.');
                    foreach(array_slice($available,0,(int)$qty) as $unit) $inventory[]=['line_no'=>$i+1,'type'=>'empty_cylinder','cylinder_type_id'=>$typeId,'quantity'=>1,'direction'=>'out','unit_id'=>(int)$unit['id']];
                } else {
                    for($n=0;$n<(int)$qty;$n++) $inventory[]=['line_no'=>$i+1,'type'=>'empty_cylinder','cylinder_type_id'=>$typeId,'quantity'=>1,'direction'=>'in','unit_id'=>null];
                }
            }else{
                throw new RuntimeException('Unsupported sale line type.');
            }

            $lineTotal=($type==='refill_kg')?$gasKg*$rate:$qty*$rate;
            $isCustom=$standardRate!==null && abs($rate-$standardRate)>0.00001;
            $customRate=$customRate||$isCustom;
            $subtotal+=$lineTotal;$totalKg+=$gasKg;
            $storedLineType=$type==='refill_kg'?'refill_kg':(($type==='empty_intake'||$type==='empty_sale')?'empty_cylinder':'filled_cylinder');
            $prepared[]=['line_no'=>$i+1,'line_type'=>$storedLineType,'cylinder_type_id'=>$typeId,'quantity'=>$qty,'gas_weight_kg'=>$gasKg,'applied_rate'=>$rate,'standard_rate'=>$standardRate,'custom_rate_flag'=>$isCustom?1:0,'empty_cylinder_received'=>$emptyReceived,'line_discount'=>0,'line_total'=>$lineTotal,'notes'=>trim((string)($line['notes']??''))?:null];
        }

        $discount=max(0,(float)($payload['discount_amount']??0));
        if($discount>$subtotal) throw new RuntimeException('Discount cannot exceed subtotal.');
        $total=$subtotal-$discount;$paymentTotal=0;$credit=0;$cashAmount=0;
        foreach($payments as $p){
            $mode=(string)($p['payment_mode']??'');$amount=(float)($p['amount']??0);
            if(!in_array($mode,['cash','cheque','online','credit'],true)||$amount<=0) throw new RuntimeException('Invalid payment.');
            if(!$customerId && $mode!=='cash') throw new RuntimeException('Walk-in sales are cash only.');
            $paymentTotal+=$amount;if($mode==='credit') $credit+=$amount;if($mode==='cash') $cashAmount+=$amount;
        }
        if(abs($paymentTotal-$total)>0.01) throw new RuntimeException('Payment total must equal sale total.');
        if($customerId && $credit>0 && $this->customerBalance($customerId)+$credit>(float)$customer['credit_limit']) throw new RuntimeException('Credit limit exceeded.');

        $this->db->transBegin();
        try{
            if($customerId){
                $lockedCustomer=$this->db->query("SELECT * FROM customers WHERE id=? FOR UPDATE",[$customerId])->getRowArray();
                if(!$lockedCustomer || !(int)$lockedCustomer['is_active']) throw new RuntimeException('Customer is unavailable.');
                $customer=$lockedCustomer;
                if($credit>0 && $this->customerBalance($customerId)+$credit>(float)$customer['credit_limit']) throw new RuntimeException('Credit limit exceeded.');
            }
            $this->acquireInventoryLocks($locationId,$inventory);
            $overrideConfirmed=!empty($payload['stock_override_confirmed']);
            $control=new InventoryControlService();
            $gasGroups=[];
            foreach($inventory as $m){
                if($m['type']!=='gas_kg' || $m['direction']!=='out') continue;
                $lineNo=(int)$m['line_no'];
                $line=$prepared[$lineNo-1]??null;
                $policyTypeId=($line && in_array($line['line_type'],['filled_cylinder','cylinder_exchange'],true))?(int)$line['cylinder_type_id']:null;
                if(!isset($gasGroups[$lineNo])) $gasGroups[$lineNo]=['qty'=>0,'type_id'=>$policyTypeId];
                $gasGroups[$lineNo]['qty']+=(float)$m['quantity'];
            }
            $virtualGasStock=(new InventoryService())->stock($locationId,'gas_kg',null,$transactionAt);
            foreach($gasGroups as $group){
                $policy=$control->policy($locationId,$group['type_id']);
                $qty=(float)$group['qty'];
                if(!(int)$policy['stock_validation_enabled']){
                    if(!$overrideConfirmed) throw new RuntimeException('Gas stock validation is OFF. Confirm the stock override before posting this sale.');
                }elseif($virtualGasStock+0.00001<$qty){
                    throw new RuntimeException('Insufficient gas stock. Available: '.number_format($virtualGasStock,3).' kg; required: '.number_format($qty,3).' kg.');
                }
                $virtualGasStock-=$qty;
            }
            foreach($inventory as $m){
                if($m['type']==='gas_kg') continue;
                $this->assertStock($locationId,$m['type'],$m['cylinder_type_id'],$m['quantity'],$m['direction'],$transactionAt);
            }
            $saleNo='S'.date('YmdHis').'-'.random_int(100,999);
            $scenarioTypes=array_values(array_unique(array_column($lines,'line_type')));
            $transactionType=count($scenarioTypes)>1?'mixed':($scenarioTypes[0]==='refill_kg'?'refill_service':$scenarioTypes[0]);
            $this->db->table('sales')->insert(['sale_no'=>$saleNo,'location_id'=>$locationId,'customer_id'=>$customerId,'transaction_type'=>$transactionType,'status'=>'posted','transaction_at'=>$transactionAt,'total_kg'=>$totalKg,'subtotal'=>$subtotal,'discount_amount'=>$discount,'total_amount'=>$total,'credit_amount'=>$credit,'custom_rate_flag'=>$customRate?1:0,'notes'=>$notes,'created_by'=>$userId]);
            $saleId=(int)$this->db->insertID();
            foreach($prepared as $row){$row['sale_id']=$saleId;$this->db->table('sale_items')->insert($row);}
            $lineIds=[];
            foreach($this->db->table('sale_items')->select('id,line_no')->where('sale_id',$saleId)->get()->getResultArray() as $row) $lineIds[(int)$row['line_no']]=(int)$row['id'];
            foreach($inventory as $m){
                $this->db->table('inventory_movements')->insert(['location_id'=>$locationId,'inventory_type'=>$m['type'],'cylinder_type_id'=>$m['cylinder_type_id'],'quantity'=>$m['quantity'],'direction'=>$m['direction'],'movement_at'=>$transactionAt,'source_type'=>'sale','source_id'=>$saleId,'source_line_id'=>$lineIds[(int)$m['line_no']]??null,'cylinder_unit_id'=>$m['unit_id']??null,'created_by'=>$userId]);
            }
            foreach($inventory as $m){
                if(($m['unit_id']??null) && in_array($m['type'],['filled_cylinder','empty_cylinder'],true) && $m['direction']==='out') $this->cylinders->markSold((int)$m['unit_id']);
            }
            foreach($inventory as $m){
                if($m['type']==='empty_cylinder' && $m['direction']==='in' && !($m['unit_id']??null)){
                    $ids=$this->cylinders->createUnits($locationId,(int)$m['cylinder_type_id'],1,'empty',0,$userId,'sale',$saleId);
                    $moved=$this->db->table('inventory_movements')->where(['source_type'=>'sale','source_id'=>$saleId,'inventory_type'=>'empty_cylinder','direction'=>'in','cylinder_unit_id'=>null])->orderBy('id','DESC')->get()->getRowArray();
                    if($moved) $this->db->table('inventory_movements')->where('id',$moved['id'])->update(['cylinder_unit_id'=>$ids[0]]);
                }
            }
            foreach($payments as $p) $this->db->table('sale_payments')->insert(['sale_id'=>$saleId,'payment_mode'=>$p['payment_mode'],'amount'=>(float)$p['amount'],'reference_no'=>trim((string)($p['reference_no']??''))?:null,'payment_at'=>$transactionAt,'received_by'=>$userId]);
            if($cashAmount>0){$session=$this->cash->openSessionForLocation($locationId);if(!$session) throw new RuntimeException('Open the counter cash session before posting a cash sale.');$this->cash->postSaleCash((int)$session['id'],$saleId,$cashAmount,$userId,$transactionAt);}
            if(!$this->db->transStatus()) throw new RuntimeException('Sale posting failed.');
            $this->db->transCommit();
            $this->releaseInventoryLocks();
            return ['id'=>$saleId,'sale_no'=>$saleNo,'total'=>$total,'customer_id'=>$customerId,'credit_amount'=>$credit];
        }catch(\Throwable $e){
            $this->releaseInventoryLocks();
            $this->db->transRollback();
            throw $e;
        }
    }

    public function void(int $saleId,int $userId,int $locationId,string $reason): string
    {
        $reason=trim($reason);
        if($reason==='') throw new RuntimeException('Void reason is required.');
        $this->db->transBegin();
        try{
            $sale=$this->db->query("SELECT * FROM sales WHERE id=? AND location_id=? FOR UPDATE",[$saleId,$locationId])->getRowArray();
            if(!$sale) throw new RuntimeException('Sale not found.');
            if($sale['status']!=='posted') throw new RuntimeException('Only posted sales can be voided.');

            $movements=$this->db->table('inventory_movements')->where('source_type','sale')->where('source_id',$saleId)->get()->getResultArray();
            $this->acquireInventoryLocks($locationId,$movements);
            foreach($movements as $m){
                $reverse=$m['direction']==='in'?'out':'in';
                $this->db->table('inventory_movements')->insert(['location_id'=>$m['location_id'],'inventory_type'=>$m['inventory_type'],'cylinder_type_id'=>$m['cylinder_type_id'],'quantity'=>$m['quantity'],'direction'=>$reverse,'movement_at'=>date('Y-m-d H:i:s'),'source_type'=>'sale_void','source_id'=>$saleId,'source_line_id'=>$m['source_line_id']??null,'cylinder_unit_id'=>$m['cylinder_unit_id']??null,'created_by'=>$userId,'notes'=>'Reversal of sale '.$sale['sale_no']]);
                $unitId=(int)($m['cylinder_unit_id']??0);
                if($unitId && $m['inventory_type']==='filled_cylinder' && $m['direction']==='out'){
                    $gasRow=$this->db->table('inventory_movements')->where('source_type','sale')->where('source_id',$saleId)->where('inventory_type','gas_kg')->where('direction','out')->where('cylinder_unit_id',$unitId)->orderBy('id')->get()->getRowArray();
                    $this->cylinders->restoreFilled($unitId,(float)($gasRow['quantity']??0));
                }elseif($unitId && $m['inventory_type']==='empty_cylinder' && $m['direction']==='out') $this->cylinders->restoreEmpty($unitId);
                elseif($unitId && $m['inventory_type']==='empty_cylinder' && $m['direction']==='in') $this->cylinders->markSold($unitId);
            }
            $cashRows=$this->db->table('cash_transactions')->where('reference_type','sale')->where('reference_id',$saleId)->where('transaction_type','sale_cash')->where('direction','in')->get()->getResultArray();
            $existingReversal=(int)$this->db->table('cash_transactions')->where('reference_type','sale_void')->where('reference_id',$saleId)->where('transaction_type','sale_cash')->where('direction','out')->countAllResults();
            if($existingReversal>0) throw new RuntimeException('Cash reversal for this sale already exists.');
            foreach($cashRows as $cash){
                $this->db->table('cash_transactions')->insert([
                    'cash_session_id'=>$cash['cash_session_id'],'transaction_type'=>'sale_cash','direction'=>'out',
                    'amount'=>$cash['amount'],'transaction_at'=>date('Y-m-d H:i:s'),'reference_type'=>'sale_void',
                    'reference_id'=>$saleId,'created_by'=>$userId,'notes'=>'Cash reversal of sale '.$sale['sale_no']
                ]);
            }
            $this->db->table('sales')->where('id',$saleId)->update(['status'=>'voided','voided_by'=>$userId,'voided_at'=>date('Y-m-d H:i:s'),'void_reason'=>$reason]);
            if(!$this->db->transStatus()) throw new RuntimeException('Sale void failed.');
            $this->db->transCommit();
            $this->releaseInventoryLocks();
            return $sale['sale_no'];
        }catch(\Throwable $e){
            $this->releaseInventoryLocks();
            $this->db->transRollback();
            throw $e;
        }
    }

    public function customerBalance(int $customerId): float
    {
        $s=$this->db->table('sales')->selectSum('credit_amount','credit')->where('customer_id',$customerId)->where('status','posted')->get()->getRowArray();
        $r=$this->db->table('customer_receipts')->selectSum('amount','paid')->where('customer_id',$customerId)->where('status','posted')->get()->getRowArray();
        $c=$this->customers->find($customerId);
        return (float)($c['opening_balance']??0)+(float)($s['credit']??0)-(float)($r['paid']??0);
    }

    protected function acquireInventoryLocks(int $locationId,array $movements): void
    {
        $keys=[];
        foreach($movements as $m){
            $type=(string)($m['type']??$m['inventory_type']??'');
            $typeId=$m['cylinder_type_id']??null;
            if($type==='') throw new RuntimeException('Invalid inventory lock key.');
            $keys[$type.'|'.($typeId===null?'null':(string)$typeId)]=true;
        }
        $keys=array_keys($keys);
        sort($keys,SORT_STRING);
        foreach($keys as $key){
            $lockKey='lpg_inv_'.$locationId.'_'.substr(hash('sha256',$key),0,48);
            $result=$this->db->query("SELECT GET_LOCK(?,5) AS locked",[$lockKey])->getRowArray();
            if((int)($result['locked']??0)!==1) throw new RuntimeException('Inventory is busy; please retry the transaction.');
            $this->inventoryLocks[]=$lockKey;
        }
    }

    protected function releaseInventoryLocks(): void
    {
        if(!$this->inventoryLocks) return;
        foreach(array_reverse($this->inventoryLocks) as $lockKey){
            $this->db->query("SELECT RELEASE_LOCK(?) AS released",[$lockKey]);
        }
        $this->inventoryLocks=[];
    }

    protected function assertStock(int $locationId,string $type,?int $typeId,float $qty,string $direction,string $at): void
    {
        if($direction!=='out') return;
        $q=$this->db->table('inventory_opening_balances')->selectSum('quantity','qty')->where('location_id',$locationId)->where('inventory_type',$type)->where('cylinder_type_id',$typeId)->where('inventory_date <=',substr($at,0,10))->get()->getRowArray();
        $in=$this->db->table('inventory_movements')->selectSum('quantity','qty')->where('location_id',$locationId)->where('inventory_type',$type)->where('cylinder_type_id',$typeId)->where('direction','in')->where('movement_at <=',$at)->get()->getRowArray();
        $out=$this->db->table('inventory_movements')->selectSum('quantity','qty')->where('location_id',$locationId)->where('inventory_type',$type)->where('cylinder_type_id',$typeId)->where('direction','out')->where('movement_at <=',$at)->get()->getRowArray();
        $stock=(float)($q['qty']??0)+(float)($in['qty']??0)-(float)($out['qty']??0);
        if($stock+0.00001<$qty) throw new RuntimeException('Insufficient '.$type.' stock.');
    }
}
