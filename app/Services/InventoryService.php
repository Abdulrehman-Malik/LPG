<?php
namespace App\Services;
use Config\Database;
use RuntimeException;

class InventoryService{
 protected $db;
 protected $cylinders;
 public function __construct(){ $this->db=Database::connect(); $this->cylinders=new CylinderUnitService(); }

 public function stock(int $locationId,string $type,?int $typeId=null,?string $at=null):float{
  $at=$at?:date('Y-m-d H:i:s'); $date=substr($at,0,10);
  $q=$this->db->table('inventory_opening_balances')->selectSum('quantity','q')->where(['location_id'=>$locationId,'inventory_type'=>$type,'cylinder_type_id'=>$typeId])->where('inventory_date <=',$date)->get()->getRowArray();
  $in=$this->db->table('inventory_movements')->selectSum('quantity','q')->where(['location_id'=>$locationId,'inventory_type'=>$type,'cylinder_type_id'=>$typeId,'direction'=>'in'])->where('movement_at <=',$at)->get()->getRowArray();
  $out=$this->db->table('inventory_movements')->selectSum('quantity','q')->where(['location_id'=>$locationId,'inventory_type'=>$type,'cylinder_type_id'=>$typeId,'direction'=>'out'])->where('movement_at <=',$at)->get()->getRowArray();
  return (float)($q['q']??0)+(float)($in['q']??0)-(float)($out['q']??0);
 }

 public function adjust(int $locationId,string $type,?int $typeId,float $qty,string $direction,int $userId,string $notes='',float $actualGasWeight=0):void{
  if($qty<=0||!in_array($direction,['in','out'],true)) throw new RuntimeException('Invalid inventory adjustment.');
  if(!in_array($type,['gas_kg','filled_cylinder','empty_cylinder'],true)) throw new RuntimeException('Invalid inventory type.');

  if($type==='gas_kg'){
   if($typeId!==null) throw new RuntimeException('Gas KG adjustment does not use a cylinder type.');
   if($direction==='out' && $this->stock($locationId,$type,null)<$qty) throw new RuntimeException('Insufficient gas stock.');
   $this->db->table('inventory_movements')->insert([
    'location_id'=>$locationId,'inventory_type'=>'gas_kg','cylinder_type_id'=>null,'quantity'=>$qty,
    'direction'=>$direction,'movement_at'=>date('Y-m-d H:i:s'),'source_type'=>'adjustment','source_id'=>0,
    'created_by'=>$userId,'notes'=>$notes?:'Manual gas stock adjustment'
   ]);
   return;
  }

  if(!$typeId) throw new RuntimeException('Cylinder type is required.');
  if(floor($qty)!==$qty) throw new RuntimeException('Cylinder quantity must be a whole number.');
  $qtyInt=(int)$qty;
  $ct=$this->db->table('cylinder_types')->where('id',$typeId)->get()->getRowArray();
  if(!$ct || !(int)$ct['is_active']) throw new RuntimeException('Invalid or inactive cylinder type.');

  $this->db->transBegin();
  try{
   if($direction==='in'){
    $gasPerUnit=$type==='filled_cylinder'?$actualGasWeight:0;
    if($type==='filled_cylinder'){
     if($gasPerUnit<=0 || $gasPerUnit>(float)$ct['capacity_kg']) throw new RuntimeException('Actual gas weight must be greater than zero and cannot exceed cylinder capacity.');
    }
    $unitIds=$this->cylinders->createUnits($locationId,$typeId,$qtyInt,$type==='filled_cylinder'?'filled':'empty',$gasPerUnit,$userId,'adjustment',0);
    foreach($unitIds as $unitId){
     $this->db->table('inventory_movements')->insert([
      'location_id'=>$locationId,'inventory_type'=>$type,'cylinder_type_id'=>$typeId,'quantity'=>1,'direction'=>'in',
      'movement_at'=>date('Y-m-d H:i:s'),'source_type'=>'adjustment','source_id'=>0,'cylinder_unit_id'=>$unitId,
      'created_by'=>$userId,'notes'=>$notes?:'Manual cylinder stock adjustment'
     ]);
     if($type==='filled_cylinder'){
      $this->db->table('inventory_movements')->insert([
       'location_id'=>$locationId,'inventory_type'=>'gas_kg','cylinder_type_id'=>null,'quantity'=>$gasPerUnit,'direction'=>'in',
       'movement_at'=>date('Y-m-d H:i:s'),'source_type'=>'adjustment','source_id'=>0,'cylinder_unit_id'=>$unitId,
       'created_by'=>$userId,'notes'=>'Gas contained in adjusted filled cylinder'.($notes?' — '.$notes:'')
      ]);
     }
    }
   }else{
    $units=$this->cylinders->available($locationId,$typeId,$type==='filled_cylinder'?'filled':'empty');
    if(count($units)<$qtyInt) throw new RuntimeException('Insufficient '.$type.' stock.');
    foreach(array_slice($units,0,$qtyInt) as $unit){
     $unitId=(int)$unit['id'];
     $this->db->table('inventory_movements')->insert([
      'location_id'=>$locationId,'inventory_type'=>$type,'cylinder_type_id'=>$typeId,'quantity'=>1,'direction'=>'out',
      'movement_at'=>date('Y-m-d H:i:s'),'source_type'=>'adjustment','source_id'=>0,'cylinder_unit_id'=>$unitId,
      'created_by'=>$userId,'notes'=>$notes?:'Manual cylinder stock adjustment'
     ]);
     if($type==='filled_cylinder'){
      $gas=(float)$unit['gas_weight_kg'];
      if($gas>0) $this->db->table('inventory_movements')->insert([
       'location_id'=>$locationId,'inventory_type'=>'gas_kg','cylinder_type_id'=>null,'quantity'=>$gas,'direction'=>'out',
       'movement_at'=>date('Y-m-d H:i:s'),'source_type'=>'adjustment','source_id'=>0,'cylinder_unit_id'=>$unitId,
       'created_by'=>$userId,'notes'=>'Gas removed with adjusted filled cylinder'.($notes?' — '.$notes:'')
      ]);
     }
     $this->cylinders->markSold($unitId);
    }
   }
   if(!$this->db->transStatus()) throw new RuntimeException('Inventory adjustment failed.');
   $this->db->transCommit();
  }catch(\Throwable $e){$this->db->transRollback();throw $e;}
 }
}