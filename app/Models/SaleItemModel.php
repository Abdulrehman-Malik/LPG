<?php

namespace App\Models;

use CodeIgniter\Model;

class SaleItemModel extends Model
{
    protected $table         = 'sale_items';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'sale_id', 'cylinder_type_id', 'line_type', 'cylinder_count',
        'gas_weight_kg', 'rate', 'line_total',
    ];

    public function forSale(int $saleId): array
    {
        return $this->where('sale_id', $saleId)->findAll();
    }

    /**
     * Sale line breakdown joined with cylinder label, for the Sales Search
     * grid's "5x11.8kg @4,450" style rendering.
     */
    public function forSaleWithLabels(int $saleId): array
    {
        return $this->select('sale_items.*, cylinder_types.label AS cylinder_label')
            ->join('cylinder_types', 'cylinder_types.id = sale_items.cylinder_type_id', 'left')
            ->where('sale_id', $saleId)
            ->findAll();
    }
}
