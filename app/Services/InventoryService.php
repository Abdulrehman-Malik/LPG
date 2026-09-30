<?php
namespace App\Services;
use Config\Database;
use RuntimeException;
class InventoryService{
 protected $db;
 public function __construct(){ $this->db=Database::connect(); }
 public function stock(int $locationId,string $type,?int $typeId=null,?string $at=null):float{
  $at=$at?:date('Y-m-d H:i:s'); $date=substr($at,0,10);
  $q=$this->db->table('inventory_opening_balances')->selectSum('quantity','q')->where(['location_id'=>$locationId,'inventory_type'=>$type,'cylinder_type_id'=>$typeId])->where('inventory_date <=',$date)->get()->getRowArray();
  $in=$this->db->table('inventory_movements')->selectSum('quantity','q')->where(['location_id'=>$locationId,'inventory_type'=>$type,'cylinder_type_id'=>$typeId,'direction'=>'in'])->where('movement_at <=',$at)->get()->getRowArray();
  $out=$this->db->table('inventory_movements')->selectSum('quantity','q')->where(['location_id'=>$locationId,'inventory_type'=>$type,'cylinder_type_id'=>$typeId,'direction'=>'out'])->where('movement_at <=',$at)->get()->getRowArray();
  return (float)($q['q']??0)+(float)($in['q']??0)-(float)($out['q']??0);
 }
 public function adjust(int $locationId,string $type,?int $typeId,float $qty,string $direction,int $userId,string $notes=''):void{
  if($qty<=0||!in_array($direction,['in','out'],true)) throw new RuntimeException('Invalid inventory adjustment.');
  if($direction==='out' && $this->stock($locationId,$type,$typeId)<$qty) throw new RuntimeException('Insufficient stock.');
  $this->db->table('inventory_movements')->insert(['location_id'=>$locationId,'inventory_type'=>$type,'cylinder_type_id'=>$typeId,'quantity'=>$qty,'direction'=>$direction,'movement_at'=>date('Y-m-d H:i:s'),'source_type'=>'adjustment','source_id'=>0,'created_by'=>$userId,'notes'=>$notes?:'Manual stock adjustment']);
 }
}