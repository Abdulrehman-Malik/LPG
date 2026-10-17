<?php
namespace App\Models;
class GasRateModel extends RateCardModel
{
    public function currentCylinderRate(int $id, ?string $asOf=null, ?int $locationId=null): ?float
    {
        $builder=$this->where('rate_type','cylinder_package')->where('cylinder_type_id',$id)->where('customer_id',null)->where('effective_from <=',$asOf??date('Y-m-d H:i:s'));
        if($locationId!==null) $builder->groupStart()->where('location_id',$locationId)->orWhere('location_id',null)->groupEnd()->orderBy('location_id','DESC');
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
        $base=$this->where('rate_type','filled_cylinder_price')
            ->where('cylinder_type_id',$typeId)
            ->where('location_id',$locationId)
            ->where('effective_from <=',$asOf)
            ->groupStart()->where('effective_to',null)->orWhere('effective_to >=',$asOf)->groupEnd();
        if($customerId){
            $customerRow=(clone $base)->where('customer_id',$customerId)->orderBy('effective_from','DESC')->first();
            if($customerRow) return ['rate'=>(float)$customerRow['rate_value'],'customer_specific'=>true,'rate_id'=>(int)$customerRow['id']];
        }
        $shopRow=(clone $base)->where('customer_id',null)->orderBy('effective_from','DESC')->first();
        return $shopRow?['rate'=>(float)$shopRow['rate_value'],'customer_specific'=>false,'rate_id'=>(int)$shopRow['id']]:null;
    }

}