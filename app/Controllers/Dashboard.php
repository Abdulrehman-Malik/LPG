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

        $toExclusive = date('Y-m-d', strtotime($to . ' +1 day'));

        return [
            'from' => $from,
            'to' => $to,
            'toExclusive' => $toExclusive,
            'label' => $from === $to ? date('d M Y', strtotime($from)) : date('d M Y', strtotime($from)) . ' — ' . date('d M Y', strtotime($to)),
            'isToday' => $from === $today && $to === $today,
        ];
    }

    private function periodQuery($builder, string $column, array $range): void
    {
        $builder->where($column . ' >=', $range['from'] . ' 00:00:00')
            ->where($column . ' <', $range['toExclusive'] . ' 00:00:00');
    }

    private function sumByPaymentMode(array $rows): array
    {
        $out = ['cash' => 0.0, 'cheque' => 0.0, 'online' => 0.0, 'credit' => 0.0];
        foreach ($rows as $row) {
            $mode = (string) ($row['payment_mode'] ?? '');
            if (isset($out[$mode])) {
                $out[$mode] += (float) ($row['amount'] ?? 0);
            }
        }
        return $out;
    }

    public function index()
    {
        if ($r = $this->guard()) {
            return $r;
        }

        $db = Database::connect();
        $locationId = (int) (session()->get('location_id') ?? 0);
        $range = $this->dateRange();

        $salesRow = $db->table('sales')
            ->select('COUNT(*) transaction_count, COALESCE(SUM(total_amount),0) total, COALESCE(SUM(total_kg),0) total_kg, COALESCE(SUM(discount_amount),0) discount, COALESCE(SUM(credit_amount),0) credit')
            ->where(['location_id' => $locationId, 'status' => 'posted'])
            ->whereNotIn('transaction_type', ['security_deposit', 'cylinder_return']);
        $this->periodQuery($salesRow, 'transaction_at', $range);
        $sales = $salesRow->get()->getRowArray() ?: [];

        $transactionRow = $db->table('sales')
            ->select('COUNT(*) transaction_count')
            ->where(['location_id' => $locationId, 'status' => 'posted']);
        $this->periodQuery($transactionRow, 'transaction_at', $range);
        $allTransactions = (int) (($transactionRow->get()->getRowArray()['transaction_count'] ?? 0));

        $salesByTypeQ = $db->table('sales')
            ->select('transaction_type, COUNT(*) count, COALESCE(SUM(total_amount),0) amount')
            ->where(['location_id' => $locationId, 'status' => 'posted'])
            ->whereNotIn('transaction_type', ['security_deposit', 'cylinder_return'])
            ->groupBy('transaction_type')
            ->orderBy('amount', 'DESC');
        $this->periodQuery($salesByTypeQ, 'transaction_at', $range);
        $salesByType = $salesByTypeQ->get()->getResultArray();

        $salePaymentsQ = $db->table('sale_payments sp')
            ->select('sp.payment_mode, COALESCE(SUM(sp.amount),0) amount')
            ->join('sales s', 's.id=sp.sale_id')
            ->where(['s.location_id' => $locationId, 's.status' => 'posted'])
            ->whereNotIn('s.transaction_type', ['security_deposit', 'cylinder_return'])
            ->groupBy('sp.payment_mode');
        $this->periodQuery($salePaymentsQ, 'sp.payment_at', $range);
        $salePayments = $this->sumByPaymentMode($salePaymentsQ->get()->getResultArray());

        $receiptsQ = $db->table('customer_receipts')
            ->select('COUNT(*) count, COALESCE(SUM(amount),0) amount');
        $receiptsQ->where(['location_id' => $locationId, 'status' => 'posted']);
        $this->periodQuery($receiptsQ, 'receipt_at', $range);
        $customerReceipts = $receiptsQ->get()->getRowArray() ?: [];

        $receiptModesQ = $db->table('customer_receipts')
            ->select('payment_mode, COALESCE(SUM(amount),0) amount')
            ->where(['location_id' => $locationId, 'status' => 'posted'])
            ->groupBy('payment_mode');
        $this->periodQuery($receiptModesQ, 'receipt_at', $range);
        $receiptPayments = $this->sumByPaymentMode($receiptModesQ->get()->getResultArray());

        $purchasesQ = $db->table('purchases')
            ->select('COUNT(*) transaction_count, COALESCE(SUM(total_amount),0) total, COALESCE(SUM(credit_amount),0) credit')
            ->where(['location_id' => $locationId, 'status' => 'posted']);
        $this->periodQuery($purchasesQ, 'transaction_at', $range);
        $purchases = $purchasesQ->get()->getRowArray() ?: [];

        $supplierPaymentsQ = $db->table('supplier_payments')
            ->select('COUNT(*) count, COALESCE(SUM(amount),0) amount')
            ->where(['location_id' => $locationId, 'status' => 'posted']);
        $this->periodQuery($supplierPaymentsQ, 'payment_at', $range);
        $supplierPayments = $supplierPaymentsQ->get()->getRowArray() ?: [];

        $expensesQ = $db->table('expenses')
            ->select('COUNT(*) count, COALESCE(SUM(amount),0) amount')
            ->where('location_id', $locationId);
        $this->periodQuery($expensesQ, 'expense_at', $range);
        $expenses = $expensesQ->get()->getRowArray() ?: [];

        $expenseModesQ = $db->table('expenses')
            ->select('payment_mode, COALESCE(SUM(amount),0) amount')
            ->where('location_id', $locationId)
            ->groupBy('payment_mode');
        $this->periodQuery($expenseModesQ, 'expense_at', $range);
        $expensePayments = $this->sumByPaymentMode($expenseModesQ->get()->getResultArray());

        $depositPeriod = $db->table('customer_security_deposits')
            ->select("COALESCE(SUM(CASE WHEN entry_type='hold' THEN amount ELSE 0 END),0) held, COALESCE(SUM(CASE WHEN entry_type='refund' THEN amount ELSE 0 END),0) refunded, COUNT(*) entries")
            ->where('location_id', $locationId);
        $this->periodQuery($depositPeriod, 'transaction_at', $range);
        $depositPeriod = $depositPeriod->get()->getRowArray() ?: [];

        $depositBalance = $db->table('customer_security_deposits')
            ->select("COALESCE(SUM(CASE WHEN entry_type='hold' THEN amount ELSE -amount END),0) balance")
            ->where('location_id', $locationId)
            ->get()->getRowArray() ?: [];

        $cashRangeQ = $db->table('cash_transactions ct')
            ->select("COALESCE(SUM(CASE WHEN ct.direction='in' THEN ct.amount ELSE 0 END),0) cash_in, COALESCE(SUM(CASE WHEN ct.direction='out' THEN ct.amount ELSE 0 END),0) cash_out, COUNT(*) entries")
            ->join('cash_sessions cs', 'cs.id=ct.cash_session_id')
            ->join('cash_registers cr', 'cr.id=cs.register_id')
            ->where('cr.location_id', $locationId);
        $this->periodQuery($cashRangeQ, 'ct.transaction_at', $range);
        $cashRange = $cashRangeQ->get()->getRowArray() ?: [];

        $openSession = $db->table('cash_sessions cs')
            ->select('cs.*, cr.code register_code, cr.name register_name')
            ->join('cash_registers cr', 'cr.id=cs.register_id')
            ->where('cr.location_id', $locationId)
            ->where('cs.status', 'open')
            ->orderBy('cs.id', 'DESC')
            ->get()->getRowArray();

        $openSessionSummary = null;
        if ($openSession) {
            $openCash = $db->table('cash_transactions')
                ->select("COALESCE(SUM(CASE WHEN direction='in' THEN amount ELSE 0 END),0) cash_in, COALESCE(SUM(CASE WHEN direction='out' THEN amount ELSE 0 END),0) cash_out")
                ->where('cash_session_id', (int) $openSession['id'])
                ->get()->getRowArray() ?: [];
            $openSessionSummary = [
                'cash_in' => (float) ($openCash['cash_in'] ?? 0),
                'cash_out' => (float) ($openCash['cash_out'] ?? 0),
                'expected' => (float) ($openSession['opening_cash'] ?? 0) + (float) ($openCash['cash_in'] ?? 0) - (float) ($openCash['cash_out'] ?? 0),
            ];
        }

        $inventoryRows = $db->table('cylinder_types ct')
            ->select("ct.id,ct.code,ct.name,ct.capacity_kg,
                COUNT(CASE WHEN cu.status='filled' THEN 1 END) filled_cylinders,
                COUNT(CASE WHEN cu.status='empty' THEN 1 END) empty_cylinders,
                COUNT(CASE WHEN cu.status='custody' THEN 1 END) custody_cylinders,
                COALESCE(SUM(CASE WHEN cu.status='filled' THEN cu.gas_weight_kg ELSE 0 END),0) gas_kg")
            ->join('cylinder_units cu', 'cu.cylinder_type_id=ct.id AND cu.location_id=' . $locationId, 'left')
            ->where('ct.is_active', 1)
            ->groupBy('ct.id,ct.code,ct.name,ct.capacity_kg,ct.sort_order')
            ->orderBy('ct.sort_order')
            ->get()->getResultArray();

        $liveInventory = [
            'filled' => 0,
            'empty' => 0,
            'custody' => 0,
            'gas_kg' => 0.0,
            'partial' => 0,
        ];
        foreach ($inventoryRows as &$row) {
            $row['filled_cylinders'] = (int) $row['filled_cylinders'];
            $row['empty_cylinders'] = (int) $row['empty_cylinders'];
            $row['custody_cylinders'] = (int) $row['custody_cylinders'];
            $row['gas_kg'] = (float) $row['gas_kg'];
            $row['capacity_kg'] = (float) $row['capacity_kg'];
            $liveInventory['filled'] += $row['filled_cylinders'];
            $liveInventory['empty'] += $row['empty_cylinders'];
            $liveInventory['custody'] += $row['custody_cylinders'];
            $liveInventory['gas_kg'] += $row['gas_kg'];

            $partialQ = $db->table('cylinder_units')
                ->where(['location_id' => $locationId, 'cylinder_type_id' => (int) $row['id'], 'status' => 'filled'])
                ->where('gas_weight_kg >', 0)
                ->where('gas_weight_kg <', $row['capacity_kg'])
                ->countAllResults();
            $row['partial_cylinders'] = (int) $partialQ;
            $liveInventory['partial'] += (int) $partialQ;
        }
        unset($row);

        $customerBalanceRow = $db->query(
            "SELECT COALESCE(SUM(CASE WHEN balance > 0 THEN balance ELSE 0 END),0) outstanding,
                    COALESCE(SUM(CASE WHEN balance < 0 THEN -balance ELSE 0 END),0) advances
             FROM (
                SELECT c.id,
                       c.opening_balance
                       + COALESCE((SELECT SUM(s.credit_amount) FROM sales s WHERE s.customer_id=c.id AND s.location_id=? AND s.status='posted'),0)
                       - COALESCE((SELECT SUM(r.amount) FROM customer_receipts r WHERE r.customer_id=c.id AND r.location_id=? AND r.status='posted'),0) balance
                FROM customers c
                WHERE c.is_active=1
             ) x",
            [$locationId, $locationId]
        )->getRowArray() ?: [];

        $supplierBalanceRow = $db->query(
            "SELECT COALESCE(SUM(CASE WHEN balance > 0 THEN balance ELSE 0 END),0) outstanding,
                    COALESCE(SUM(CASE WHEN balance < 0 THEN -balance ELSE 0 END),0) advances
             FROM (
                SELECT s.id,
                       s.opening_balance
                       + COALESCE((SELECT SUM(p.credit_amount) FROM purchases p WHERE p.supplier_id=s.id AND p.location_id=? AND p.status='posted'),0)
                       - COALESCE((SELECT SUM(sp.amount) FROM supplier_payments sp WHERE sp.supplier_id=s.id AND sp.location_id=? AND sp.status='posted'),0) balance
                FROM suppliers s
                WHERE s.is_active=1
             ) x",
            [$locationId, $locationId]
        )->getRowArray() ?: [];

        $custody = $db->table('cylinder_custody')
            ->select('COUNT(*) issued_count, COALESCE(SUM(deposit_amount-refund_amount),0) deposit_value')
            ->where(['location_id' => $locationId, 'status' => 'issued'])
            ->get()->getRowArray() ?: [];

        $wastageQ = $db->table('inventory_wastage_logs')
            ->select('COUNT(*) count, COALESCE(SUM(gas_weight_kg),0) gas_kg')
            ->where('location_id', $locationId);
        $this->periodQuery($wastageQ, 'created_at', $range);
        $wastage = $wastageQ->get()->getRowArray() ?: [];

        $adjustmentsQ = $db->table('inventory_movements')
            ->select('COUNT(*) count')
            ->where(['location_id' => $locationId, 'source_type' => 'adjustment']);
        $this->periodQuery($adjustmentsQ, 'movement_at', $range);
        $adjustments = (int) (($adjustmentsQ->get()->getRowArray()['count'] ?? 0));

        $auditQ = $db->table('audit_logs')
            ->select('COUNT(*) count')
            ->where('location_id', $locationId);
        $this->periodQuery($auditQ, 'created_at', $range);
        $auditCount = (int) (($auditQ->get()->getRowArray()['count'] ?? 0));

        $dailyQ = $db->table('sales')
            ->select("DATE(transaction_at) day, COUNT(*) count, COALESCE(SUM(total_amount),0) amount, COALESCE(SUM(total_kg),0) kg")
            ->where(['location_id' => $locationId, 'status' => 'posted'])
            ->whereNotIn('transaction_type', ['security_deposit', 'cylinder_return'])
            ->groupBy('day')
            ->orderBy('day');
        $this->periodQuery($dailyQ, 'transaction_at', $range);
        $dailyTrend = $dailyQ->get()->getResultArray();

        $topCustomersQ = $db->table('sales s')
            ->select('c.id,c.code,c.name,COUNT(s.id) transactions,COALESCE(SUM(s.total_amount),0) amount,COALESCE(SUM(s.total_kg),0) kg')
            ->join('customers c', 'c.id=s.customer_id', 'inner')
            ->where(['s.location_id' => $locationId, 's.status' => 'posted'])
            ->whereNotIn('s.transaction_type', ['security_deposit', 'cylinder_return'])
            ->groupBy('c.id,c.code,c.name')
            ->orderBy('amount', 'DESC')
            ->limit(8);
        $this->periodQuery($topCustomersQ, 's.transaction_at', $range);
        $topCustomers = $topCustomersQ->get()->getResultArray();

        $recent = [];
        $addRecent = static function (array &$list, array $rows): void {
            foreach ($rows as $row) {
                $list[] = $row;
            }
        };

        $recentSalesQ = $db->table('sales s')
            ->select("s.transaction_at date,s.sale_no reference,COALESCE(c.name,'Walk-in') party,s.total_amount amount,CONCAT('Sale — ',REPLACE(s.transaction_type,'_',' ')) type")
            ->join('customers c', 'c.id=s.customer_id', 'left')
            ->where(['s.location_id' => $locationId, 's.status' => 'posted']);
        $this->periodQuery($recentSalesQ, 's.transaction_at', $range);
        $addRecent($recent, $recentSalesQ->orderBy('s.transaction_at', 'DESC')->limit(10)->get()->getResultArray());

        $recentReceiptsQ = $db->table('customer_receipts r')
            ->select("r.receipt_at date,r.receipt_no reference,c.name party,r.amount amount,'Customer Receipt' type")
            ->join('customers c', 'c.id=r.customer_id', 'left')
            ->where(['r.location_id' => $locationId, 'r.status' => 'posted']);
        $this->periodQuery($recentReceiptsQ, 'r.receipt_at', $range);
        $addRecent($recent, $recentReceiptsQ->orderBy('r.receipt_at', 'DESC')->limit(10)->get()->getResultArray());

        $recentPurchasesQ = $db->table('purchases p')
            ->select("p.transaction_at date,p.purchase_no reference,s.name party,p.total_amount amount,'Purchase' type")
            ->join('suppliers s', 's.id=p.supplier_id', 'left')
            ->where(['p.location_id' => $locationId, 'p.status' => 'posted']);
        $this->periodQuery($recentPurchasesQ, 'p.transaction_at', $range);
        $addRecent($recent, $recentPurchasesQ->orderBy('p.transaction_at', 'DESC')->limit(10)->get()->getResultArray());

        $recentSupplierPaymentsQ = $db->table('supplier_payments sp')
            ->select("sp.payment_at date,sp.payment_no reference,s.name party,sp.amount amount,'Supplier Payment' type")
            ->join('suppliers s', 's.id=sp.supplier_id', 'left')
            ->where(['sp.location_id' => $locationId, 'sp.status' => 'posted']);
        $this->periodQuery($recentSupplierPaymentsQ, 'sp.payment_at', $range);
        $addRecent($recent, $recentSupplierPaymentsQ->orderBy('sp.payment_at', 'DESC')->limit(10)->get()->getResultArray());

        $recentExpensesQ = $db->table('expenses e')
            ->select("e.expense_at date,e.expense_no reference,COALESCE(ec.name,'Expense') party,e.amount amount,'Expense' type")
            ->join('expense_categories ec', 'ec.id=e.category_id', 'left')
            ->where('e.location_id', $locationId);
        $this->periodQuery($recentExpensesQ, 'e.expense_at', $range);
        $addRecent($recent, $recentExpensesQ->orderBy('e.expense_at', 'DESC')->limit(10)->get()->getResultArray());

        usort($recent, static fn (array $a, array $b): int => strcmp((string) $b['date'], (string) $a['date']));
        $recent = array_slice($recent, 0, 15);

        $salesTotal = (float) ($sales['total'] ?? 0);
        $saleCollected = (float) ($salePayments['cash'] ?? 0) + (float) ($salePayments['cheque'] ?? 0) + (float) ($salePayments['online'] ?? 0);
        $purchasesTotal = (float) ($purchases['total'] ?? 0);
        $receiptsTotal = (float) ($customerReceipts['amount'] ?? 0);
        $supplierPaymentTotal = (float) ($supplierPayments['amount'] ?? 0);
        $expenseTotal = (float) ($expenses['amount'] ?? 0);
        $cashIn = (float) ($cashRange['cash_in'] ?? 0);
        $cashOut = (float) ($cashRange['cash_out'] ?? 0);

        $summary = [
            'sales' => $salesTotal,
            'sales_count' => (int) ($sales['transaction_count'] ?? 0),
            'sales_kg' => (float) ($sales['total_kg'] ?? 0),
            'discount' => (float) ($sales['discount'] ?? 0),
            'credit_sales' => (float) ($sales['credit'] ?? 0),
            'receipts' => $receiptsTotal,
            'sale_collected' => $saleCollected,
            'customer_collected' => $saleCollected + $receiptsTotal,
            'purchases' => $purchasesTotal,
            'purchase_credit' => (float) ($purchases['credit'] ?? 0),
            'supplier_payments' => $supplierPaymentTotal,
            'expenses' => $expenseTotal,
            'cash_in' => $cashIn,
            'cash_out' => $cashOut,
            'cash_net' => $cashIn - $cashOut,
            'operating_outflow' => $supplierPaymentTotal + $expenseTotal,
            'sales_plus_receipts' => $salesTotal + $receiptsTotal,
        ];

        return view('dashboard/index', [
            'title' => 'Admin Dashboard',
            'range' => $range,
            'summary' => $summary,
            'allTransactions' => $allTransactions,
            'salesByType' => $salesByType,
            'salePayments' => $salePayments,
            'receiptPayments' => $receiptPayments,
            'expensePayments' => $expensePayments,
            'customerReceipts' => $customerReceipts,
            'purchases' => $purchases,
            'supplierPayments' => $supplierPayments,
            'expenses' => $expenses,
            'depositPeriod' => $depositPeriod,
            'depositBalance' => (float) ($depositBalance['balance'] ?? 0),
            'cashRange' => $cashRange,
            'openSession' => $openSession,
            'openSessionSummary' => $openSessionSummary,
            'inventoryRows' => $inventoryRows,
            'liveInventory' => $liveInventory,
            'receivables' => [
                'outstanding' => (float) ($customerBalanceRow['outstanding'] ?? 0),
                'advances' => (float) ($customerBalanceRow['advances'] ?? 0),
            ],
            'payables' => [
                'outstanding' => (float) ($supplierBalanceRow['outstanding'] ?? 0),
                'advances' => (float) ($supplierBalanceRow['advances'] ?? 0),
            ],
            'custody' => [
                'issued' => (int) ($custody['issued_count'] ?? 0),
                'deposit_value' => (float) ($custody['deposit_value'] ?? 0),
            ],
            'wastage' => [
                'count' => (int) ($wastage['count'] ?? 0),
                'gas_kg' => (float) ($wastage['gas_kg'] ?? 0),
            ],
            'adjustments' => $adjustments,
            'auditCount' => $auditCount,
            'dailyTrend' => $dailyTrend,
            'topCustomers' => $topCustomers,
            'recent' => $recent,
        ]);
    }
}
