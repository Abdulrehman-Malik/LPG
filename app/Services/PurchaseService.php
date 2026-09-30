<?php
namespace App\Services;
use Config\Database;
use RuntimeException;
class PurchaseService{
 protected $db; protected $inv; protected $cash;
 public function __construct(){ $this->db=Database::connect(); $this->inv=new InventoryService(); $this->cash=new CashService(); }
 public function post(array $p,int $userId,int $locationId):array{
  $supplierId=(int)($p['supplier_id']??0); $lines=$p['lines']??[]; $payments=$p['payments']??[];
  if(!$supplierId||!$lines||!$payments) throw new RuntimeException('Supplier, purchase lines and payments are required.');
  $supplier=$this->db->table('suppliers')->where('id',$supplierId)->get()->getRowArray();
  if(!$supplier||(int)$supplier['is_active']!==1) throw new RuntimeException('Supplier is invalid or inactive.');
  $subtotal=0;$prepared=[];$inventory=[];$cash=0;$credit=0;
  foreach($lines as $i=>$l){$type=(string)($l['line_type']??'');$qty=(float)($l['quantity']??0);$rate=(float)($l['unit_rate']??0);$ct=isset($l['cylinder_type_id'])&&$l['cylinder_type_id']!==''?(int)$l['cylinder_type_id']:null;
   if($qty<=0||$rate<0) throw new RuntimeException('Invalid purchase line '.($i+1).'.');
   if(!in_array($type,['gas_kg','filled_cylinder','empty_cylinder'],true)) throw new RuntimeException('Invalid purchase type.');
   if($type!=='gas_kg'&&!$ct) throw new RuntimeException('Cylinder type required.');
   if($type==='gas_kg')$ct=null; $total=$qty*$rate;$subtotal+=$total;
   $prepared[]=['line_no'=>$i+1,'line_type'=>$type,'cylinder_type_id'=>$ct,'quantity'=>$qty,'unit_rate'=>$rate,'line_total'=>$total];
   $inventory[]=['inventory_type'=>$type,'cylinder_type_id'=>$ct,'quantity'=>$qty];
  }
  $discount=max(0,(float)($p['discount_amount']??0));if($discount>$subtotal)throw new RuntimeException('Discount exceeds subtotal.');
  $total=$subtotal-$discount;$paid=0;
  foreach($payments as $pay){$mode=(string)($pay['payment_mode']??'');$amount=(float)($pay['amount']??0);if(!in_array($mode,['cash','cheque','online','credit'],true)||$amount<=0)throw new RuntimeException('Invalid purchase payment.');$paid+=$amount;if($mode==='cash')$cash+=$amount;if($mode==='credit')$credit+=$amount;}
  if(abs($paid-$total)>0.01)throw new RuntimeException('Payment total must equal purchase total.');
  if($credit>0 && (float)$supplier['opening_balance']+$credit>(float)$supplier['credit_limit'])throw new RuntimeException('Supplier credit limit exceeded.');
  $this->db->transBegin();
  try{
   $supplier=$this->db->query('SELECT * FROM suppliers WHERE id=? FOR UPDATE',[$supplierId])->getRowArray();
   if($credit>0 && (float)$supplier['opening_balance']+$credit>(float)$supplier['credit_limit'])throw new RuntimeException('Supplier credit limit exceeded.');
   foreach($inventory as $m){if($this->inv->stock($locationId,$m['inventory_type'],$m['cylinder_type_id'])<0)throw new RuntimeException('Invalid inventory state.');}
   $no='P'.date('YmdHis').'-'.random_int(100,999);
   $this->db->table('purchases')->insert(['purchase_no'=>$no,'location_id'=>$locationId,'supplier_id'=>$supplierId,'status'=>'posted','transaction_at'=>date('Y-m-d H:i:s'),'subtotal'=>$subtotal,'discount_amount'=>$discount,'total_amount'=>$total,'credit_amount'=>$credit,'notes'=>trim((string)($p['notes']??''))?:null,'created_by'=>$userId]);$id=(int)$this->db->insertID();
   foreach($prepared as $row){$row['purchase_id']=$id;$this->db->table('purchase_items')->insert($row);}
   foreach($inventory as $m)$this->db->table('inventory_movements')->insert(['location_id'=>$locationId,'inventory_type'=>$m['inventory_type'],'cylinder_type_id'=>$m['cylinder_type_id'],'quantity'=>$m['quantity'],'direction'=>'in','movement_at'=>date('Y-m-d H:i:s'),'source_type'=>'purchase','source_id'=>$id,'created_by'=>$userId]);
   foreach($payments as $pay)$this->db->table('purchase_payments')->insert(['purchase_id'=>$id,'payment_mode'=>$pay['payment_mode'],'amount'=>(float)$pay['amount'],'reference_no'=>trim((string)($pay['reference_no']??''))?:null,'payment_at'=>date('Y-m-d H:i:s'),'paid_by'=>$userId]);
   if($cash>0){$session=$this->cash->openSessionForLocation($locationId);if(!$session)throw new RuntimeException('Open the counter cash session before posting a cash purchase.');$this->cash->postGeneric((int)$session['id'],'purchase_cash','out',$cash,'purchase',$id,$userId,'Purchase cash');}
   if(!$this->db->transStatus())throw new RuntimeException('Purchase posting failed.');
   $this->db->transCommit();AuditService::log('CREATE','purchase',$id,null,['purchase_no'=>$no,'total'=>$total],$userId,$locationId);return ['id'=>$id,'purchase_no'=>$no,'total'=>$total];
  }catch(\Throwable $e){$this->db->transRollback();throw $e;}
 }
}