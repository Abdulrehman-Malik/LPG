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

 public function adjust(int $locationId,string $type,?int $typeId,float $qty,string $direction,int $userId,string $notes='',float $actualGasWeight=0,?int $sourceCylinderUnitId=null,string $adjustmentScope='bulk'):void{
  if($qty<=0||!in_array($direction,['in','out'],true)) throw new RuntimeException('Invalid inventory adjustment.');
  if(!in_array($type,['gas_kg','filled_cylinder','empty_cylinder'],true)) throw new RuntimeException('Invalid inventory type.');
  if(!in_array($adjustmentScope,['bulk','specific'],true)) throw new RuntimeException('Invalid adjustment scope.');
  if($adjustmentScope==='specific' && $sourceCylinderUnitId===null) throw new RuntimeException('Select a specific source cylinder for this adjustment.');
  if($adjustmentScope==='bulk' && $sourceCylinderUnitId!==null) throw new RuntimeException('Specific source cylinder can only be used with specific adjustment scope.');

  if($adjustmentScope==='bulk'){
   if($type==='gas_kg'){
    if($typeId!==null) throw new RuntimeException('Bulk Gas KG adjustment does not use a cylinder type.');
    if($direction==='out' && $this->stock($locationId,$type,null)<$qty) throw new RuntimeException('Insufficient gas stock.');
    $this->db->table('inventory_movements')->insert([
     'location_id'=>$locationId,'inventory_type'=>'gas_kg','cylinder_type_id'=>null,'quantity'=>$qty,
     'direction'=>$direction,'movement_at'=>date('Y-m-d H:i:s'),'source_type'=>'adjustment','source_id'=>0,
     'created_by'=>$userId,'notes'=>$notes?:'Manual gas stock adjustment'
    ]);
    AuditService::log('CREATE','inventory_adjustment',0,null,['inventory_type'=>$type,'cylinder_type_id'=>null,'quantity'=>$qty,'direction'=>$direction,'actual_gas_weight_kg'=>$actualGasWeight,'notes'=>$notes,'adjustment_scope'=>'bulk'],$userId,$locationId);
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
    AuditService::log('CREATE','inventory_adjustment',0,null,['inventory_type'=>$type,'cylinder_type_id'=>$typeId,'quantity'=>$qty,'direction'=>$direction,'actual_gas_weight_kg'=>$actualGasWeight,'notes'=>$notes,'adjustment_scope'=>'bulk'],$userId,$locationId);
   }catch(\\Throwable $e){$this->db->transRollback();throw $e;}
   return;
  }

  if($type!=='gas_kg' && floor($qty)!==$qty) throw new RuntimeException('Cylinder quantity must be a whole number.');
  if($type!=='gas_kg' && (int)$qty!==1) throw new RuntimeException('A specific-cylinder adjustment can affect only one cylinder at a time.');

  $this->db->transBegin();
  try{
   $unit=$this->db->query('SELECT cu.*,ct.code cylinder_code,ct.name cylinder_name,ct.capacity_kg FROM cylinder_units cu JOIN cylinder_types ct ON ct.id=cu.cylinder_type_id WHERE cu.id=? AND cu.location_id=? FOR UPDATE',[$sourceCylinderUnitId,$locationId])->getRowArray();
   if(!$unit) throw new RuntimeException('Selected source cylinder was not found in this branch.');
   if($typeId===null || (int)$unit['cylinder_type_id']!==$typeId) throw new RuntimeException('Selected source cylinder does not match the selected cylinder type.');
   if($unit['status']==='custody' || $unit['status']==='sold') throw new RuntimeException('Selected cylinder is not available for stock adjustment.');
   $now=date('Y-m-d H:i:s');
   $note=$notes!==''?$notes:'Specific physical cylinder stock adjustment';

   if($type==='gas_kg'){
    if($direction==='out'){
     if($unit['status']!=='filled') throw new RuntimeException('Gas KG decrease requires a filled source cylinder.');
     $current=(float)$unit['gas_weight_kg'];
     if($qty>$current+0.00001) throw new RuntimeException('Gas quantity cannot exceed the selected cylinder current gas weight ('.number_format($current,3).' KG).');
     $remaining=$current-$qty;
     $this->db->table('inventory_movements')->insert([
      'location_id'=>$locationId,'inventory_type'=>'gas_kg','cylinder_type_id'=>null,'quantity'=>$qty,'direction'=>'out',
      'movement_at'=>$now,'source_type'=>'adjustment','source_id'=>0,'cylinder_unit_id'=>$sourceCylinderUnitId,
      'created_by'=>$userId,'notes'=>$note
     ]);
     if($remaining<=0.00001){
      $this->db->table('inventory_movements')->insert([
       'location_id'=>$locationId,'inventory_type'=>'filled_cylinder','cylinder_type_id'=>$typeId,'quantity'=>1,'direction'=>'out',
       'movement_at'=>$now,'source_type'=>'adjustment','source_id'=>0,'cylinder_unit_id'=>$sourceCylinderUnitId,
       'created_by'=>$userId,'notes'=>'Selected cylinder became empty after gas adjustment: '.$note
      ]);
      $this->db->table('inventory_movements')->insert([
       'location_id'=>$locationId,'inventory_type'=>'empty_cylinder','cylinder_type_id'=>$typeId,'quantity'=>1,'direction'=>'in',
       'movement_at'=>$now,'source_type'=>'adjustment','source_id'=>0,'cylinder_unit_id'=>$sourceCylinderUnitId,
       'created_by'=>$userId,'notes'=>'Selected cylinder moved to empty after gas adjustment: '.$note
      ]);
      $this->db->table('cylinder_units')->where('id',$sourceCylinderUnitId)->update(['status'=>'empty','gas_weight_kg'=>0]);
     }else{
      $this->db->table('cylinder_units')->where('id',$sourceCylinderUnitId)->update(['gas_weight_kg'=>$remaining]);
     }
    }else{
     if(!in_array($unit['status'],['filled','empty'],true)) throw new RuntimeException('Gas KG increase requires an available filled or empty cylinder.');
     $current=(float)$unit['gas_weight_kg'];$capacity=(float)$unit['capacity_kg'];$after=$current+$qty;
     if($after>$capacity+0.00001) throw new RuntimeException('Gas quantity exceeds the selected cylinder capacity. Available capacity: '.number_format(max(0,$capacity-$current),3).' KG.');
     $this->db->table('inventory_movements')->insert([
      'location_id'=>$locationId,'inventory_type'=>'gas_kg','cylinder_type_id'=>null,'quantity'=>$qty,'direction'=>'in',
      'movement_at'=>$now,'source_type'=>'adjustment','source_id'=>0,'cylinder_unit_id'=>$sourceCylinderUnitId,
      'created_by'=>$userId,'notes'=>$note
     ]);
     if($unit['status']==='empty'){
      $this->db->table('inventory_movements')->insert([
       'location_id'=>$locationId,'inventory_type'=>'empty_cylinder','cylinder_type_id'=>$typeId,'quantity'=>1,'direction'=>'out',
       'movement_at'=>$now,'source_type'=>'adjustment','source_id'=>0,'cylinder_unit_id'=>$sourceCylinderUnitId,
       'created_by'=>$userId,'notes'=>'Selected empty cylinder filled by gas adjustment: '.$note
      ]);
      $this->db->table('inventory_movements')->insert([
       'location_id'=>$locationId,'inventory_type'=>'filled_cylinder','cylinder_type_id'=>$typeId,'quantity'=>1,'direction'=>'in',
       'movement_at'=>$now,'source_type'=>'adjustment','source_id'=>0,'cylinder_unit_id'=>$sourceCylinderUnitId,
       'created_by'=>$userId,'notes'=>'Selected cylinder became filled after gas adjustment: '.$note
      ]);
      $this->db->table('cylinder_units')->where('id',$sourceCylinderUnitId)->update(['status'=>'filled','gas_weight_kg'=>$after]);
     }else{
      $this->db->table('cylinder_units')->where('id',$sourceCylinderUnitId)->update(['gas_weight_kg'=>$after]);
     }
    }
   }elseif($type==='filled_cylinder'){
    if($direction==='in'){
     if($unit['status']!=='empty') throw new RuntimeException('Filled-cylinder increase can only use an empty physical cylinder.');
     if($actualGasWeight<=0 || $actualGasWeight>(float)$unit['capacity_kg']) throw new RuntimeException('Actual gas weight must be greater than zero and cannot exceed cylinder capacity.');
     $this->db->table('inventory_movements')->insert([
      'location_id'=>$locationId,'inventory_type'=>'empty_cylinder','cylinder_type_id'=>$typeId,'quantity'=>1,'direction'=>'out',
      'movement_at'=>$now,'source_type'=>'adjustment','source_id'=>0,'cylinder_unit_id'=>$sourceCylinderUnitId,
      'created_by'=>$userId,'notes'=>'Selected empty cylinder refilled: '.$note
     ]);
     $this->db->table('inventory_movements')->insert([
      'location_id'=>$locationId,'inventory_type'=>'filled_cylinder','cylinder_type_id'=>$typeId,'quantity'=>1,'direction'=>'in',
      'movement_at'=>$now,'source_type'=>'adjustment','source_id'=>0,'cylinder_unit_id'=>$sourceCylinderUnitId,
      'created_by'=>$userId,'notes'=>'Selected cylinder became filled after refill adjustment: '.$note
     ]);
     $this->db->table('inventory_movements')->insert([
      'location_id'=>$locationId,'inventory_type'=>'gas_kg','cylinder_type_id'=>null,'quantity'=>$actualGasWeight,'direction'=>'in',
      'movement_at'=>$now,'source_type'=>'adjustment','source_id'=>0,'cylinder_unit_id'=>$sourceCylinderUnitId,
      'created_by'=>$userId,'notes'=>'Gas added to selected filled cylinder: '.$note
     ]);
     $this->db->table('cylinder_units')->where('id',$sourceCylinderUnitId)->update(['status'=>'filled','gas_weight_kg'=>$actualGasWeight]);
    }else{
     if($unit['status']!=='filled') throw new RuntimeException('Filled-cylinder decrease requires a filled source cylinder.');
     $gas=(float)$unit['gas_weight_kg'];
     $this->db->table('inventory_movements')->insert([
      'location_id'=>$locationId,'inventory_type'=>'filled_cylinder','cylinder_type_id'=>$typeId,'quantity'=>1,'direction'=>'out',
      'movement_at'=>$now,'source_type'=>'adjustment','source_id'=>0,'cylinder_unit_id'=>$sourceCylinderUnitId,
      'created_by'=>$userId,'notes'=>$note
     ]);
     if($gas>0) $this->db->table('inventory_movements')->insert([
      'location_id'=>$locationId,'inventory_type'=>'gas_kg','cylinder_type_id'=>null,'quantity'=>$gas,'direction'=>'out',
      'movement_at'=>$now,'source_type'=>'adjustment','source_id'=>0,'cylinder_unit_id'=>$sourceCylinderUnitId,
      'created_by'=>$userId,'notes'=>'Gas removed with selected filled cylinder: '.$note
     ]);
     $this->cylinders->markSold($sourceCylinderUnitId);
    }
   }elseif($type==='empty_cylinder'){
    if($direction==='in') throw new RuntimeException('A specific empty-cylinder increase cannot use an existing source cylinder. Use Bulk / Cylinder Type adjustment to add new empty stock.');
    if($unit['status']!=='empty') throw new RuntimeException('Empty-cylinder decrease requires an empty source cylinder.');
    $this->db->table('inventory_movements')->insert([
     'location_id'=>$locationId,'inventory_type'=>'empty_cylinder','cylinder_type_id'=>$typeId,'quantity'=>1,'direction'=>'out',
     'movement_at'=>$now,'source_type'=>'adjustment','source_id'=>0,'cylinder_unit_id'=>$sourceCylinderUnitId,
     'created_by'=>$userId,'notes'=>$note
    ]);
    $this->cylinders->markSold($sourceCylinderUnitId);
   }

   if(!$this->db->transStatus()) throw new RuntimeException('Specific inventory adjustment failed.');
   $this->db->transCommit();
   AuditService::log('CREATE','inventory_adjustment',0,null,['inventory_type'=>$type,'cylinder_type_id'=>$typeId,'quantity'=>$qty,'direction'=>$direction,'actual_gas_weight_kg'=>$actualGasWeight,'source_cylinder_unit_id'=>$sourceCylinderUnitId,'adjustment_scope'=>'specific','notes'=>$notes],$userId,$locationId);
  }catch(\\Throwable $e){$this->db->transRollback();throw $e;}
 }