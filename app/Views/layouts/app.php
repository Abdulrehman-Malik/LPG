<?php
$location = $location ?? [];
if (!$location && session()->get('location_id')) {
    try {
        $layoutDb = \Config\Database::connect();
        $location = $layoutDb->table('locations')->where('id',(int)session()->get('location_id'))->get()->getRowArray() ?: [];
    } catch (Throwable $e) {
        $location = [];
    }
}
$branchName = trim((string)($location['name'] ?? ''));
$branchName = $branchName !== '' ? $branchName : 'Perfect LPG';
$appSettings = (new \App\Models\ShopSettingsModel())->forLocation((int) session()->get('location_id'));
$fontStacks = ['system'=>'system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif','arial'=>'Arial,sans-serif','verdana'=>'Verdana,sans-serif','tahoma'=>'Tahoma,sans-serif','trebuchet'=>'"Trebuchet MS",sans-serif','georgia'=>'Georgia,serif','times'=>'"Times New Roman",serif'];
$fontStack = $fontStacks[$appSettings['font_family'] ?? 'system'] ?? $fontStacks['system'];
$current=uri_string(); $isActive=static fn(string $n):string=>str_starts_with($current,$n)?'active':'';
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?= esc($title ?? 'Dashboard') ?> — <?= esc($location['name'] ?? 'Perfect LPG') ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"><link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
<style>
:root{--lpg-dark:<?= esc($appSettings['primary_color'] ?? '#1b2a3a') ?>;--lpg-accent:<?= esc($appSettings['accent_color'] ?? '#ff7a1a') ?>;--sidebar-w:250px;--app-font-size:<?= esc((float)($appSettings['pos_font_size_px'] ?? 14)) ?>px;--app-font-family:<?= esc($fontStack ?? 'system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif') ?>}
html,body{font-family:var(--app-font-family);font-size:var(--app-font-size)}
body{background:<?= ($appSettings['theme_mode'] ?? 'light') === 'dark' ? '#12171d' : '#f4f6f9' ?>;color:<?= ($appSettings['theme_mode'] ?? 'light') === 'dark' ? '#e8edf2' : '#212529' ?>}
.sidebar{position:fixed;top:0;bottom:0;left:0;width:var(--sidebar-w);background:var(--lpg-dark);color:#cfd8e3;overflow-y:auto;z-index:1030}.sidebar .brand{padding:1.25rem 1rem;border-bottom:1px solid rgba(255,255,255,.08)}.sidebar .brand .bi{color:var(--lpg-accent);font-size:1.5rem}.sidebar .nav-link{color:#cfd8e3;padding:.65rem 1.25rem;font-size:.925em;border-left:3px solid transparent}.sidebar .nav-link i{width:1.25rem;text-align:center;margin-right:.5rem}.sidebar .nav-link:hover{background:rgba(255,255,255,.05);color:#fff}.sidebar .nav-link.active{background:color-mix(in srgb,var(--lpg-accent) 12%,transparent);border-left-color:var(--lpg-accent);color:#fff;font-weight:600}.main-content{margin-left:var(--sidebar-w);min-height:100vh}.topbar{background:<?= ($appSettings['theme_mode'] ?? 'light') === 'dark' ? '#1b222b' : '#fff' ?>;border-bottom:1px solid <?= ($appSettings['theme_mode'] ?? 'light') === 'dark' ? '#303944' : '#e6e9ee' ?>;padding:.75rem 1.5rem;display:flex;align-items:center;justify-content:space-between}.content-wrap{padding:1.5rem}<?= ($appSettings['theme_mode'] ?? 'light') === 'dark' ? '.card,.modal-content,.dropdown-menu,.list-group-item,.table{background-color:#1b222b;color:#e8edf2;border-color:#303944}.bg-white{background-color:#1b222b!important;color:#e8edf2}.text-muted{color:#aeb8c3!important}.form-control,.form-select{background-color:#151b22;color:#e8edf2;border-color:#3a4653}.form-control:focus,.form-select:focus{background-color:#151b22;color:#e8edf2}.table{--bs-table-bg:#1b222b;--bs-table-color:#e8edf2}.nav-tabs .nav-link{color:#aeb8c3}.nav-tabs .nav-link.active{background:#1b222b;color:#e8edf2}' : '' ?>@media(max-width:991.98px){.sidebar{transform:translateX(-100%);transition:transform .2s ease}.sidebar.show{transform:translateX(0)}.main-content{margin-left:0}}</style><?= $this->renderSection('styles') ?></head>
<body>
if (!$location && session()->get('location_id')) {
    try {
        $layoutDb = ConfigDatabase::connect();
        $location = $layoutDb->table('locations')->where('id',(int)session()->get('location_id'))->get()->getRowArray() ?: [];
    } catch (Throwable $e) {
        $location = [];
    }
}
$branchName = trim((string)($location['name'] ?? ''));
$branchName = $branchName !== '' ? $branchName : 'Perfect LPG';
$appSettings = (new \App\Models\ShopSettingsModel())->forLocation((int) session()->get('location_id'));
$fontStacks = ['system'=>'system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif','arial'=>'Arial,sans-serif','verdana'=>'Verdana,sans-serif','tahoma'=>'Tahoma,sans-serif','trebuchet'=>'"Trebuchet MS",sans-serif','georgia'=>'Georgia,serif','times'=>'"Times New Roman",serif'];
$fontStack = $fontStacks[$appSettings['font_family'] ?? 'system'] ?? $fontStacks['system'];
$current=uri_string(); $isActive=static fn(string $n):string=>str_starts_with($current,$n)?'active':'';
?>
<nav class="sidebar d-flex flex-column" id="sidebar"><div class="brand d-flex align-items-center"><i class="bi bi-fire me-2"></i><div><div class="fw-bold text-white"><?= esc($branchName) ?></div><div class="text-white-50" style="font-size:.7rem;">LPG POS & ERP</div></div></div>
<ul class="nav nav-pills flex-column mt-2 mb-auto">
<li class="nav-item"><a href="<?= site_url('dashboard') ?>" class="nav-link <?= $isActive('dashboard') ?>"><i class="bi bi-speedometer2"></i>Dashboard</a></li>
<li class="nav-item"><a href="<?= site_url('customers') ?>" class="nav-link <?= $isActive('customers') ?>"><i class="bi bi-people"></i>Customers / Parties</a></li>
<li class="nav-item"><a href="<?= site_url('suppliers') ?>" class="nav-link <?= $isActive('suppliers') ?>"><i class="bi bi-truck"></i>Suppliers</a></li>
<li class="nav-item"><a href="<?= site_url('cylinder-types') ?>" class="nav-link <?= $isActive('cylinder-types') ?>"><i class="bi bi-box"></i>Cylinder Types</a></li>
<li class="nav-item"><a href="<?= site_url('rates') ?>" class="nav-link <?= $isActive('rates') ?>"><i class="bi bi-tags"></i>LPG Rates</a></li>
<li class="nav-item"><a href="<?= site_url('inventory/opening') ?>" class="nav-link <?= $isActive('inventory/opening') ?>"><i class="bi bi-archive"></i>Opening Inventory</a></li>
<li class="nav-item"><a href="<?= site_url('users') ?>" class="nav-link <?= $isActive('users') ?>"><i class="bi bi-person-gear"></i>Users / Roles</a></li>
<li class="nav-item"><a href="<?= site_url('shop-settings') ?>" class="nav-link <?= $isActive('shop-settings') ?>"><i class="bi bi-gear-wide-connected"></i>Shop Settings</a></li>
<li class="nav-item mt-2 pt-2 border-top border-white border-opacity-10"><a href="<?= site_url('sales') ?>" class="nav-link <?= $isActive('sales') ?>"><i class="bi bi-cart-plus"></i>POS Sales</a></li>
<li class="nav-item"><a href="<?= site_url('cash') ?>" class="nav-link <?= $isActive('cash') ?>"><i class="bi bi-cash-stack"></i>Counter Cash</a></li><li class="nav-item"><a href="<?= site_url('purchases') ?>" class="nav-link <?= $isActive('purchases') ?>"><i class="bi bi-bag-plus"></i>Purchases</a></li><li class="nav-item"><a href="<?= site_url('inventory') ?>" class="nav-link <?= $isActive('inventory') ?>"><i class="bi bi-boxes"></i>Inventory</a></li><li class="nav-item"><a href="<?= site_url('inventory/controls') ?>" class="nav-link <?= $isActive('inventory/controls') ?>"><i class="bi bi-sliders"></i>Inventory Controls</a></li><li class="nav-item"><a href="<?= site_url('inventory/wastage') ?>" class="nav-link <?= $isActive('inventory/wastage') ?>"><i class="bi bi-droplet-half"></i>Gas Wastage</a></li><li class="nav-item"><a href="<?= site_url('expenses') ?>" class="nav-link <?= $isActive('expenses') ?>"><i class="bi bi-receipt"></i>Expenses</a></li><li class="nav-item"><a href="<?= site_url('receipts') ?>" class="nav-link <?= $isActive('receipts') ?>"><i class="bi bi-wallet2"></i>Customer Receipts</a></li><li class="nav-item"><a href="<?= site_url('supplier-payments') ?>" class="nav-link <?= $isActive('supplier-payments') ?>"><i class="bi bi-credit-card"></i>Supplier Payments</a></li><li class="nav-item"><a href="<?= site_url('reports') ?>" class="nav-link <?= $isActive('reports') ?>"><i class="bi bi-bar-chart"></i>Reports</a></li><li class="nav-item"><a href="<?= site_url('audit') ?>" class="nav-link <?= $isActive('audit') ?>"><i class="bi bi-shield-check"></i>Audit Log</a></li>
</ul><div class="p-3 border-top border-white border-opacity-10"><a href="<?= site_url('logout') ?>" class="nav-link text-white-50"><i class="bi bi-box-arrow-right"></i>Logout</a></div></nav>
<div class="main-content"><div class="topbar"><button class="btn btn-sm btn-outline-secondary d-lg-none" id="sidebarToggle"><i class="bi bi-list"></i></button><h5 class="mb-0"><?= esc($title ?? 'Dashboard') ?></h5><div class="d-flex align-items-center gap-2"><span class="small"><?= esc($branchName) ?></span><span class="text-muted small"><?= esc(session()->get('full_name')??'User') ?> <span class="badge bg-secondary"><?= esc(session()->get('role')??'') ?></span></span></div></div>
<div class="content-wrap"><?php if(session()->getFlashdata('success')): ?><div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div><?php endif; ?><?php if(session()->getFlashdata('error')): ?><div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div><?php endif; ?><?= $this->renderSection('content') ?></div></div>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script><script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script><script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script><script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script><script>document.getElementById('sidebarToggle')?.addEventListener('click',()=>document.getElementById('sidebar').classList.toggle('show'));$('.datatable').DataTable({pageLength:25});</script><?= $this->renderSection('scripts') ?></body></html>