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

        $prepared=[];$subtotal=0;$totalKg=0;$customRate=false;$gasRequirements=[];
        foreach($lines as $i=>$line){
            $mode=(string)($line['sale_mode']??'');
            $allowed=['sell_gas_only','replace_same','sell_filled','replace_different','sell_empty'];
            if(!in_array($mode,$allowed,true)) throw new RuntimeException('Unsupported POS transaction type on line '.($i+1).'.');

            $qty=(float)($line['quantity']??0);
            $typeId=isset($line['cylinder_type_id'])&&$line['cylinder_type_id']!==''?(int)$line['cylinder_type_id']:null;
            $receivedTypeId=isset($line['received_cylinder_type_id'])&&$line['received_cylinder_type_id']!==''?(int)$line['received_cylinder_type_id']:null;
            $gasRateInput=trim((string)($line['gas_rate']??''))===''?null:(float)$line['gas_rate'];
            $cylinderRateInput=trim((string)($line['cylinder_rate']??''))===''?null:(float)$line['cylinder_rate'];
            if($qty<=0) throw new RuntimeException('Line '.($i+1).' quantity must be greater than zero.');
            if(($gasRateInput!==null && $gasRateInput<0)||($cylinderRateInput!==null && $cylinderRateInput<0)) throw new RuntimeException('Line '.($i+1).' rate cannot be negative.');

            $type=$typeId?(new CylinderTypeModel())->find($typeId):null;
            if(in_array($mode,['sell_gas_only','replace_same','sell_filled','replace_different','sell_empty'],true) && !$typeId) throw new RuntimeException('Cylinder type is required on line '.($i+1).'.');
            if($typeId && (!$type || !(int)$type['is_active'])) throw new RuntimeException('Invalid or inactive cylinder type on line '.($i+1).'.');

            $gasRate=0;$cylinderRate=0;$gasKg=0;$standardGasRate=null;$standardCylinderRate=null;
            $emptyReceived=0;$previewUnits=[];

            if(in_array($mode,['sell_gas_only','replace_same','sell_filled','replace_different'],true)){
                $standardGasRate=$this->rates->currentKgRate($transactionAt);
                if($standardGasRate===null) throw new RuntimeException('No effective gas/kg rate exists.');
                $gasRate=$gasRateInput??$standardGasRate;
            }
            if(in_array($mode,['sell_filled','replace_different','sell_empty'],true)){
                $standardCylinderRate=$this->rates->currentCylinderRate($typeId,$transactionAt);
                if($standardCylinderRate===null) throw new RuntimeException('No effective cylinder price exists for '.$type['name'].'.');
                $cylinderRate=$cylinderRateInput??$standardCylinderRate;
            }

            if($mode==='sell_gas_only'){
                $gasKg=(float)($line['gas_weight_kg']??$qty);
                if($gasKg<=0) throw new RuntimeException('Gas KG must be greater than zero on line '.($i+1).'.');
                $qty=$gasKg;
                $previewUnits=$this->cylinders->availableForDisplay($locationId,$typeId,'filled');
                $sourceGas=array_sum(array_map(static fn($u)=>(float)$u['gas_weight_kg'],$previewUnits));
                if($sourceGas+0.00001<$gasKg) throw new RuntimeException('Insufficient source gas in '.$type['name'].'. Available: '.number_format($sourceGas,3).' kg.');
                $gasRequirements[]=['line_no'=>$i+1,'type_id'=>$typeId,'gas_kg'=>$gasKg];
            }elseif(in_array($mode,['replace_same','sell_filled','replace_different'],true)){
                if(floor($qty)!==$qty) throw new RuntimeException('Cylinder quantity on line '.($i+1).' must be a whole number.');
                $previewUnits=$this->cylinders->availableForDisplay($locationId,$typeId,'filled');
                if(count($previewUnits)<(int)$qty) throw new RuntimeException('Insufficient filled cylinders of '.$type['name'].'.');
                $selected=array_slice($previewUnits,0,(int)$qty);
                $gasKg=array_sum(array_map(static fn($u)=>(float)$u['gas_weight_kg'],$selected));
                if($gasKg<=0) throw new RuntimeException('Selected filled cylinders contain no gas.');
                if($mode==='replace_same'){
                    $receivedTypeId=$typeId;
                }elseif($mode==='replace_different'){
                    if(!$receivedTypeId) throw new RuntimeException('Received empty cylinder type is required on line '.($i+1).'.');
                    if($receivedTypeId===$typeId) throw new RuntimeException('Different-capacity replacement must use a different received cylinder type.');
                    $receivedType=$this->types->find($receivedTypeId);
                    if(!$receivedType || !(int)$receivedType['is_active']) throw new RuntimeException('Invalid or inactive received empty-cylinder type.');
                }
                $gasRequirements[]=['line_no'=>$i+1,'type_id'=>$typeId,'gas_kg'=>$gasKg];
                if($receivedTypeId) $emptyReceived=$qty;
            }elseif($mode==='sell_empty'){
                if(floor($qty)!==$qty) throw new RuntimeException('Empty-cylinder quantity on line '.($i+1).' must be a whole number.');
                $availableEmpty=$this->cylinders->availableForDisplay($locationId,$typeId,'empty');
                if(count($availableEmpty)<(int)$qty) throw new RuntimeException('Insufficient empty cylinders of '.$type['name'].'.');
            }

            $customGas=$standardGasRate!==null && abs($gasRate-$standardGasRate)>0.00001;
            $customCylinder=$standardCylinderRate!==null && abs($cylinderRate-$standardCylinderRate)>0.00001;
            $customRate=$customRate||$customGas||$customCylinder;
            $lineTotal=0;
            if(in_array($mode,['sell_gas_only','replace_same'],true)) $lineTotal=$gasKg*$gasRate;
            elseif(in_array($mode,['sell_filled','replace_different'],true)) $lineTotal=$gasKg*$gasRate+$qty*$cylinderRate;
            elseif($mode==='sell_empty') $lineTotal=$qty*$cylinderRate;

            $subtotal+=$lineTotal;$totalKg+=$gasKg;
            $storedLineType=$mode==='sell_empty'?'empty_cylinder':($mode==='sell_gas_only'?'refill_kg':'filled_cylinder');
            $noteParts=[];
            if($mode==='sell_gas_only') $noteParts[]='Gas source: '.$type['code'].' — '.$type['name'];
            if($mode==='replace_different') $noteParts[]='Received empty: '.($receivedType['code']??'').' — '.($receivedType['name']??'');
            $prepared[]=[
                'line_no'=>$i+1,'sale_mode'=>$mode,'line_type'=>$storedLineType,'cylinder_type_id'=>$typeId,
                'received_cylinder_type_id'=>$receivedTypeId,'quantity'=>$qty,'gas_weight_kg'=>$gasKg,
                'gas_rate'=>$gasRate,'cylinder_rate'=>$cylinderRate,'standard_gas_rate'=>$standardGasRate,
                'standard_cylinder_rate'=>$standardCylinderRate,'custom_rate_flag'=>($customGas||$customCylinder)?1:0,
                'empty_cylinder_received'=>$emptyReceived,'line_discount'=>0,'line_total'=>$lineTotal,
                'notes'=>implode(' | ',$noteParts)?:null
            ];
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

        $this->db->transBegin();
        try{
            if($customerId){
                $lockedCustomer=$this->db->query("SELECT * FROM customers WHERE id=? FOR UPDATE",[$customerId])->getRowArray();
                if(!$lockedCustomer || !(int)$lockedCustomer['is_active']) throw new RuntimeException('Customer is unavailable.');
                $customer=$lockedCustomer;
            }

            $lockRows=[];
            foreach($prepared as $row){
                if(in_array($row['sale_mode'],['sell_gas_only','replace_same','sell_filled','replace_different'],true)){
                    $lockRows[]=['type'=>'gas_kg','cylinder_type_id'=>null];
                    $lockRows[]=['type'=>'filled_cylinder','cylinder_type_id'=>(int)$row['cylinder_type_id']];
                }
                if(in_array($row['sale_mode'],['replace_same','replace_different'],true)) $lockRows[]=['type'=>'empty_cylinder','cylinder_type_id'=>(int)$row['received_cylinder_type_id']];
                if($row['sale_mode']==='sell_empty') $lockRows[]=['type'=>'empty_cylinder','cylinder_type_id'=>(int)$row['cylinder_type_id']];
            }
            $this->acquireInventoryLocks($locationId,$lockRows);

            $gasAvailable=(float)($this->db->query("SELECT COALESCE(SUM(gas_weight_kg),0) AS gas_stock FROM cylinder_units WHERE location_id=? AND status='filled'",[$locationId])->getRowArray()['gas_stock']??0);
            $gasGroups=[];
            foreach($prepared as $idx=>$row){
                if(!$row['gas_weight_kg']) continue;
                if(!isset($gasGroups[$idx])) $gasGroups[$idx]=['qty'=>0,'type_id'=>(int)$row['cylinder_type_id']];
                $gasGroups[$idx]['qty']+=(float)$row['gas_weight_kg'];
            }
            $overrideConfirmed=!empty($payload['stock_override_confirmed']);
            $control=new InventoryControlService();
            $virtualGasStock=$gasAvailable;
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

            $inventory=[];
            foreach($prepared as $idx=>&$row){
                $mode=$row['sale_mode'];$typeId=(int)$row['cylinder_type_id'];$qty=(float)$row['quantity'];
                if($mode==='sell_gas_only'){
                    $remaining=(float)$row['gas_weight_kg'];
                    $units=$this->cylinders->available($locationId,$typeId,'filled');
                    foreach($units as $unit){
                        if($remaining<=0.00001) break;
                        $before=(float)$unit['gas_weight_kg'];$used=min($before,$remaining);$after=$before-$used;
                        if($after<=0.00001){
                            $this->db->table('cylinder_units')->where('id',(int)$unit['id'])->update(['status'=>'empty','gas_weight_kg'=>0]);
                            $inventory[]=['line_no'=>$row['line_no'],'type'=>'gas_kg','cylinder_type_id'=>null,'quantity'=>$used,'direction'=>'out','unit_id'=>(int)$unit['id']];
                            $inventory[]=['line_no'=>$row['line_no'],'type'=>'filled_cylinder','cylinder_type_id'=>$typeId,'quantity'=>1,'direction'=>'out','unit_id'=>(int)$unit['id'],'transition'=>'to_empty'];
                            $inventory[]=['line_no'=>$row['line_no'],'type'=>'empty_cylinder','cylinder_type_id'=>$typeId,'quantity'=>1,'direction'=>'in','unit_id'=>(int)$unit['id'],'transition'=>'from_filled'];
                        }else{
                            $this->db->table('cylinder_units')->where('id',(int)$unit['id'])->update(['gas_weight_kg'=>$after,'status'=>'filled']);
                            $inventory[]=['line_no'=>$row['line_no'],'type'=>'gas_kg','cylinder_type_id'=>null,'quantity'=>$used,'direction'=>'out','unit_id'=>(int)$unit['id']];
                        }
                        $remaining-=$used;
                    }
                    if($remaining>0.00001) throw new RuntimeException('Source cylinder gas changed while posting. Please retry the sale.');
                }elseif(in_array($mode,['replace_same','sell_filled','replace_different'],true)){
                    $units=$this->cylinders->available($locationId,$typeId,'filled');
                    if(count($units)<(int)$qty) throw new RuntimeException('Filled-cylinder stock changed while posting. Please retry the sale.');
                    $selected=array_slice($units,0,(int)$qty);$actualGas=0;
                    foreach($selected as $unit){
                        $actualGas+=(float)$unit['gas_weight_kg'];
                        $inventory[]=['line_no'=>$row['line_no'],'type'=>'gas_kg','cylinder_type_id'=>null,'quantity'=>(float)$unit['gas_weight_kg'],'direction'=>'out','unit_id'=>(int)$unit['id']];
                        $inventory[]=['line_no'=>$row['line_no'],'type'=>'filled_cylinder','cylinder_type_id'=>$typeId,'quantity'=>1,'direction'=>'out','unit_id'=>(int)$unit['id'],'transition'=>'sold'];
                    }
                    if(abs($actualGas-(float)$row['gas_weight_kg'])>0.00001) throw new RuntimeException('Filled-cylinder gas weight changed while posting. Please retry the sale.');
                    if($row['received_cylinder_type_id']){
                        for($n=0;$n<(int)$qty;$n++) $inventory[]=['line_no'=>$row['line_no'],'type'=>'empty_cylinder','cylinder_type_id'=>(int)$row['received_cylinder_type_id'],'quantity'=>1,'direction'=>'in','unit_id'=>null,'transition'=>'new_empty'];
                    }
                }elseif($mode==='sell_empty'){
                    $units=$this->cylinders->available($locationId,$typeId,'empty');
                    if(count($units)<(int)$qty) throw new RuntimeException('Empty-cylinder stock changed while posting. Please retry the sale.');
                    foreach(array_slice($units,0,(int)$qty) as $unit) $inventory[]=['line_no'=>$row['line_no'],'type'=>'empty_cylinder','cylinder_type_id'=>$typeId,'quantity'=>1,'direction'=>'out','unit_id'=>(int)$unit['id'],'transition'=>'sold'];
                }
            }
            unset($row);

            $preparedSubtotal=array_sum(array_map(static fn($r)=>(float)$r['line_total'],$prepared));
            $discount=max(0,(float)($payload['discount_amount']??0));if($discount>$preparedSubtotal) throw new RuntimeException('Discount cannot exceed subtotal.');
            $total=$preparedSubtotal-$discount;$paymentTotal=0;$credit=0;$cashAmount=0;
            foreach($payments as $p){
                $mode=(string)$p['payment_mode'];$amount=(float)$p['amount'];$paymentTotal+=$amount;
                if($mode==='credit')$credit+=$amount;if($mode==='cash')$cashAmount+=$amount;
            }
            if(abs($paymentTotal-$total)>0.01) throw new RuntimeException('Payment total must equal the recalculated sale total.');
            if($customerId && $credit>0 && $this->customerBalance($customerId)+$credit>(float)$customer['credit_limit']) throw new RuntimeException('Credit limit exceeded.');

            $saleNo='S'.date('YmdHis').'-'.random_int(100,999);
            $scenarioTypes=array_values(array_unique(array_column($prepared,'sale_mode')));
            $transactionType=count($scenarioTypes)>1?'mixed':match($scenarioTypes[0]){
                'sell_gas_only'=>'refill_service','replace_same'=>'cylinder_exchange','sell_filled'=>'filled_cylinder',
                'replace_different'=>'mixed','sell_empty'=>'empty_sale'
            };
            $this->db->table('sales')->insert(['sale_no'=>$saleNo,'location_id'=>$locationId,'customer_id'=>$customerId,'transaction_type'=>$transactionType,'status'=>'posted','transaction_at'=>$transactionAt,'total_kg'=>array_sum(array_map(static fn($r)=>(float)$r['gas_weight_kg'],$prepared)),'subtotal'=>$preparedSubtotal,'discount_amount'=>$discount,'total_amount'=>$total,'credit_amount'=>$credit,'custom_rate_flag'=>$customRate?1:0,'notes'=>$notes,'created_by'=>$userId]);
            $saleId=(int)$this->db->insertID();

            foreach($prepared as $row){
                $gasKg=(float)$row['gas_weight_kg'];$qty=(float)$row['quantity'];
                if(in_array($row['sale_mode'],['sell_filled','replace_different'],true)) $appliedRate=(($gasKg/max($qty,1))*$row['gas_rate'])+$row['cylinder_rate'];
                elseif(in_array($row['sale_mode'],['sell_gas_only','replace_same'],true)) $appliedRate=$row['gas_rate']; else $appliedRate=$row['cylinder_rate'];
                $standardRate=$row['standard_gas_rate']!==null?(in_array($row['sale_mode'],['sell_filled','replace_different'],true)?((($gasKg/max($qty,1))*$row['standard_gas_rate'])+$row['standard_cylinder_rate']):$row['standard_gas_rate']):$row['standard_cylinder_rate'];
                $this->db->table('sale_items')->insert([
                    'sale_id'=>$saleId,'line_no'=>$row['line_no'],'line_type'=>$row['line_type'],'cylinder_type_id'=>$row['cylinder_type_id'],
                    'quantity'=>$qty,'gas_weight_kg'=>$gasKg,'applied_rate'=>$appliedRate,'standard_rate'=>$standardRate,
                    'custom_rate_flag'=>$row['custom_rate_flag'],'empty_cylinder_received'=>$row['empty_cylinder_received'],
                    'line_discount'=>0,'line_total'=>$row['line_total'],'notes'=>$row['notes']
                ]);
            }
            $lineIds=[];foreach($this->db->table('sale_items')->select('id,line_no')->where('sale_id',$saleId)->get()->getResultArray() as $row)$lineIds[(int)$row['line_no']]=(int)$row['id'];

            foreach($inventory as $idx=>$m){
                $this->db->table('inventory_movements')->insert([
                    'location_id'=>$locationId,'inventory_type'=>$m['type'],'cylinder_type_id'=>$m['cylinder_type_id'],
                    'quantity'=>$m['quantity'],'direction'=>$m['direction'],'movement_at'=>$transactionAt,'source_type'=>'sale',
                    'source_id'=>$saleId,'source_line_id'=>$lineIds[(int)$m['line_no']]??null,'cylinder_unit_id'=>$m['unit_id']??null,
                    'created_by'=>$userId,'notes'=>($m['transition']??null)
                ]);
                $inventory[$idx]['movement_id']=(int)$this->db->insertID();
            }

            foreach($inventory as $m){
                if($m['type']==='empty_cylinder' && $m['direction']==='in' && empty($m['unit_id'])){
                    $ids=$this->cylinders->createUnits($locationId,(int)$m['cylinder_type_id'],1,'empty',0,$userId,'sale',$saleId);
                    $this->db->table('inventory_movements')->where('id',$m['movement_id'])->update(['cylinder_unit_id'=>$ids[0]]);
                }
            }

            foreach($inventory as $m){
                if(($m['unit_id']??null) && $m['type']==='filled_cylinder' && $m['direction']==='out' && ($m['transition']??'')==='sold') $this->cylinders->markSold((int)$m['unit_id']);
                if(($m['unit_id']??null) && $m['type']==='empty_cylinder' && $m['direction']==='out') $this->cylinders->markSold((int)$m['unit_id']);
            }

            foreach($payments as $p) $this->db->table('sale_payments')->insert(['sale_id'=>$saleId,'payment_mode'=>$p['payment_mode'],'amount'=>(float)$p['amount'],'reference_no'=>trim((string)($p['reference_no']??''))?:null,'payment_at'=>$transactionAt,'received_by'=>$userId]);
            if($cashAmount>0){
                $session=$this->cash->openSessionForLocation($locationId);
                if(!$session) throw new RuntimeException('Open the counter cash session before posting a cash sale.');
                $this->cash->postSaleCash((int)$session['id'],$saleId,$cashAmount,$userId,$transactionAt);
            }
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
