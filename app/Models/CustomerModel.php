<?php

namespace App\Models;

use CodeIgniter\Model;

class CustomerModel extends Model
{
    protected $table         = 'customers';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $allowedFields = [
        'name', 'phone', 'vehicle_no', 'address', 'opening_balance', 'is_active',
    ];

    /**
     * Credit Due = opening_balance + SUM(sales.credit_amount) - SUM(customer_payments.amount)
     * Total Purchased (Rs) = SUM(sales.total_amount)
     * Total Gas (Kg)       = SUM(sales.total_kg)
     */
    public function withLedgerTotals(int $customerId): ?array
    {
        $customer = $this->find($customerId);

        if (! $customer) {
            return null;
        }

        $db = $this->db;

        $salesTotals = $db->table('sales')
            ->selectSum('total_amount', 'total_purchased')
            ->selectSum('credit_amount', 'total_credit')
            ->selectSum('total_kg', 'total_kg')
            ->where('customer_id', $customerId)
            ->get()
            ->getRowArray();

        $paymentsTotal = $db->table('customer_payments')
            ->selectSum('amount', 'total_paid')
            ->where('customer_id', $customerId)
            ->get()
            ->getRowArray();

        $totalCredit = (float) ($salesTotals['total_credit'] ?? 0);
        $totalPaid   = (float) ($paymentsTotal['total_paid'] ?? 0);

        $customer['total_purchased'] = (float) ($salesTotals['total_purchased'] ?? 0);
        $customer['total_gas_kg']    = (float) ($salesTotals['total_kg'] ?? 0);
        $customer['credit_due']      = (float) $customer['opening_balance'] + $totalCredit - $totalPaid;

        return $customer;
    }

    public function activeDirectory(): array
    {
        return $this->where('is_active', 1)->orderBy('name', 'ASC')->findAll();
    }
}
