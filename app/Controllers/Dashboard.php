<?php

namespace App\Controllers;

use App\Services\PermissionService;
use CodeIgniter\Controller;
use Config\Database;

class Dashboard extends Controller
{
    private function guard()
    {
        return PermissionService::allows('DASHBOARD_VIEW')
            ? null
            : $this->response->setStatusCode(403)->setBody('Forbidden');
    }

    private function dateRange(): array
    {
        $today = date('Y-m-d');
        $from = trim((string) $this->request->getGet('from'));
        $to = trim((string) $this->request->getGet('to'));

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
            $from = $today;
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
            $to = $from;
        }

        $fromTs = strtotime($from);
        $toTs = strtotime($to);

        if ($fromTs === false) {
            $from = $today;
            $fromTs = strtotime($from);
        }
        if ($toTs === false || $toTs < $fromTs) {
            $to = $from;
        }

        return [
            'from' => $from,
            'to' => $to,
            'toExclusive' => date('Y-m-d', strtotime($to . ' +1 day')),
            'label' => $from === $to
                ? date('d M Y', strtotime($from))
                : date('d M Y', strtotime($from)) . ' — ' . date('d M Y', strtotime($to)),
            'isToday' => $from === $today && $to === $today,
        ];
    }

    private function periodQuery($builder, string $column, array $range): void
    {
        $builder
            ->where($column . ' >=', $range['from'] . ' 00:00:00')
            ->where($column . ' <', $range['toExclusive'] . ' 00:00:00');
    }

    public function index()
    {
        if ($r = $this->guard()) {
            return $r;
        }

        $db = Database::connect();
        $locationId = (int) (session()->get('location_id') ?? 0);
        $range = $this->dateRange();

        $salesQ = $db->table('sales')
            ->select('COUNT(*) count, COALESCE(SUM(total_amount),0) total, COALESCE(SUM(credit_amount),0) credit')
            ->where([
                'location_id' => $locationId,
                'status' => 'posted',
            ])
            ->whereNotIn('transaction_type', ['security_deposit', 'cylinder_return']);
        $this->periodQuery($salesQ, 'transaction_at', $range);
        $sales = $salesQ->get()->getRowArray() ?: [];

        $cashSaleQ = $db->table('sale_payments sp')
            ->select('COALESCE(SUM(sp.amount),0) total')
            ->join('sales s', 's.id=sp.sale_id')
            ->where([
                's.location_id' => $locationId,
                's.status' => 'posted',
                'sp.payment_mode' => 'cash',
            ])
            ->whereNotIn('s.transaction_type', ['security_deposit', 'cylinder_return']);
        $this->periodQuery($cashSaleQ, 'sp.payment_at', $range);
        $cashSale = (float) (($cashSaleQ->get()->getRowArray()['total'] ?? 0));

        $salePaymentQ = $db->table('sale_payments sp')
            ->select('COALESCE(SUM(sp.amount),0) total')
            ->join('sales s', 's.id=sp.sale_id')
            ->where([
                's.location_id' => $locationId,
                's.status' => 'posted',
            ])
            ->whereNotIn('s.transaction_type', ['security_deposit', 'cylinder_return'])
            ->where('sp.payment_mode !=', 'credit');
        $this->periodQuery($salePaymentQ, 'sp.payment_at', $range);
        $salePayments = (float) (($salePaymentQ->get()->getRowArray()['total'] ?? 0));

        $receiptQ = $db->table('customer_receipts')
            ->select('COALESCE(SUM(amount),0) total')
            ->where([
                'location_id' => $locationId,
                'status' => 'posted',
            ]);
        $this->periodQuery($receiptQ, 'receipt_at', $range);
        $customerReceipts = (float) (($receiptQ->get()->getRowArray()['total'] ?? 0));

        $payments = $salePayments + $customerReceipts;

        $purchaseQ = $db->table('purchases')
            ->select('COUNT(*) count, COALESCE(SUM(total_amount),0) total')
            ->where([
                'location_id' => $locationId,
                'status' => 'posted',
            ]);
        $this->periodQuery($purchaseQ, 'transaction_at', $range);
        $purchases = $purchaseQ->get()->getRowArray() ?: [];

        $purchaseBreakdownQ = $db->table('purchase_items pi')
            ->select('pi.line_type, COALESCE(SUM(pi.quantity),0) quantity')
            ->join('purchases p', 'p.id=pi.purchase_id')
            ->where([
                'p.location_id' => $locationId,
                'p.status' => 'posted',
            ])
            ->groupBy('pi.line_type');
        $this->periodQuery($purchaseBreakdownQ, 'p.transaction_at', $range);
        $purchaseBreakdown = [
            'gas_kg' => 0.0,
            'filled_cylinder' => 0.0,
            'empty_cylinder' => 0.0,
        ];
        foreach ($purchaseBreakdownQ->get()->getResultArray() as $row) {
            $key = (string) $row['line_type'];
            if (isset($purchaseBreakdown[$key])) {
                $purchaseBreakdown[$key] = (float) $row['quantity'];
            }
        }

        $filled = $db->table('cylinder_units cu')
            ->select('cu.id,cu.unit_code,cu.gas_weight_kg,cu.updated_at,ct.code type_code,ct.name type_name,ct.capacity_kg')
            ->join('cylinder_types ct', 'ct.id=cu.cylinder_type_id')
            ->where([
                'cu.location_id' => $locationId,
                'cu.status' => 'filled',
            ])
            ->orderBy('ct.sort_order')
            ->orderBy('cu.unit_code')
            ->get()->getResultArray();

        $empty = $db->table('cylinder_units cu')
            ->select('cu.id,cu.unit_code,cu.gas_weight_kg,cu.updated_at,ct.code type_code,ct.name type_name,ct.capacity_kg')
            ->join('cylinder_types ct', 'ct.id=cu.cylinder_type_id')
            ->where([
                'cu.location_id' => $locationId,
                'cu.status' => 'empty',
            ])
            ->orderBy('ct.sort_order')
            ->orderBy('cu.unit_code')
            ->get()->getResultArray();

        $issued = $db->table('cylinder_units cu')
            ->select('cu.id,cu.unit_code,cu.gas_weight_kg,cu.updated_at,ct.code type_code,ct.name type_name,ct.capacity_kg,c.id customer_id,c.code customer_code,c.name customer_name,c.phone customer_phone,cc.deposit_amount,cc.issued_at')
            ->join('cylinder_types ct', 'ct.id=cu.cylinder_type_id')
            ->join('cylinder_custody cc', "cc.cylinder_unit_id=cu.id AND cc.status='issued'", 'inner')
            ->join('customers c', 'c.id=cc.customer_id', 'inner')
            ->where([
                'cu.location_id' => $locationId,
                'cu.status' => 'custody',
            ])
            ->orderBy('ct.sort_order')
            ->orderBy('cu.unit_code')
            ->get()->getResultArray();

        $stock = [
            'filled' => [
                'title' => 'Filled Cylinders',
                'count' => count($filled),
                'gas_kg' => array_sum(array_map(static fn(array $row): float => (float) $row['gas_weight_kg'], $filled)),
                'rows' => $filled,
            ],
            'empty' => [
                'title' => 'Empty Cylinders',
                'count' => count($empty),
                'gas_kg' => 0.0,
                'rows' => $empty,
            ],
            'issued' => [
                'title' => 'Issued Temporarily Against Deposit',
                'count' => count($issued),
                'deposit' => array_sum(array_map(static fn(array $row): float => (float) $row['deposit_amount'], $issued)),
                'rows' => $issued,
            ],
        ];

        return view('dashboard/index', [
            'title' => 'Dashboard',
            'range' => $range,
            'summary' => [
                'sales' => (float) ($sales['total'] ?? 0),
                'sale_count' => (int) ($sales['count'] ?? 0),
                'cash_sale' => $cashSale,
                'credit_sale' => (float) ($sales['credit'] ?? 0),
                'payments' => $payments,
                'sale_payments' => $salePayments,
                'customer_receipts' => $customerReceipts,
                'stock_purchased' => (float) ($purchases['total'] ?? 0),
                'purchase_count' => (int) ($purchases['count'] ?? 0),
                'purchase_gas_kg' => $purchaseBreakdown['gas_kg'],
                'purchase_filled' => $purchaseBreakdown['filled_cylinder'],
                'purchase_empty' => $purchaseBreakdown['empty_cylinder'],
            ],
            'stock' => $stock,
        ]);
    }
}
