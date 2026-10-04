<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>

<?php
$money = static fn(float $v): string => 'Rs. ' . number_format($v, 2);
$qty = static fn(float $v): string => number_format($v, 2);
$stockTab = trim((string)(service('request')->getGet('stock_tab') ?? ''));
if (!in_array($stockTab, ['filled', 'empty', 'issued'], true)) {
    $stockTab = '';
}
$displayStockTab = $stockTab !== '' ? $stockTab : 'filled';
?>

<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
    <div>
        <h4 class="mb-1"><i class="bi bi-speedometer2 me-2 text-warning"></i>Dashboard</h4>
        <div class="text-muted small"><?= esc($range['label']) ?></div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="<?= site_url('dashboard?from=' . date('Y-m-d') . '&to=' . date('Y-m-d')) ?>" class="btn btn-sm <?= $range['isToday'] ? 'btn-primary' : 'btn-outline-primary' ?>">Today</a>
        <a href="<?= site_url('dashboard?from=' . date('Y-m-d', strtotime('-6 days')) . '&to=' . date('Y-m-d')) ?>" class="btn btn-sm btn-outline-secondary">7 Days</a>
        <a href="<?= site_url('dashboard?from=' . date('Y-m-d', strtotime('-29 days')) . '&to=' . date('Y-m-d')) ?>" class="btn btn-sm btn-outline-secondary">30 Days</a>
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
        </form>
    </div>
</div>

<ul class="nav nav-tabs dashboard-tabs mb-4" id="dashboardTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $stockTab === '' ? 'active' : '' ?> fw-semibold" id="summary-tab" data-bs-toggle="tab" data-bs-target="#summary-pane" type="button" role="tab">
            <i class="bi bi-grid-1x2 me-1"></i>Summary
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $stockTab !== '' ? 'active' : '' ?> fw-semibold" id="stock-tab" data-bs-toggle="tab" data-bs-target="#stock-pane" type="button" role="tab">
            <i class="bi bi-boxes me-1"></i>Current Stock
        </button>
    </li>
</ul>

<div class="tab-content" id="dashboardTabContent">
    <div class="tab-pane fade <?= $stockTab === '' ? 'show active' : '' ?>" id="summary-pane" role="tabpanel" aria-labelledby="summary-tab">
        <div class="row g-3">
            <div class="col-12 col-md-6 col-xl">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-body">
                        <div class="text-muted small">Total Sale</div>
                        <div class="display-6 fw-semibold mt-1"><?= $money((float)$summary['sales']) ?></div>
                        <div class="small text-muted mt-2"><?= number_format((int)$summary['sale_count']) ?> posted sales</div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-xl">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-body">
                        <div class="text-muted small">Cash Sale</div>
                        <div class="display-6 fw-semibold mt-1"><?= $money((float)$summary['cash_sale']) ?></div>
                        <div class="small text-muted mt-2">Cash payments against sales</div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-xl">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-body">
                        <div class="text-muted small">Credit Sale</div>
                        <div class="display-6 fw-semibold mt-1"><?= $money((float)$summary['credit_sale']) ?></div>
                        <div class="small text-muted mt-2">Credit created during this period</div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-xl">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-body">
                        <div class="text-muted small">Payments</div>
                        <div class="display-6 fw-semibold mt-1"><?= $money((float)$summary['payments']) ?></div>
                        <div class="small text-muted mt-2">Sale payments + customer receipts</div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-xl">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-body">
                        <div class="text-muted small">Stock Purchased</div>
                        <div class="display-6 fw-semibold mt-1"><?= $money((float)$summary['stock_purchased']) ?></div>
                        <div class="small text-muted mt-2"><?= number_format((int)$summary['purchase_count']) ?> purchases</div>
                        <div class="small mt-2">
                            Gas: <strong><?= $qty((float)$summary['purchase_gas_kg']) ?> KG</strong>
                            · Filled: <strong><?= number_format((float)$summary['purchase_filled'], 0) ?></strong>
                            · Empty: <strong><?= number_format((float)$summary['purchase_empty'], 0) ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-muted small mt-4">
            The selected date range controls the five summary figures above. Current physical stock is shown in the separate Current Stock tab.
        </div>
    </div>

    <div class="tab-pane fade <?= $stockTab !== '' ? 'show active' : '' ?>" id="stock-pane" role="tabpanel" aria-labelledby="stock-tab">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h5 class="mb-1">Current Stock Details</h5>
                <div class="text-muted small">Live physical cylinder position in this branch.</div>
            </div>
            <a href="<?= site_url('reports/inventory-detail') ?>" class="btn btn-sm btn-outline-secondary">Inventory Detail Report</a>
        </div>

        <div class="row g-3 mb-4">
            <?php
            $stockCards = [
                'filled' => ['title'=>'Filled Cylinders','icon'=>'bi-fuel-pump-fill','value'=>number_format($stock['filled']['count']),'sub'=>$qty((float)$stock['filled']['gas_kg']).' KG gas'],
                'empty' => ['title'=>'Empty Cylinders','icon'=>'bi-circle-square','value'=>number_format($stock['empty']['count']),'sub'=>'Empty physical cylinders'],
                'issued' => ['title'=>'Issued Temporarily Against Deposit','icon'=>'bi-person-badge','value'=>number_format($stock['issued']['count']),'sub'=>$money((float)$stock['issued']['deposit']).' deposit held'],
            ];
            foreach ($stockCards as $key=>$card):
                $active = $stockTab === $key;
                $url = site_url('dashboard?from='.urlencode($range['from']).'&to='.urlencode($range['to']).'&stock_tab='.$key);
            ?>
            <div class="col-md-4">
                <a href="<?= esc($url) ?>" class="text-decoration-none stock-type-card">
                    <div class="card h-100 shadow-sm <?= $active ? 'border-primary border-2' : 'border-0' ?>">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="text-muted small"><?= esc($card['title']) ?></div>
                                <i class="bi <?= esc($card['icon']) ?> fs-5 text-primary"></i>
                            </div>
                            <div class="display-6 fw-semibold mt-2"><?= esc($card['value']) ?></div>
                            <div class="small text-muted mt-1"><?= esc($card['sub']) ?></div>
                            <div class="small text-primary mt-3"><i class="bi bi-arrow-right-circle me-1"></i>View details</div>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <strong><?= esc($stock[$displayStockTab]['title']) ?></strong>
                <span class="badge text-bg-secondary float-end"><?= number_format((int)$stock[$displayStockTab]['count']) ?> record(s)</span>
            </div>

            <?php if ($displayStockTab === 'filled'): ?>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0 datatable">
                        <thead>
                            <tr><th>#</th><th>Physical Cylinder</th><th>Type</th><th>Capacity KG</th><th>Current Gas KG</th><th>Fill Status</th><th>Last Updated</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($stock['filled']['rows'] as $i=>$row): ?>
                            <?php $isPartial = (float)$row['gas_weight_kg'] < (float)$row['capacity_kg']; ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td class="fw-semibold"><?= esc($row['unit_code']) ?></td>
                                <td><?= esc($row['type_code'].' — '.$row['type_name']) ?></td>
                                <td><?= $qty((float)$row['capacity_kg']) ?></td>
                                <td class="fw-semibold"><?= $qty((float)$row['gas_weight_kg']) ?></td>
                                <td><span class="badge <?= $isPartial ? 'text-bg-warning' : 'text-bg-success' ?>"><?= $isPartial ? 'Partially Filled' : 'Full' ?></span></td>
                                <td><?= esc($row['updated_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php elseif ($displayStockTab === 'empty'): ?>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0 datatable">
                        <thead>
                            <tr><th>#</th><th>Physical Cylinder</th><th>Type</th><th>Capacity KG</th><th>Gas KG</th><th>Last Updated</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($stock['empty']['rows'] as $i=>$row): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td class="fw-semibold"><?= esc($row['unit_code']) ?></td>
                                <td><?= esc($row['type_code'].' — '.$row['type_name']) ?></td>
                                <td><?= $qty((float)$row['capacity_kg']) ?></td>
                                <td>0.00</td>
                                <td><?= esc($row['updated_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0 datatable">
                        <thead>
                            <tr><th>#</th><th>Physical Cylinder</th><th>Type</th><th>Capacity KG</th><th>Customer</th><th>Phone</th><th>Deposit</th><th>Issued At</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($stock['issued']['rows'] as $i=>$row): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td class="fw-semibold"><?= esc($row['unit_code']) ?></td>
                                <td><?= esc($row['type_code'].' — '.$row['type_name']) ?></td>
                                <td><?= $qty((float)$row['capacity_kg']) ?></td>
                                <td><strong><?= esc($row['customer_name']) ?></strong><div class="small text-muted"><?= esc($row['customer_code'] ?? '') ?></div></td>
                                <td><?= esc($row['customer_phone'] ?? '') ?></td>
                                <td class="fw-semibold"><?= $money((float)$row['deposit_amount']) ?></td>
                                <td><?= esc($row['issued_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <?php if (!$stock[$displayStockTab]['rows']): ?>
                <div class="alert alert-light border rounded-0 mb-0">No <?= esc(strtolower($stock[$displayStockTab]['title'])) ?> found in the current physical stock.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
