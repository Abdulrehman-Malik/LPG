<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php $companyName = trim((string)($companyName ?? env('app.companyName', 'Perfect LPG (Pvt.) LTD'))) ?: 'Perfect LPG (Pvt.) LTD'; ?>
    <title><?= esc(($title ?? 'Login') . ' — ' . $companyName) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@500;700;800&family=DM+Sans:wght@400;500;600;700&family=Noto+Nastaliq+Urdu:wght@500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('css/lpg-theme.css') ?>">
</head>
<body class="login-page font-system">
<svg width="0" height="0" style="position:absolute" aria-hidden="true">
    <defs>
        <linearGradient id="sv"><stop offset="0" stop-color="#8d9d97"/><stop offset=".22" stop-color="#e9f0ed"/><stop offset=".45" stop-color="#fff"/><stop offset=".78" stop-color="#bccac4"/><stop offset="1" stop-color="#74857e"/></linearGradient>
        <linearGradient id="gd"><stop offset="0" stop-color="#b9820d"/><stop offset=".5" stop-color="#f7d06b"/><stop offset="1" stop-color="#b9820d"/></linearGradient>
        <linearGradient id="em"><stop offset="0" stop-color="#04261e"/><stop offset=".4" stop-color="#0f7d5e"/><stop offset="1" stop-color="#04261e"/></linearGradient>
        <radialGradient id="sh"><stop offset="0" stop-opacity=".5"/><stop offset="1" stop-opacity="0"/></radialGradient>
    </defs>
</svg>
<?php
$shop = $shop ?? [];
$shopName = trim((string)($shop['name'] ?? '')) ?: 'Perfect LPG';
$shopPlace = trim(implode(', ', array_filter([trim((string)($shop['address'] ?? '')), trim((string)($shop['city'] ?? ''))])));
$shopPhone = trim((string)($shop['phone'] ?? ''));
$gateReady = (bool)(($migrationGate ?? ['ready' => true])['ready'] ?? false);
?>
<div class="wl">
    <header class="wl-top"><div class="wl-in">
        <div class="wl-brand"><span class="logo-mark"><i class="bi bi-fire"></i></span>Perfect LPG</div>
        <div class="wl-clock" aria-live="off"><b id="wlTime"><?= esc(date('g:i A')) ?></b><span id="wlDate"><?= esc(date('l, j F Y')) ?></span></div>
    </div></header>

    <main class="wl-main"><div class="wl-in">
        <section>
            <span class="wl-eyebrow"><i></i><?= esc($shopName) ?></span>
            <h1>Your shop. <em>Every cylinder</em> counted.</h1>
            <p class="wl-ur" lang="ur" dir="rtl">آپ کی دکان، آپ کا حساب۔</p>
            <div class="wl-card">
            <h2>Sign in</h2>
            <p class="wl-sub">Use the username your shop admin gave you.</p>
            <?php
            $migrationGate=$migrationGate ?? ['ready'=>true,'blocked_message'=>null,'migrations'=>[],'total'=>0,'completed'=>0];
            $migrationReady=(bool)($migrationGate['ready'] ?? false);
            $migrationRows=$migrationGate['migrations'] ?? [];
            ?>

            <?php if (session()->getFlashdata('migration_success')) : ?>
                <div class="alert alert-success d-flex align-items-center" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <div><?= esc(session()->getFlashdata('migration_success')) ?></div>
                </div>
            <?php endif; ?>

            <?php if (session()->getFlashdata('migration_error')) : ?>
                <div class="alert alert-danger d-flex align-items-start" role="alert">
                    <i class="bi bi-exclamation-octagon-fill me-2 mt-1"></i>
                    <div><?= esc(session()->getFlashdata('migration_error')) ?></div>
                </div>
            <?php endif; ?>

            <?php if (!$migrationReady) : ?>
                <div class="alert alert-warning d-flex align-items-start" role="alert">
                    <i class="bi bi-database-exclamation me-2 mt-1"></i>
                    <div>
                        <strong>Database update required before login.</strong>
                        <div class="small mt-1">
                            <?= esc($migrationGate['blocked_message'] ?? 'Pending database migrations must be executed before users can sign in.') ?>
                        </div>
                        <?php if (!empty($migrationGate['system_error'])) : ?>
                            <div class="small mt-1 text-danger"><?= esc($migrationGate['system_error']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($migrationRows) : ?>
                    <div class="table-responsive migration-list mb-3">
                        <table class="table table-sm table-bordered align-middle">
                            <thead class="table-light">
                            <tr>
                                <th style="width:70px">Seq</th>
                                <th>Migration</th>
                                <th style="width:120px">Status</th>
                                <th>Result</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($migrationRows as $m) : ?>
                                <?php
                                $status=(string)($m['status'] ?? 'pending');
                                $badge=[
                                    'success'=>'success',
                                    'failed'=>'danger',
                                    'running'=>'warning',
                                    'changed'=>'danger',
                                    'skipped'=>'warning',
                                    'pending'=>'secondary',
                                ][$status] ?? 'secondary';
                                ?>
                                <tr>
                                    <td><?= (int)$m['seq'] ?></td>
                                    <td class="small"><?= esc($m['file']) ?></td>
                                    <td><span class="badge text-bg-<?= $badge ?>"><?= esc(strtoupper($status)) ?></span></td>
                                    <td class="small"><?= esc($m['message'] ?? '') ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <form action="<?= site_url('migrations/run') ?>" method="post" onsubmit="this.querySelector('button[type=submit]').disabled=true;this.querySelector('button[type=submit]').innerHTML='<span class=&quot;spinner-border spinner-border-sm me-1&quot;></span> Executing...';">
                    <?= csrf_field() ?>
                    <button type="submit" class="gp-btn w-100">
                        <i class="bi bi-database-up me-1"></i>
                        Execute Pending Migrations
                    </button>
                </form>

                <div class="text-center text-muted small mt-3">
                    <?= (int)($migrationGate['completed'] ?? 0) ?> of <?= (int)($migrationGate['total'] ?? 0) ?> migrations completed.
                    The next migration is executed only after the previous one succeeds.
                </div>
            <?php endif; ?>

            <?php if ($migrationReady) : ?>
                <?php if (session()->getFlashdata('error')) : ?>
                    <div class="alert alert-danger d-flex align-items-center" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <div><?= esc(session()->getFlashdata('error')) ?></div>
                    </div>
                <?php endif; ?>

                <?php if (session()->getFlashdata('success')) : ?>
                    <div class="alert alert-success d-flex align-items-center" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <div><?= esc(session()->getFlashdata('success')) ?></div>
                    </div>
                <?php endif; ?>

                <?php if (session()->getFlashdata('errors')) : ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0 ps-3">
                            <?php foreach (session()->getFlashdata('errors') as $error) : ?>
                                <li><?= esc($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form action="<?= site_url('login') ?>" method="post" novalidate>
                    <?= csrf_field() ?>

                    <label for="login">Username or Email</label>
                    <input type="text" class="form-control" id="login" name="login"
                           value="<?= esc(old('login')) ?>" placeholder="e.g. admin" required autofocus>

                    <label for="password">Password</label>
                    <div class="password-field">
                        <input type="password" class="form-control" id="password" name="password"
                               placeholder="Enter password" autocomplete="current-password" required>
                        <button type="button" class="password-toggle" id="togglePassword"
                                aria-label="Show password" aria-pressed="false" aria-controls="password">
                            <i class="bi bi-eye" aria-hidden="true"></i>
                        </button>
                    </div>

                    <div class="login-meta">
                        <label class="remember-me" for="remember">
                            <input class="form-check-input" type="checkbox" id="remember" name="remember" value="1"> Keep me signed in
                        </label>
                    </div>

                    <button type="submit" class="gp-btn">Sign in</button>
                </form>
                <p class="wl-help">Need access? Ask your shop admin.</p>
            <?php endif; ?>
            </div>
        </section>

        <section class="wl-stage" aria-label="Shop">
            <div class="wl-glow"></div>
            <div class="wl-stat wl-s1"><small>Branch</small><b><?= esc($shopName) ?></b></div>
            <div class="wl-stat wl-s2"><small>System</small><b class="<?= $gateReady ? 'ok' : 'warn' ?>"><?= $gateReady ? 'Ready' : 'Update needed' ?></b></div>
            <div class="wl-cyls" role="img" aria-label="LPG cylinders">
                <div><?= view('partials/cylinder_svg', ['fill' => 78]) ?></div>
                <div><?= view('partials/cylinder_svg', ['fill' => 92]) ?></div>
                <div><?= view('partials/cylinder_svg', ['fill' => 35]) ?></div>
            </div>
            <div class="wl-floor"></div>
        </section>
    </div></main>

    <footer class="wl-foot"><div class="wl-in">
        <div class="grp">
            <?php if ($shopPlace !== '') : ?><span><b>Shop</b> <?= esc($shopPlace) ?></span><?php endif; ?>
            <?php if ($shopPhone !== '') : ?><span><b>Call</b> <a href="tel:<?= esc(preg_replace('/[^0-9+]/', '', $shopPhone), 'attr') ?>"><?= esc($shopPhone) ?></a></span><?php endif; ?>
            <span><b><?= esc($companyName) ?></b></span>
        </div>
        <span class="wl-health <?= $gateReady ? '' : 'bad' ?>"><i></i><?= $gateReady ? 'Database connected · Up to date' : 'Database update required' ?></span>
    </div></footer>
</div>
<script>
(function () {
    var t = document.getElementById('wlTime'), d = document.getElementById('wlDate');
    function tick() {
        var n = new Date();
        if (t) t.textContent = n.toLocaleTimeString('en-US', {hour: 'numeric', minute: '2-digit'});
        if (d) d.textContent = n.toLocaleDateString('en-GB', {weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'});
    }
    tick();
    setInterval(tick, 15000);
})();
</script>
<script>
(function () {
    var input = document.getElementById('password');
    var btn = document.getElementById('togglePassword');
    if (!input || !btn) return;
    btn.addEventListener('click', function () {
        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        btn.setAttribute('aria-pressed', show ? 'true' : 'false');
        btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        btn.firstElementChild.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
        input.focus();
    });
})();
</script>
</body>
</html>
