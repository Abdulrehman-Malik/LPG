<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title ?? 'Dashboard') ?> — Perfect LPG (Pvt.) LTD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
    <style>
        :root {
            --lpg-dark: #1b2a3a;
            --lpg-dark-2: #16212e;
            --lpg-accent: #ff7a1a;
            --sidebar-w: 250px;
        }
        body { background: #f4f6f9; }

        /* ---- Sidebar ---- */
        .sidebar {
            position: fixed;
            top: 0; bottom: 0; left: 0;
            width: var(--sidebar-w);
            background: var(--lpg-dark);
            color: #cfd8e3;
            overflow-y: auto;
            z-index: 1030;
        }
        .sidebar .brand {
            padding: 1.25rem 1rem;
            border-bottom: 1px solid rgba(255,255,255,.08);
        }
        .sidebar .brand .bi { color: var(--lpg-accent); font-size: 1.5rem; }
        .sidebar .nav-link {
            color: #cfd8e3;
            padding: .65rem 1.25rem;
            font-size: .925rem;
            border-left: 3px solid transparent;
        }
        .sidebar .nav-link i { width: 1.25rem; text-align: center; margin-right: .5rem; }
        .sidebar .nav-link:hover {
            background: rgba(255,255,255,.05);
            color: #fff;
        }
        .sidebar .nav-link.active {
            background: rgba(255,122,26,.12);
            border-left-color: var(--lpg-accent);
            color: #fff;
            font-weight: 600;
        }

        /* ---- Main content ---- */
        .main-content {
            margin-left: var(--sidebar-w);
            min-height: 100vh;
        }
        .topbar {
            background: #fff;
            border-bottom: 1px solid #e6e9ee;
            padding: .75rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .content-wrap { padding: 1.5rem; }

        .kpi-card {
            border: none;
            border-radius: .75rem;
            box-shadow: 0 .25rem .75rem rgba(0,0,0,.05);
        }
        .kpi-card .icon-badge {
            width: 48px; height: 48px;
            border-radius: .6rem;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.3rem;
            color: #fff;
        }

        @media (max-width: 991.98px) {
            .sidebar { transform: translateX(-100%); transition: transform .2s ease; }
            .sidebar.show { transform: translateX(0); }
            .main-content { margin-left: 0; }
        }
    </style>
    <?= $this->renderSection('styles') ?>
</head>
<body>

<nav class="sidebar d-flex flex-column" id="sidebar">
    <div class="brand d-flex align-items-center">
        <i class="bi bi-fire me-2"></i>
        <div>
            <div class="fw-bold text-white" style="font-size:.95rem;">Perfect LPG</div>
            <div class="text-white-50" style="font-size:.7rem;">(Pvt.) LTD</div>
        </div>
    </div>

    <?php
        // Highlight the active link by matching the current route/segment.
        $current = uri_string();
        $isActive = static fn (string $needle): string =>
            str_starts_with($current, $needle) ? 'active' : '';
    ?>

    <ul class="nav nav-pills flex-column mt-2 mb-auto">
        <li class="nav-item">
            <a href="<?= site_url('dashboard') ?>" class="nav-link <?= $isActive('dashboard') ?>">
                <i class="bi bi-speedometer2"></i>Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= site_url('sales/new') ?>" class="nav-link <?= $isActive('sales/new') ?>">
                <i class="bi bi-cart-plus"></i>New Sale
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= site_url('sales/search') ?>" class="nav-link <?= $isActive('sales/search') ?>">
                <i class="bi bi-search"></i>Sales Search
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= site_url('customers') ?>" class="nav-link <?= $isActive('customers') ?>">
                <i class="bi bi-people"></i>Customers / Parties
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= site_url('cylinder-stock') ?>" class="nav-link <?= $isActive('cylinder-stock') ?>">
                <i class="bi bi-archive"></i>Cylinder Stock
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= site_url('reports/daily-cash') ?>" class="nav-link <?= $isActive('reports/daily-cash') ?>">
                <i class="bi bi-cash-coin"></i>Daily Cash / Sale Report
            </a>
        </li>
        <li class="nav-item mt-2 pt-2 border-top border-white border-opacity-10">
            <a href="<?= site_url('settings') ?>" class="nav-link <?= $isActive('settings') ?>">
                <i class="bi bi-gear"></i>Settings &amp; Users
            </a>
        </li>
    </ul>

    <div class="p-3 border-top border-white border-opacity-10">
        <a href="<?= site_url('logout') ?>" class="nav-link text-white-50">
            <i class="bi bi-box-arrow-right"></i>Logout
        </a>
    </div>
</nav>

<div class="main-content">
    <div class="topbar">
        <button class="btn btn-sm btn-outline-secondary d-lg-none" id="sidebarToggle">
            <i class="bi bi-list"></i>
        </button>
        <h5 class="mb-0"><?= esc($title ?? 'Dashboard') ?></h5>
        <div class="d-flex align-items-center gap-2">
            <span class="text-muted small d-none d-sm-inline">
                <?= esc(session()->get('full_name') ?? 'User') ?>
                <span class="badge bg-secondary ms-1"><?= esc(session()->get('role') ?? '') ?></span>
            </span>
        </div>
    </div>

    <div class="content-wrap">
        <?php if (session()->getFlashdata('success')) : ?>
            <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('error')) : ?>
            <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
        <?php endif; ?>

        <?= $this->renderSection('content') ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script>
    document.getElementById('sidebarToggle')?.addEventListener('click', function () {
        document.getElementById('sidebar').classList.toggle('show');
    });
</script>
<?= $this->renderSection('scripts') ?>
</body>
</html>
