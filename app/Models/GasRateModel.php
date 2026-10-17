<?php
namespace App\Models;
class GasRateModel extends RateCardModel
{
    public function currentCylinderRate(int $id, ?string $asOf=null, ?int $locationId=null): ?float
    {
        $asOf=$asOf??date('Y-m-d H:i:s');
        $builder=$this->where('rate_type','cylinder_package')
            ->where('cylinder_type_id',$id)
            ->where('customer_id',null)
            ->where('effective_from <=',$asOf)
            ->groupStart()->where('effective_to',null)->orWhere('effective_to >=',$asOf)->groupEnd();
        if($locationId!==null) $builder->groupStart()->where('location_id',$locationId)->orWhere('location_id',null)->groupEnd();
        $row=$builder->orderBy('effective_from','DESC')->first();
        return $row?(float)$row['rate_value']:null;
    }

    public function currentKgRate(?string $asOf=null): ?float
    {
        $row=$this->where('rate_type','gas_per_kg')->where('cylinder_type_id',null)->where('effective_from <=',$asOf??date('Y-m-d H:i:s'))->orderBy('effective_from','DESC')->first();
        return $row?(float)$row['rate_value']:null;
    }
 
    public function currentFilledCylinderRate(int $typeId, int $locationId, ?int $customerId=null, ?string $asOf=null): ?array
    {
        $asOf=$asOf??date('Y-m-d H:i:s');
        $findRate=function(?int $cid) use ($typeId,$locationId,$asOf): ?array {
            $sql="SELECT id,rate_value FROM rate_cards WHERE location_id=? AND rate_type='filled_cylinder_price' AND cylinder_type_id=? AND customer_id <=> ? AND effective_from<=? AND (effective_to IS NULL OR effective_to>=?) ORDER BY effective_from DESC,id DESC LIMIT 1";
            $row=$this->db->query($sql,[$locationId,$typeId,$cid,$asOf,$asOf])->getRowArray();
            return $row?:null;
        };
        if($customerId){
            $row=$findRate($customerId);
            if($row) return ['rate'=>(float)$row['rate_value'],'customer_specific'=>true,'rate_id'=>(int)$row['id']];
        }
        $row=$findRate(null);
        return $row?['rate'=>(float)$row['rate_value'],'customer_specific'=>false,'rate_id'=>(int)$row['id']]:null;
    }

}