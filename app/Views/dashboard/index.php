<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>

<?php
$money = static fn(float $v): string => 'Rs. ' . number_format($v, 2);
$qty = static fn(float $v): string => number_format($v, 2);
$label = static fn(string $v): string => ucwords(str_replace('_', ' ', $v));
$salesTotal = (float) ($summary['sales'] ?? 0);
$receiptsTotal = (float) ($summary['receipts'] ?? 0);
$purchasesTotal = (float) ($summary['purchases'] ?? 0);
$supplierPaymentsTotal = (float) ($summary['supplier_payments'] ?? 0);
$expensesTotal = (float) ($summary['expenses'] ?? 0);
$trendMax = 0.0;
foreach ($dailyTrend as $day) $trendMax = max($trendMax, (float) ($day['amount'] ?? 0));
$todayUrl = site_url('dashboard?from=' . date('Y-m-d') . '&to=' . date('Y-m-d'));
$weekFrom = date('Y-m-d', strtotime('-6 days'));
$weekUrl = site_url('dashboard?from=' . $weekFrom . '&to=' . date('Y-m-d'));
$monthFrom = date('Y-m-d', strtotime('-29 days'));
$monthUrl = site_url('dashboard?from=' . $monthFrom . '&to=' . date('Y-m-d'));
?>

<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
    <div>
        <h4 class="mb-1"><i class="bi bi-speedometer2 me-2 text-warning"></i>Admin Dashboard</h4>
        <div class="text-muted small">
            <?= esc($range['isToday'] ? "Today's management position" : 'Management performance for the selected period') ?>
            · <?= esc($range['label']) ?>
        </div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="<?= esc($todayUrl) ?>" class="btn btn-sm <?= $range['isToday'] ? 'btn-primary' : 'btn-outline-primary' ?>">Today</a>
        <a href="<?= esc($weekUrl) ?>" class="btn btn-sm btn-outline-secondary">Last 7 Days</a>
        <a href="<?= esc($monthUrl) ?>" class="btn btn-sm btn-outline-secondary">Last 30 Days</a>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <form method="get" action="<?= site_url('dashboard') ?>" class="row g-2 align-items-end">
            <div class="col-sm-5 col-md-3">
                <label class="form-label small text-muted mb-1">From</label>
                <input type="date" name="from" class="form-control" value="<?= esc($range['from']) ?>" required>
            </div>
            <div class="col-sm-5 col-md-3">
                <label class="form-label small text-muted mb-1">To</label>
                <input type="date" name="to" class="form-control" value="<?= esc($range['to']) ?>" required>
            </div>
            <div class="col-sm-2 col-md-2">
                <button class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i>Apply</button>
            </div>
            <div class="col-md-4">
                <div class="small text-muted mt-2 mt-md-0">
                    <strong><?= esc($range['label']) ?></strong>
                    · <?= number_format($allTransactions) ?> posted system transactions
                </div>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-body">
                <div class="d-flex justify-content-between"><span class="text-muted small">Gross Sales</span><i class="bi bi-cart-check text-warning"></i></div>
                <div class="fs-4 fw-bold mt-1"><?= $money($salesTotal) ?></div>
                <div class="small text-muted"><?= number_format((int)($summary['sales_count'] ?? 0)) ?> sales · <?= $qty((float)($summary['sales_kg'] ?? 0)) ?> KG</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-body">
                <div class="d-flex justify-content-between"><span class="text-muted small">Customer Collections</span><i class="bi bi-wallet2 text-success"></i></div>
                <div class="fs-4 fw-bold mt-1"><?= $money((float)($summary['customer_collected'] ?? 0)) ?></div>
                <div class="small text-muted">Non-credit sale payments + OS receipts</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-body">
                <div class="d-flex justify-content-between"><span class="text-muted small">Purchases</span><i class="bi bi-bag-check text-primary"></i></div>
                <div class="fs-4 fw-bold mt-1"><?= $money($purchasesTotal) ?></div>
                <div class="small text-muted"><?= number_format((int)($purchases['transaction_count'] ?? 0)) ?> posted purchases</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-body">
                <div class="d-flex justify-content-between"><span class="text-muted small">Operating Outflow</span><i class="bi bi-arrow-down-circle text-danger"></i></div>
                <div class="fs-4 fw-bold mt-1"><?= $money($supplierPaymentsTotal + $expensesTotal) ?></div>
                <div class="small text-muted">Supplier payments + expenses</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-header bg-white border-0 pt-3"><strong>Receivables</strong><span class="badge text-bg-warning float-end">Live</span></div>
            <div class="card-body pt-1">
                <div class="display-6 fw-semibold"><?= $money((float)$receivables['outstanding']) ?></div>
                <div class="text-muted small">Customer outstanding balance</div>
                <div class="mt-2 small">Customer advances / overpayments: <strong><?= $money((float)$receivables['advances']) ?></strong></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-header bg-white border-0 pt-3"><strong>Payables</strong><span class="badge text-bg-warning float-end">Live</span></div>
            <div class="card-body pt-1">
                <div class="display-6 fw-semibold"><?= $money((float)$payables['outstanding']) ?></div>
                <div class="text-muted small">Supplier outstanding balance</div>
                <div class="mt-2 small">Supplier advances / credits: <strong><?= $money((float)$payables['advances']) ?></strong></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-header bg-white border-0 pt-3"><strong>Security Deposit Liability</strong><span class="badge text-bg-warning float-end">Live</span></div>
            <div class="card-body pt-1">
                <div class="display-6 fw-semibold"><?= $money((float)$depositBalance) ?></div>
                <div class="text-muted small">Net refundable deposits held</div>
                <div class="mt-2 small">Selected period: held <?= $money((float)($depositPeriod['held'] ?? 0)) ?> · refunded <?= $money((float)($depositPeriod['refunded'] ?? 0)) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-xl-7">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <strong>Sales Trend</strong>
                <span class="small text-muted"><?= count($dailyTrend) ?> active day(s)</span>
            </div>
            <div class="card-body">
                <?php if (!$dailyTrend): ?>
                    <div class="alert alert-light border mb-0">No posted sales were recorded in this date range.</div>
                <?php else: ?>
                    <?php foreach ($dailyTrend as $day): ?>
                        <?php $amount = (float)($day['amount'] ?? 0); $width = $trendMax > 0 ? max(3, ($amount / $trendMax) * 100) : 3; ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between small mb-1">
                                <span><?= esc(date('D, d M', strtotime($day['day']))) ?></span>
                                <span><strong><?= $money($amount) ?></strong> · <?= $qty((float)($day['kg'] ?? 0)) ?> KG</span>
                            </div>
                            <div class="progress" style="height:9px"><div class="progress-bar" style="width:<?= number_format($width, 2, '.', '') ?>%"></div></div>
                            <div class="text-muted small mt-1"><?= number_format((int)($day['count'] ?? 0)) ?> transaction(s)</div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-xl-5">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white"><strong>Sales & Collection Mix</strong></div>
            <div class="card-body">
                <div class="row g-2">
                    <?php foreach (['cash'=>'Cash','cheque'=>'Cheque','online'=>'Online','credit'=>'Credit'] as $mode=>$modeLabel): ?>
                        <div class="col-6">
                            <div class="border rounded p-2">
                                <div class="text-muted small"><?= esc($modeLabel) ?> sales</div>
                                <div class="fw-bold"><?= $money((float)($salePayments[$mode] ?? 0)) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <hr>
                <div class="d-flex justify-content-between mb-1"><span class="text-muted">OS receipts</span><strong><?= $money($receiptsTotal) ?></strong></div>
                <div class="d-flex justify-content-between mb-1"><span class="text-muted">Sales credit created</span><strong><?= $money((float)($summary['credit_sales'] ?? 0)) ?></strong></div>
                <div class="d-flex justify-content-between"><span class="text-muted">Discounts granted</span><strong><?= $money((float)($summary['discount'] ?? 0)) ?></strong></div>
                <div class="small text-muted mt-3">Gross sales is turnover, not profit. The current schema does not provide a full cost-of-goods valuation for a reliable profit figure.</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-xl-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white"><strong>Cash Control</strong></div>
            <div class="card-body">
                <div class="row text-center g-2">
                    <div class="col-4"><div class="text-muted small">Cash IN</div><strong><?= $money((float)($cashRange['cash_in'] ?? 0)) ?></strong></div>
                    <div class="col-4"><div class="text-muted small">Cash OUT</div><strong><?= $money((float)($cashRange['cash_out'] ?? 0)) ?></strong></div>
                    <div class="col-4"><div class="text-muted small">Net</div><strong><?= $money((float)($summary['cash_net'] ?? 0)) ?></strong></div>
                </div>
                <hr>
                <?php if ($openSession): ?>
                    <div class="alert alert-success py-2 mb-2">
                        <strong>Open:</strong> <?= esc($openSession['register_code']) ?> — <?= esc($openSession['register_name']) ?>
                    </div>
                    <div class="d-flex justify-content-between small"><span>Opening float</span><strong><?= $money((float)$openSession['opening_cash']) ?></strong></div>
                    <div class="d-flex justify-content-between small"><span>Expected cash</span><strong><?= $money((float)($openSessionSummary['expected'] ?? 0)) ?></strong></div>
                    <a href="<?= site_url('cash') ?>" class="btn btn-sm btn-outline-primary w-100 mt-3">Open Counter Cash</a>
                <?php else: ?>
                    <div class="alert alert-warning py-2 mb-0"><strong>No cash session is open.</strong> Open the counter before cash operations.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white"><strong>Inventory — Live Physical Position</strong><span class="badge text-bg-secondary float-end">Live now</span></div>
            <div class="card-body">
                <div class="row g-2 text-center">
                    <div class="col-6"><div class="border rounded p-2"><div class="text-muted small">Filled</div><div class="fs-4 fw-bold"><?= number_format($liveInventory['filled']) ?></div></div></div>
                    <div class="col-6"><div class="border rounded p-2"><div class="text-muted small">Empty</div><div class="fs-4 fw-bold"><?= number_format($liveInventory['empty']) ?></div></div></div>
                    <div class="col-6"><div class="border rounded p-2"><div class="text-muted small">Customer Custody</div><div class="fs-4 fw-bold"><?= number_format($liveInventory['custody']) ?></div></div></div>
                    <div class="col-6"><div class="border rounded p-2"><div class="text-muted small">Gas in Filled</div><div class="fs-4 fw-bold"><?= $qty($liveInventory['gas_kg']) ?> KG</div></div></div>
                </div>
                <div class="mt-3 small text-muted">Partially used filled cylinders currently on hand: <strong><?= number_format($liveInventory['partial']) ?></strong></div>
                <a href="<?= site_url('reports/inventory-detail') ?>" class="btn btn-sm btn-outline-success w-100 mt-3">Open Inventory Detail</a>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white"><strong>Control & Exceptions</strong></div>
            <div class="card-body">
                <div class="d-flex justify-content-between border-bottom py-2"><span>Gas wastage</span><strong><?= $qty((float)($wastage['gas_kg'] ?? 0)) ?> KG <span class="text-muted fw-normal">(<?= number_format((int)($wastage['count'] ?? 0)) ?>)</span></strong></div>
                <div class="d-flex justify-content-between border-bottom py-2"><span>Stock adjustments</span><strong><?= number_format($adjustments) ?></strong></div>
                <div class="d-flex justify-content-between border-bottom py-2"><span>Audit events</span><strong><?= number_format($auditCount) ?></strong></div>
                <div class="d-flex justify-content-between border-bottom py-2"><span>Cylinders with customers</span><strong><?= number_format((int)$custody['issued']) ?></strong></div>
                <div class="d-flex justify-content-between py-2"><span>Custody deposit value</span><strong><?= $money((float)$custody['deposit_value']) ?></strong></div>
                <?php if ((float)($wastage['gas_kg'] ?? 0) > 0): ?><div class="alert alert-warning py-2 small mt-3 mb-0">Wastage was recorded in this range. Review the wastage report and reason entries.</div><?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-xl-7">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white"><strong>Live Stock by Cylinder Type</strong></div>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead><tr><th>Type</th><th>Capacity</th><th>Filled</th><th>Gas KG</th><th>Empty</th><th>Custody</th><th>Partial</th></tr></thead>
                    <tbody>
                    <?php foreach ($inventoryRows as $row): ?>
                        <tr>
                            <td><strong><?= esc($row['code']) ?></strong><div class="small text-muted"><?= esc($row['name']) ?></div></td>
                            <td><?= $qty((float)$row['capacity_kg']) ?></td>
                            <td><?= number_format((int)$row['filled_cylinders']) ?></td>
                            <td><?= $qty((float)$row['gas_kg']) ?></td>
                            <td><?= number_format((int)$row['empty_cylinders']) ?></td>
                            <td><?= number_format((int)$row['custody_cylinders']) ?></td>
                            <td><?= number_format((int)$row['partial_cylinders']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-xl-5">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white"><strong>Top Customers in Selected Period</strong></div>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead><tr><th>Customer</th><th>Sales</th><th>KG</th></tr></thead>
                    <tbody>
                    <?php if (!$topCustomers): ?>
                        <tr><td colspan="3" class="text-muted text-center py-3">No customer sales in this range.</td></tr>
                    <?php else: ?>
                        <?php foreach ($topCustomers as $row): ?>
                            <tr>
                                <td><strong><?= esc($row['name']) ?></strong><div class="small text-muted"><?= esc($row['code'] ?? '') ?></div></td>
                                <td><?= $money((float)$row['amount']) ?><div class="small text-muted"><?= number_format((int)$row['transactions']) ?> tx</div></td>
                                <td><?= $qty((float)$row['kg']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white"><strong>Purchasing</strong></div>
            <div class="card-body">
                <div class="fs-4 fw-bold"><?= $money($purchasesTotal) ?></div>
                <div class="small text-muted mb-3"><?= number_format((int)($purchases['transaction_count'] ?? 0)) ?> purchases · Credit created <?= $money((float)($purchases['credit'] ?? 0)) ?></div>
                <div class="d-flex justify-content-between small"><span>Supplier payments</span><strong><?= $money($supplierPaymentsTotal) ?></strong></div>
                <div class="d-flex justify-content-between small mt-1"><span>Current supplier payable</span><strong><?= $money((float)$payables['outstanding']) ?></strong></div>
                <a href="<?= site_url('purchases') ?>" class="btn btn-sm btn-outline-primary w-100 mt-3">Open Purchases</a>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white"><strong>Expenses</strong></div>
            <div class="card-body">
                <div class="fs-4 fw-bold"><?= $money($expensesTotal) ?></div>
                <div class="small text-muted mb-3"><?= number_format((int)($expenses['count'] ?? 0)) ?> expense entries</div>
                <?php foreach (['cash'=>'Cash','cheque'=>'Cheque','online'=>'Online'] as $mode=>$modeLabel): ?>
                    <div class="d-flex justify-content-between small border-bottom py-1"><span><?= esc($modeLabel) ?></span><strong><?= $money((float)($expensePayments[$mode] ?? 0)) ?></strong></div>
                <?php endforeach; ?>
                <a href="<?= site_url('expenses') ?>" class="btn btn-sm btn-outline-danger w-100 mt-3">Open Expenses</a>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white"><strong>Customer Receipts</strong></div>
            <div class="card-body">
                <div class="fs-4 fw-bold"><?= $money($receiptsTotal) ?></div>
                <div class="small text-muted mb-3"><?= number_format((int)($customerReceipts['count'] ?? 0)) ?> receipt entries</div>
                <?php foreach (['cash'=>'Cash','cheque'=>'Cheque','online'=>'Online'] as $mode=>$modeLabel): ?>
                    <div class="d-flex justify-content-between small border-bottom py-1"><span><?= esc($modeLabel) ?></span><strong><?= $money((float)($receiptPayments[$mode] ?? 0)) ?></strong></div>
                <?php endforeach; ?>
                <a href="<?= site_url('receipts') ?>" class="btn btn-sm btn-outline-success w-100 mt-3">Open Customer Receipts</a>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <strong>Recent Activity in Selected Period</strong>
                <span class="small text-muted"><?= count($recent) ?> latest entries</span>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead><tr><th>Date / Time</th><th>Type</th><th>Reference</th><th>Party / Category</th><th class="text-end">Amount</th></tr></thead>
                    <tbody>
                    <?php if (!$recent): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">No activity in this date range.</td></tr>
                    <?php else: ?>
                        <?php foreach ($recent as $row): ?>
                            <tr>
                                <td class="small"><?= esc(date('d M Y H:i', strtotime($row['date']))) ?></td>
                                <td><?= esc($row['type']) ?></td>
                                <td class="fw-semibold"><?= esc($row['reference']) ?></td>
                                <td><?= esc($row['party']) ?></td>
                                <td class="text-end fw-semibold"><?= $money((float)$row['amount']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white"><strong>Sales by Transaction Type</strong></div>
            <div class="card-body">
                <?php if (!$salesByType): ?>
                    <div class="text-muted">No sales in this period.</div>
                <?php else: ?>
                    <?php foreach ($salesByType as $row): ?>
                        <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                            <div><strong><?= esc($label((string)$row['transaction_type'])) ?></strong><div class="small text-muted"><?= number_format((int)$row['count']) ?> tx</div></div>
                            <strong><?= $money((float)$row['amount']) ?></strong>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="small text-muted mt-4">
    Live balances and physical inventory are shown as of now. The selected date range controls operating activity and financial transactions only.
</div>

<?= $this->endSection() ?>
