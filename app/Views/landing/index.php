<?php
$companyName = trim((string)($companyName ?? 'Perfect LPG (Pvt.) LTD')) ?: 'Perfect LPG (Pvt.) LTD';
$brand = 'Perfect LPG';
$isLoggedIn = (bool)($isLoggedIn ?? false);
$signInUrl = site_url('login');
$dashboardUrl = $isLoggedIn ? site_url('dashboard') : site_url('login');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= esc($brand) ?> — LPG POS &amp; ERP</title>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@500;700;800&family=DM+Sans:wght@400;500;600&family=Noto+Nastaliq+Urdu:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= base_url('css/lpg-theme.css') ?>">
</head>
<body class="landing-page">
<svg width="0" height="0" style="position:absolute" aria-hidden="true">
    <defs>
        <linearGradient id="sv"><stop offset="0" stop-color="#8d9d97"/><stop offset=".22" stop-color="#e9f0ed"/><stop offset=".45" stop-color="#fff"/><stop offset=".78" stop-color="#bccac4"/><stop offset="1" stop-color="#74857e"/></linearGradient>
        <linearGradient id="gd"><stop offset="0" stop-color="#b9820d"/><stop offset=".5" stop-color="#f7d06b"/><stop offset="1" stop-color="#b9820d"/></linearGradient>
        <linearGradient id="em"><stop offset="0" stop-color="#04261e"/><stop offset=".4" stop-color="#0f7d5e"/><stop offset="1" stop-color="#04261e"/></linearGradient>
        <radialGradient id="sh"><stop offset="0" stop-opacity=".5"/><stop offset="1" stop-opacity="0"/></radialGradient>
    </defs>
</svg>

<section class="hero">
    <nav>
        <a class="brand" href="<?= site_url('/') ?>"><span class="logo-mark"><i class="bi bi-fire"></i></span><?= esc($brand) ?></a>
        <div class="l">
            <a href="#features">Features</a>
            <a href="#features">Pricing</a>
            <a href="#support">Support</a>
        </div>
        <a class="gp-btn" href="<?= esc($signInUrl) ?>" style="padding:10px 18px">Sign in</a>
    </nav>
    <div class="hg">
        <div>
            <h1>Run your LPG shop without a single register.</h1>
            <p class="s">Sales, cylinder stock, deposits, udhaar and daily cash — in one place. Built for Pakistani gas shops, from one counter to ten branches.</p>
            <a class="gp-btn" href="<?= esc($signInUrl) ?>">Sign in to your shop</a>
            <a class="gp-btn ghost" href="<?= esc($dashboardUrl) ?>">View dashboard</a>
            <div class="ur" lang="ur" dir="rtl">آپ کی دکان، آپ کا حساب۔</div>
        </div>
        <div class="stage" aria-hidden="true">
            <div><?= view('partials/cylinder_svg', ['fill' => 78]) ?></div>
            <div><?= view('partials/cylinder_svg', ['fill' => 92]) ?></div>
            <div><?= view('partials/cylinder_svg', ['fill' => 35]) ?></div>
            <div class="chip" style="top:20px;left:0">Sale saved<small>11.8 kg · Rs. 3,540</small></div>
            <div class="chip" style="top:120px;right:-6px;animation-delay:-2s">Low stock<small>6 kg cylinders: 3 left</small></div>
        </div>
    </div>
    <div class="prev" aria-hidden="true">
        <div class="pb"><i></i><i></i><i></i></div>
        <div class="pg">
            <div class="pm">
                <span class="a">Dashboard</span>
                <span>POS sales</span>
                <span>Inventory</span>
                <span>Udhaar</span>
                <span>Cash counter</span>
            </div>
            <div class="pc">
                <div class="pk">
                    <div>Today's sales<b>Rs. 1,48,200</b></div>
                    <div>Gas available<b>1,840 kg</b></div>
                    <div>Udhaar<b>Rs. 3,12,000</b></div>
                </div>
                <div class="pbars">
                    <i style="height:40%"></i><i style="height:60%"></i><i style="height:50%"></i>
                    <i style="height:75%"></i><i style="height:65%"></i><i style="height:55%"></i>
                    <i style="height:90%"></i>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="mq"><div>
    <span>POS sales</span><span>Cylinder tracking</span><span>Udhaar ledger</span><span>JazzCash</span><span>Easypaisa</span><span>Cash counter</span><span>Gas wastage</span><span>Urdu + English</span>
    <span>POS sales</span><span>Cylinder tracking</span><span>Udhaar ledger</span><span>JazzCash</span><span>Easypaisa</span><span>Cash counter</span><span>Gas wastage</span><span>Urdu + English</span>
</div></div>

<div class="sec" id="features">
    <h2>Everything a gas shop counts every day, counted for you.</h2>
    <div class="grid3">
        <div class="fc"><div class="ic">🧾</div><h3>Fast POS counter</h3><p>Sell by kg or by cylinder in three taps. Cash, JazzCash, Easypaisa or credit.</p></div>
        <div class="fc"><div class="ic">🛢️</div><h3>Cylinder tracking</h3><p>Know which cylinders are full, empty, or sitting with a customer.</p></div>
        <div class="fc"><div class="ic">📒</div><h3>Udhaar ledger</h3><p>See who owes what, send reminders by WhatsApp.</p></div>
        <div class="fc"><div class="ic">💰</div><h3>Cash counter</h3><p>Opening, closing and shortage — matched every night.</p></div>
        <div class="fc"><div class="ic">🔥</div><h3>Gas wastage</h3><p>Catch leakage and loss before it eats your margin.</p></div>
        <div class="fc"><div class="ic">📱</div><h3>Works on any phone</h3><p>Urdu and English, with the same shop records on every screen.</p></div>
    </div>
</div>

<footer class="landing-footer" id="support">© <?= date('Y') ?> <?= esc($companyName) ?> · Lahore, Pakistan</footer>
</body>
</html>
