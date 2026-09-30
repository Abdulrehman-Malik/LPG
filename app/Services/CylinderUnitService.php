<?php
namespace App\Services;
use Config\Database;
use RuntimeException;

class CylinderUnitService
{
    protected $db;
    public function __construct(){ $this->db=Database::connect(); }

    public function createUnits(int $locationId,int $typeId,int $quantity,string $status,float $gasWeight,int $userId,string $sourceType,int $sourceId): array
    {
        if($quantity<1 || floor($quantity)!==$quantity) throw new RuntimeException('Cylinder quantity must be a whole number.');
        $ct=$this->db->table('cylinder_types')->where('id',$typeId)->get()->getRowArray();
        if(!$ct) throw new RuntimeException('Cylinder type not found.');
        if($gasWeight<0 || $gasWeight>(float)$ct['capacity_kg']) throw new RuntimeException('Actual gas weight must be between 0 and the cylinder capacity.');
        if($status==='filled' && $gasWeight<=0) throw new RuntimeException('Filled cylinders must have actual gas weight greater than zero.');
        if($status==='empty') $gasWeight=0;
        $ids=[];
        for($i=0;$i<(int)$quantity;$i++){
            $code='CYL-'.date('YmdHis').'-'.random_int(10000,99999);
            $this->db->table('cylinder_units')->insert([
                'location_id'=>$locationId,'cylinder_type_id'=>$typeId,'unit_code'=>$code,
                'status'=>$status,'gas_weight_kg'=>$gasWeight,'source_type'=>$sourceType,
                'source_id'=>$sourceId,'created_by'=>$userId
            ]);
            $ids[]=(int)$this->db->insertID();
        }
        return $ids;
    }

    public function available(int $locationId,int $typeId,string $status='filled'): array
    {
        return $this->db->query(
            "SELECT * FROM cylinder_units WHERE location_id=? AND cylinder_type_id=? AND status=? ORDER BY id FOR UPDATE",
            [$locationId,$typeId,$status]
        )->getResultArray();
    }

    public function availableForDisplay(int $locationId,int $typeId,string $status='filled'): array
    {
        return $this->db->table('cylinder_units')->where(['location_id'=>$locationId,'cylinder_type_id'=>$typeId,'status'=>$status])->orderBy('id')->get()->getResultArray();
    }

    public function markSold(int $unitId): void
    {
        $u=$this->db->query("SELECT * FROM cylinder_units WHERE id=? FOR UPDATE",[$unitId])->getRowArray();
        if(!$u || $u['status']==='sold') throw new RuntimeException('Cylinder is no longer available.');
        $this->db->table('cylinder_units')->where('id',$unitId)->update(['status'=>'sold','gas_weight_kg'=>0]);
    }

    public function restoreFilled(int $unitId,float $gasWeight): void
    {
        $u=$this->db->query("SELECT * FROM cylinder_units WHERE id=? FOR UPDATE",[$unitId])->getRowArray();
        if(!$u) throw new RuntimeException('Cylinder unit not found.');
        $ct=$this->db->table('cylinder_types')->where('id',$u['cylinder_type_id'])->get()->getRowArray();
        if($gasWeight<=0 || $gasWeight>(float)$ct['capacity_kg']) throw new RuntimeException('Invalid restored cylinder gas weight.');
        $this->db->table('cylinder_units')->where('id',$unitId)->update(['status'=>'filled','gas_weight_kg'=>$gasWeight]);
    }

    public function restoreEmpty(int $unitId): void
    {
        $u=$this->db->query("SELECT * FROM cylinder_units WHERE id=? FOR UPDATE",[$unitId])->getRowArray();
        if(!$u) throw new RuntimeException('Cylinder unit not found.');
        $this->db->table('cylinder_units')->where('id',$unitId)->update(['status'=>'empty','gas_weight_kg'=>0]);
    }
}