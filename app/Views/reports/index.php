<?= $this->extend('layouts/app') ?><?= $this->section('content') ?>
<h4>Daily Reports <small class="text-muted"><?=$today?></small></h4>
<div class="row g-3">
<div class="col-md-3"><div class="card card-body"><div class="text-muted">Sales</div><h3>Rs. <?=number_format((float)($sales['total']??0),2)?></h3><div><?=$sales['count']??0?> posted transactions</div><div>Credit Rs. <?=number_format((float)($sales['credit']??0),2)?></div></div></div>
<div class="col-md-3"><div class="card card-body"><div class="text-muted">Purchases</div><h3>Rs. <?=number_format((float)($purchases['total']??0),2)?></h3><div><?=$purchases['count']??0?> posted transactions</div><div>Credit Rs. <?=number_format((float)($purchases['credit']??0),2)?></div></div></div>
<div class="col-md-3"><div class="card card-body"><div class="text-muted">Sales by Payment Mode</div><?php foreach($payments as $p):?><div class="d-flex justify-content-between"><span><?=esc($p['payment_mode'])?></span><strong>Rs. <?=number_format($p['amount'],2)?></strong></div><?php endforeach;?></div></div>
<div class="col-md-3"><div class="card card-body border-warning"><div class="text-muted">Security Deposits Held</div><h3>Rs. <?=number_format((float)$depositBalance,2)?></h3><div>Today received: Rs. <?=number_format((float)($deposits['held']??0),2)?></div><div>Today refunded: Rs. <?=number_format((float)($deposits['refunded']??0),2)?></div><div class="small text-muted mt-1">Refundable liability — not revenue</div></div></div>
</div>
<div class="mt-3 d-flex gap-2"><a class="btn btn-outline-primary" href="<?=site_url('inventory')?>">Daily Stock</a><a class="btn btn-outline-warning" href="<?=site_url('reports/custody')?>">Cylinder Custody & Deposits</a></div>
<?= $this->endSection() ?>