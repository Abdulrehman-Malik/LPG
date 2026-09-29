<?php

namespace App\Models;

use CodeIgniter\Model;

class GasRateModel extends Model
{
    protected $table         = 'gas_rates';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'cylinder_type_id', 'rate_per_cylinder', 'rate_per_kg', 'effective_from',
    ];

    /**
     * Latest per-cylinder rate effective on or before $asOf (defaults to today).
     */
    public function currentCylinderRate(int $cylinderTypeId, ?string $asOf = null): ?float
    {
        $asOf = $asOf ?? date('Y-m-d');

        $row = $this->where('cylinder_type_id', $cylinderTypeId)
            ->where('effective_from <=', $asOf)
            ->orderBy('effective_from', 'DESC')
            ->first();

        return $row ? (float) $row['rate_per_cylinder'] : null;
    }

    /**
     * Latest generic per-kg rate (cylinder_type_id IS NULL) effective on/before $asOf.
     */
    public function currentKgRate(?string $asOf = null): ?float
    {
        $asOf = $asOf ?? date('Y-m-d');

        $row = $this->where('cylinder_type_id', null)
            ->where('effective_from <=', $asOf)
            ->orderBy('effective_from', 'DESC')
            ->first();

        return $row ? (float) $row['rate_per_kg'] : null;
    }
}
