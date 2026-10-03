<?= $this->extend('layouts/app') ?><?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3"><div><h4 class="mb-1">Inventory</h4><div class="text-muted small">Current branch stock by gas and physical cylinder category.</div></div><a class="btn btn-primary" href="<?=site_url('inventory/adjustments')?>"><i class="bi bi-sliders2-vertical me-1"></i>Stock Adjustment & History</a></div>
<?php if(session()->getFlashdata('success')):?><div class="alert alert-success"><?=esc(session()->getFlashdata('success'))?></div><?php endif;?>
<?php if(session()->getFlashdata('error')):?><div class="alert alert-danger"><?=esc(session()->getFlashdata('error'))?></div><?php endif;?>
<div class="table-responsive"><table class="table table-sm table-striped align-middle"><thead><tr><th>Item</th><th>Stock</th></tr></thead><tbody><?php foreach($rows as $r):?><tr><td><?=esc($r['name'])?></td><td class="fw-semibold"><?=number_format((float)$r['stock'],3)?></td></tr><?php endforeach;?></tbody></table></div>
<?= $this->endSection() ?>
