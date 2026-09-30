<?php

namespace App\Models;

use CodeIgniter\Model;

class InventoryOpeningBalanceModel extends Model
{
    protected $table='inventory_opening_balances';
    protected $primaryKey='id';
    protected $returnType='array';
    protected $useTimestamps=false;
    protected $allowedFields=['location_id','inventory_date','inventory_type','cylinder_type_id','quantity','created_by','created_at'];
}