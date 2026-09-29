<?php

namespace App\Models;

use CodeIgniter\Model;

class SaleModel extends Model
{
    protected $table         = 'sales';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $allowedFields = [
        'sale_date', 'customer_id', 'vehicle_no', 'sale_mode', 'total_kg',
        'total_amount', 'cash_received', 'online_received', 'credit_amount',
        'empty_recv_count', 'payment_status', 'notes', 'created_by',
    ];

    /**
     * Sum of total_amount for a given date (defaults to today). Used by the
     * dashboard "Today's Sales" KPI.
     */
    public function totalAmountForDate(?string $date = null): float
    {
        $date = $date ?? date('Y-m-d');

        $row = $this->selectSum('total_amount', 'total')
            ->where('sale_date', $date)
            ->first();

        return (float) ($row['total'] ?? 0);
    }

    /**
     * Breakdown of cash / online / credit collected for a date — feeds the
     * Daily Cash / Sale Report.
     */
    public function paymentBreakdownForDate(?string $date = null): array
    {
        $date = $date ?? date('Y-m-d');

        $row = $this->selectSum('cash_received', 'cash')
            ->selectSum('online_received', 'online')
            ->selectSum('credit_amount', 'credit')
            ->selectSum('total_amount', 'total')
            ->where('sale_date', $date)
            ->first();

        return [
            'cash'   => (float) ($row['cash'] ?? 0),
            'online' => (float) ($row['online'] ?? 0),
            'credit' => (float) ($row['credit'] ?? 0),
            'total'  => (float) ($row['total'] ?? 0),
        ];
    }

    /**
     * Applies the Sales Search module's filter set. Returns a query builder
     * so the controller can paginate/DataTables-ify it.
     */
    public function search(array $filters)
    {
        $builder = $this->builder();

        if (! empty($filters['from_date'])) {
            $builder->where('sale_date >=', $filters['from_date']);
        }
        if (! empty($filters['to_date'])) {
            $builder->where('sale_date <=', $filters['to_date']);
        }
        if (! empty($filters['customer_id'])) {
            $builder->where('customer_id', $filters['customer_id']);
        }
        if (! empty($filters['vehicle_no'])) {
            $builder->like('vehicle_no', $filters['vehicle_no']);
        }
        if (! empty($filters['payment_status']) && $filters['payment_status'] !== 'all') {
            $builder->where('payment_status', $filters['payment_status']);
        }

        return $builder->orderBy('sale_date', 'DESC');
    }
}
