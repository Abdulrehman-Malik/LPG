<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>

<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="card kpi-card"><div class="card-body d-flex align-items-center gap-3">
        <div class="icon-badge" style="background:#ff7a1a;"><i class="bi bi-cash-stack"></i></div>
        <div><div class="text-muted small">Today's Sales</div><div class="fs-4 fw-bold">Rs. <?= number_format($todaysSales,2) ?></div></div>
    </div></div></div>
    <div class="col-md-4"><div class="card kpi-card"><div class="card-body d-flex align-items-center gap-3">
        <div class="icon-badge" style="background:#1b2a3a;"><i class="bi bi-archive"></i></div>
        <div><div class="text-muted small">Filled Cylinder Stock</div><div class="fs-4 fw-bold"><?= number_format($cylinderStock) ?></div></div>
    </div></div></div>
    <div class="col-md-4"><div class="card kpi-card"><div class="card-body d-flex align-items-center gap-3">
        <div class="icon-badge" style="background:#2f9e44;"><i class="bi bi-speedometer"></i></div>
        <div><div class="text-muted small">Gas Stock</div><div class="fs-4 fw-bold"><?= number_format($totalGasStockKg,2) ?> kg</div></div>
    </div></div></div>
</div>

<div class="alert alert-light border">
    <i class="bi bi-check-circle me-1 text-success"></i>
    Foundation is connected to the new transaction/ledger database. Complete module testing is tracked in TESTING.md.
</div>

<?= $this->endSection() ?>
