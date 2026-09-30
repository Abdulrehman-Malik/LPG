<?php

namespace App\Services;

use App\Models\CylinderTypeModel;
use App\Models\CustomerModel;
use App\Models\GasRateModel;
use App\Services\CashService;
use Config\Database;
use RuntimeException;

class SalesService
{
    protected $db;
    protected $types;
    protected $customers;
    protected $rates;
    protected $cash;

    public function __construct()
    {
        $this->db=Database::connect();
        $this->types=new CylinderTypeModel();
        $this->customers=new CustomerModel();
        $this->rates=new GasRateModel();
        $this->cash=new CashService();
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
                $gasKg=$qty*(float)$ct['capacity_kg'];
                $standardRate=$this->rates->currentCylinderRate($typeId,$transactionAt);
                if($standardRate===null) throw new RuntimeException('No effective package rate exists for '.$ct['name'].'.');
                if($rate<=0) $rate=$standardRate;
                if($emptyReceived<0) throw new RuntimeException('Empty cylinder quantity cannot be negative.');
                if($type==='cylinder_exchange' && $emptyReceived<=0) $emptyReceived=$qty;
                if($type==='filled_cylinder' && $emptyReceived>0) throw new RuntimeException('Empty cylinder intake must use cylinder exchange.');
                $inventory[]=['line_no'=>$i+1,'type'=>'gas_kg','cylinder_type_id'=>null,'quantity'=>$gasKg,'direction'=>'out'];
                $inventory[]=['line_no'=>$i+1,'type'=>'filled_cylinder','cylinder_type_id'=>$typeId,'quantity'=>$qty,'direction'=>'out'];
                if($emptyReceived>0) $inventory[]=['line_no'=>$i+1,'type'=>'empty_cylinder','cylinder_type_id'=>$typeId,'quantity'=>$emptyReceived,'direction'=>'in'];
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
                if($rate<0) throw new RuntimeException('Rate cannot be negative.');
                $standardRate=$rate;
                $inventory[]=['line_no'=>$i+1,'type'=>'empty_cylinder','cylinder_type_id'=>$typeId,'quantity'=>$qty,'direction'=>$type==='empty_intake'?'in':'out'];
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
            foreach($inventory as $m) $this->assertStock($locationId,$m['type'],$m['cylinder_type_id'],$m['quantity'],$m['direction'],$transactionAt);
            $saleNo='S'.date('YmdHis').'-'.random_int(100,999);
            $scenarioTypes=array_values(array_unique(array_column($lines,'line_type')));
            $transactionType=count($scenarioTypes)>1?'mixed':($scenarioTypes[0]==='refill_kg'?'refill_service':$scenarioTypes[0]);
            $this->db->table('sales')->insert(['sale_no'=>$saleNo,'location_id'=>$locationId,'customer_id'=>$customerId,'transaction_type'=>$transactionType,'status'=>'posted','transaction_at'=>$transactionAt,'total_kg'=>$totalKg,'subtotal'=>$subtotal,'discount_amount'=>$discount,'total_amount'=>$total,'credit_amount'=>$credit,'custom_rate_flag'=>$customRate?1:0,'notes'=>$notes,'created_by'=>$userId]);
            $saleId=(int)$this->db->insertID();
            foreach($prepared as $row){$row['sale_id']=$saleId;$this->db->table('sale_items')->insert($row);}
            $lineIds=[];
            foreach($this->db->table('sale_items')->select('id,line_no')->where('sale_id',$saleId)->get()->getResultArray() as $row) $lineIds[(int)$row['line_no']]=(int)$row['id'];
            foreach($inventory as $m) $this->db->table('inventory_movements')->insert(['location_id'=>$locationId,'inventory_type'=>$m['type'],'cylinder_type_id'=>$m['cylinder_type_id'],'quantity'=>$m['quantity'],'direction'=>$m['direction'],'movement_at'=>$transactionAt,'source_type'=>'sale','source_id'=>$saleId,'source_line_id'=>$lineIds[(int)$m['line_no']]??null,'created_by'=>$userId]);
            foreach($payments as $p) $this->db->table('sale_payments')->insert(['sale_id'=>$saleId,'payment_mode'=>$p['payment_mode'],'amount'=>(float)$p['amount'],'reference_no'=>trim((string)($p['reference_no']??''))?:null,'payment_at'=>$transactionAt,'received_by'=>$userId]);
            if($cashAmount>0){$session=$this->cash->openSessionForLocation($locationId);if(!$session) throw new RuntimeException('Open the counter cash session before posting a cash sale.');$this->cash->postSaleCash((int)$session['id'],$saleId,$cashAmount,$userId,$transactionAt);}
            if(!$this->db->transStatus()) throw new RuntimeException('Sale posting failed.');
            $this->db->transCommit();
            return ['id'=>$saleId,'sale_no'=>$saleNo,'total'=>$total,'customer_id'=>$customerId,'credit_amount'=>$credit];
        }catch(\Throwable $e){$this->db->transRollback();throw $e;}
    }

    public function void(int $saleId,int $userId,int $locationId,string $reason): string
    {
        $reason=trim($reason);
        if($reason==='') throw new RuntimeException('Void reason is required.');
        $this->db->transBegin();
        try{
            $sale=$this->db->table('sales')->where('id',$saleId)->where('location_id',$locationId)->get()->getRowArray();
            if(!$sale) throw new RuntimeException('Sale not found.');
            if($sale['status']!=='posted') throw new RuntimeException('Only posted sales can be voided.');

            $movements=$this->db->table('inventory_movements')->where('source_type','sale')->where('source_id',$saleId)->get()->getResultArray();
            foreach($movements as $m){
                $reverse=$m['direction']==='in'?'out':'in';
                $this->db->table('inventory_movements')->insert(['location_id'=>$m['location_id'],'inventory_type'=>$m['inventory_type'],'cylinder_type_id'=>$m['cylinder_type_id'],'quantity'=>$m['quantity'],'direction'=>$reverse,'movement_at'=>date('Y-m-d H:i:s'),'source_type'=>'sale_void','source_id'=>$saleId,'source_line_id'=>$m['source_line_id']??null,'created_by'=>$userId,'notes'=>'Reversal of sale '.$sale['sale_no']]);
            }
            $cashRows=$this->db->table('cash_transactions')->where('reference_type','sale')->where('reference_id',$saleId)->where('transaction_type','sale_cash')->where('direction','in')->get()->getResultArray();
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
            return $sale['sale_no'];
        }catch(\Throwable $e){$this->db->transRollback();throw $e;}
    }

    public function customerBalance(int $customerId): float
    {
        $s=$this->db->table('sales')->selectSum('credit_amount','credit')->where('customer_id',$customerId)->where('status','posted')->get()->getRowArray();
        $r=$this->db->table('customer_receipts')->selectSum('amount','paid')->where('customer_id',$customerId)->where('status','posted')->get()->getRowArray();
        $c=$this->customers->find($customerId);
        return (float)($c['opening_balance']??0)+(float)($s['credit']??0)-(float)($r['paid']??0);
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