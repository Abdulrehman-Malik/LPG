<?php
namespace App\Models;
use CodeIgniter\Model;
class CylinderTypeModel extends Model
{
    protected $table='cylinder_types'; protected $primaryKey='id'; protected $returnType='array';
    protected $useTimestamps=true; protected $createdField='created_at'; protected $updatedField='updated_at';
    protected $allowedFields=['code','name','capacity_kg','tare_weight_kg','is_active','sort_order'];
    public function activeOrdered(): array { return $this->where('is_active',1)->orderBy('sort_order')->findAll(); }
}