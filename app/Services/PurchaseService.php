<?php
namespace App\Services;
use Config\Database;
use RuntimeException;
class PurchaseService{
 protected $db; protected $inv; protected $cash; protected $cylinders;
 public function __construct(){ $this->db=Database::connect(); $this->inv=new InventoryService(); $this->cash=new CashService(); $this->cylinders=new CylinderUnitService(); }
 public function post(array $p,int $userId,int $locationId):array{
  $supplierId=(int)($p['supplier_id']??0); $lines=$p['lines']??[]; $payments=$p['payments']??[];
  if(!$supplierId||!$lines) throw new RuntimeException('Supplier and purchase lines are required.');
  $supplier=$this->db->table('suppliers')->where('id',$supplierId)->get()->getRowArray();
  if(!$supplier||(int)$supplier['is_active']!==1) throw new RuntimeException('Supplier is invalid or inactive.');
  $subtotal=0;$prepared=[];$inventory=[];$cash=0;$credit=0;
  foreach($lines as $i=>$l){
   $type=(string)($l['line_type']??'');$qty=(float)($l['quantity']??0);$rate=(float)($l['unit_rate']??0);$ct=isset($l['cylinder_type_id'])&&$l['cylinder_type_id']!==''?(int)$l['cylinder_type_id']:null;
   if($qty<=0||$rate<0) throw new RuntimeException('Invalid purchase line '.($i+1).'.');
   if(!in_array($type,['gas_kg','filled_cylinder','empty_cylinder'],true)) throw new RuntimeException('Invalid purchase type.');
   if($type!=='gas_kg'&&!$ct) throw new RuntimeException('Cylinder type required.');
   if($type==='gas_kg') $ct=null;

   $actualRaw=$l['actual_gas_weight_kg']??null;
   $actual=$actualRaw!==null && $actualRaw!==''?(float)$actualRaw:0;
   if($type==='filled_cylinder'){
    if(floor($qty)!==$qty) throw new RuntimeException('Filled cylinder quantity must be a whole number.');
    $ctRow=$this->db->table('cylinder_types')->where('id',$ct)->get()->getRowArray();
    if(!$ctRow || !(int)$ctRow['is_active']) throw new RuntimeException('Invalid or inactive cylinder type.');
    $cap=(float)$ctRow['capacity_kg'];
    $actual=$actualRaw!==null && $actualRaw!==''?$actual:$cap;
    if($actual<=0||$actual>$cap) throw new RuntimeException('Actual gas weight must be greater than zero and cannot exceed cylinder capacity.');
   } else {
    $actual=0;
   }

   $lineTotal=$qty*$rate;$subtotal+=$lineTotal;
   $prepared[]=['line_no'=>$i+1,'line_type'=>$type,'cylinder_type_id'=>$ct,'quantity'=>$qty,'actual_gas_weight_kg'=>$actual,'unit_rate'=>$rate,'line_total'=>$lineTotal];
   $inventory[]=['inventory_type'=>$type,'cylinder_type_id'=>$ct,'quantity'=>$qty,'actual_gas_weight_kg'=>$actual];
  }
  $discount=max(0,(float)($p['discount_amount']??0));if($discount>$subtotal)throw new RuntimeException('Discount exceeds subtotal.');
  $total=$subtotal-$discount;$paid=0;$normalizedPayments=[];
  foreach($payments as $pay){$mode=(string)($pay['payment_mode']??'');$amount=(float)($pay['amount']??0);if($amount<=0)continue;if(!in_array($mode,['cash','cheque','online'],true)||$amount<=0)throw new RuntimeException('Invalid purchase payment.');$paid+=$amount;$normalizedPayments[]=['payment_mode'=>$mode,'amount'=>$amount,'reference_no'=>trim((string)($pay['reference_no']??''))?:null];}
  $currentPayable=$total;$previousPayable=$this->supplierBalance($supplierId,$locationId);
  if($paid>$previousPayable+$currentPayable+0.01)throw new RuntimeException('Amount paid cannot exceed the supplier payable balance of Rs. '.number_format(max(0,$previousPayable+$currentPayable),2).'.');
  $currentPurchasePaid=min($paid,$currentPayable);$credit=max(0,$currentPayable-$currentPurchasePaid);$priorPayableSettlement=max(0,$paid-$currentPurchasePaid);
  $newPayable=max(0,$previousPayable-$priorPayableSettlement+$credit);
  if($newPayable>(float)$supplier['credit_limit']+0.01)throw new RuntimeException('Supplier credit limit exceeded. Available credit is Rs. '.number_format(max(0,(float)$supplier['credit_limit']-$previousPayable),2).'.');
  $this->db->transBegin();
  try{
   $supplier=$this->db->query('SELECT * FROM suppliers WHERE id=? FOR UPDATE',[$supplierId])->getRowArray();
   if($credit>0){$currentCredit=$this->supplierBalance($supplierId,$locationId);if($currentCredit+$credit>(float)$supplier['credit_limit'])throw new RuntimeException('Supplier credit limit exceeded.');}
   foreach($inventory as $m){if($this->inv->stock($locationId,$m['inventory_type'],$m['cylinder_type_id'])<0)throw new RuntimeException('Invalid inventory state.');}
   $no='P'.date('YmdHis').'-'.random_int(100,999);
   $this->db->table('purchases')->insert(['purchase_no'=>$no,'location_id'=>$locationId,'supplier_id'=>$supplierId,'status'=>'posted','transaction_at'=>date('Y-m-d H:i:s'),'subtotal'=>$subtotal,'discount_amount'=>$discount,'total_amount'=>$total,'credit_amount'=>$credit,'notes'=>trim((string)($p['notes']??''))?:null,'created_by'=>$userId]);$id=(int)$this->db->insertID();
   $purchaseLineIds=[];
   foreach($prepared as $row){
    $row['purchase_id']=$id;
    $this->db->table('purchase_items')->insert($row);
    $purchaseLineIds[(int)$row['line_no']]=(int)$this->db->insertID();
   }
   foreach($inventory as $index=>$m){
    $this->inv->receivePurchase(
      $locationId,
      $m['inventory_type'],
      $m['cylinder_type_id'],
      (float)$m['quantity'],
      (float)$m['actual_gas_weight_kg'],
      $id,
      $userId,
      $purchaseLineIds[$index+1] ?? null
    );
   }
   $remainingCurrent=$currentPayable;$cashPurchase=0;
   foreach($normalizedPayments as $pay){$applyCurrent=min((float)$pay['amount'],$remainingCurrent);$applyPrior=max(0,(float)$pay['amount']-$applyCurrent);
    if($applyCurrent>0){$this->db->table('purchase_payments')->insert(['purchase_id'=>$id,'payment_mode'=>$pay['payment_mode'],'amount'=>$applyCurrent,'reference_no'=>$pay['reference_no'],'payment_at'=>date('Y-m-d H:i:s'),'paid_by'=>$userId]);if($pay['payment_mode']==='cash')$cashPurchase+=$applyCurrent;}
    if($applyPrior>0){$paymentNo='SP'.date('YmdHis').'-'.random_int(100,999);$this->db->table('supplier_payments')->insert(['payment_no'=>$paymentNo,'location_id'=>$locationId,'supplier_id'=>$supplierId,'status'=>'posted','amount'=>$applyPrior,'payment_mode'=>$pay['payment_mode'],'payment_at'=>date('Y-m-d H:i:s'),'reference_no'=>$pay['reference_no'],'notes'=>'Applied to previous supplier payable during purchase '.$no,'created_by'=>$userId]);if($pay['payment_mode']==='cash'){ $session=$this->cash->openSessionForLocation($locationId);if(!$session)throw new RuntimeException('Open the counter cash session before posting a cash purchase.');$this->cash->postGeneric((int)$session['id'],'supplier_payment','out',$applyPrior,'supplier_payment',0,$userId,'Supplier payable settlement during purchase '.$no);}}
    $remainingCurrent=max(0,$remainingCurrent-(float)$pay['amount']);
   }
   if($cashPurchase>0){$session=$this->cash->openSessionForLocation($locationId);if(!$session)throw new RuntimeException('Open the counter cash session before posting a cash purchase.');$this->cash->postGeneric((int)$session['id'],'purchase_cash','out',$cashPurchase,'purchase',$id,$userId,'Purchase cash');}
   if(!$this->db->transStatus())throw new RuntimeException('Purchase posting failed.');
   $this->db->transCommit();AuditService::log('CREATE','purchase',$id,null,['purchase_no'=>$no,'total'=>$total,'amount_paid'=>$paid,'current_credit'=>$credit,'previous_payable'=>$previousPayable,'new_payable'=>$newPayable],$userId,$locationId);return ['id'=>$id,'purchase_no'=>$no,'total'=>$total,'previous_payable'=>$previousPayable,'amount_paid'=>$paid,'balance_payable'=>$newPayable];
  }catch(\Throwable $e){$this->db->transRollback();throw $e;}
 }
 public function supplierBalance(int $supplierId, ?int $locationId=null): float
 {
  $locationId=$locationId ?? (int)(session()->get('location_id') ?? 0);
  $s=$this->db->table('purchases')->selectSum('credit_amount','credit')->where('supplier_id',$supplierId)->where('status','posted');
  if($locationId>0) $s->where('location_id',$locationId);
  $s=$s->get()->getRowArray();
  $p=$this->db->table('supplier_payments')->selectSum('amount','paid')->where('supplier_id',$supplierId)->where('status','posted');
  if($locationId>0) $p->where('location_id',$locationId);
  $p=$p->get()->getRowArray();
  $supplier=$this->db->table('suppliers')->where('id',$supplierId)->get()->getRowArray();
  return (float)($supplier['opening_balance']??0)+(float)($s['credit']??0)-(float)($p['paid']??0);
 }

}
