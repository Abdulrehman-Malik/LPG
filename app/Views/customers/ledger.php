<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<div class="d-flex justify-content-between"><div><h4><?=esc($customer['name'])?></h4><div class="text-muted"><?=esc($customer['phone']??'')?></div></div><a class="btn btn-outline-secondary" href="<?=site_url('customers')?>">Back</a></div>

<div class="row g-3 my-2">
  <div class="col-md-3"><div class="card"><div class="card-body"><small>Opening Balance</small><h5>Rs. <?=number_format((float)$customer['opening_balance'],2)?></h5></div></div></div>
  <div class="col-md-3"><div class="card"><div class="card-body"><small>Total Purchased</small><h5>Rs. <?=number_format((float)$customer['total_purchased'],2)?></h5></div></div></div>
  <div class="col-md-3"><div class="card"><div class="card-body"><small>Credit Due</small><h5>Rs. <?=number_format((float)$customer['credit_due'],2)?></h5></div></div></div>
  <div class="col-md-3"><div class="card border-warning"><div class="card-body"><small>Security Deposits Held</small><h5>Rs. <?=number_format((float)($customer['security_deposit_held']??0),2)?></h5><div class="small text-muted">Refundable liability</div></div></div></div>
</div>

<div class="card mb-3"><div class="card-header d-flex justify-content-between"><span>Active Cylinder Custody</span><span class="badge text-bg-warning"><?=count($custody)?> Cylinder(s)</span></div><div class="card-body table-responsive"><table class="table table-sm"><thead><tr><th>Cylinder</th><th>Type</th><th>Gas KG</th><th>Deposit Held</th><th>Issued</th><th>Status</th></tr></thead><tbody>
<?php foreach($custody as $r): ?><tr><td><?=esc($r['unit_code'])?></td><td><?=esc($r['cylinder_code'].' — '.$r['cylinder_name'])?></td><td><?=number_format((float)$r['gas_weight_kg'],2)?></td><td>Rs. <?=number_format((float)$r['deposit_amount'],2)?></td><td><?=esc($r['issued_at'])?></td><td><span class="badge text-bg-warning">Issued / Held</span></td></tr><?php endforeach; ?>
<?php if(!$custody): ?><tr><td colspan="6" class="text-muted">No cylinders currently on customer custody.</td></tr><?php endif; ?>
</tbody></table></div></div>

<div class="row g-4">
  <div class="col-lg-7"><div class="card"><div class="card-header">Sales / Transactions</div><div class="card-body table-responsive"><table class="table table-sm"><thead><tr><th>Date</th><th>No.</th><th>Type</th><th>Total</th><th>Credit</th><th>Deposit</th><th>Refund</th></tr></thead><tbody>
  <?php foreach($sales as $r): ?><tr><td><?=esc($r['transaction_at'])?></td><td><?=esc($r['sale_no'])?></td><td><?=esc(ucwords(str_replace('_',' ',$r['transaction_type'])))?></td><td>Rs. <?=number_format((float)$r['total_amount'],2)?></td><td>Rs. <?=number_format((float)$r['credit_amount'],2)?></td><td>Rs. <?=number_format((float)$r['security_deposit_amount'],2)?></td><td>Rs. <?=number_format((float)$r['security_deposit_refund_amount'],2)?></td></tr><?php endforeach; ?>
  </tbody></table></div></div></div>
  <div class="col-lg-5"><div class="card"><div class="card-header">Security Deposit Ledger</div><div class="card-body table-responsive"><table class="table table-sm"><thead><tr><th>Date</th><th>Entry</th><th>Amount</th><th>Note</th></tr></thead><tbody>
  <?php foreach($deposits as $r): ?><tr><td><?=esc($r['transaction_at'])?></td><td><?= $r['entry_type']==='hold'?'<span class="text-success">Deposit Held</span>':'<span class="text-danger">Deposit Refunded</span>' ?></td><td>Rs. <?=number_format((float)$r['amount'],2)?></td><td><?=esc($r['notes']??'')?></td></tr><?php endforeach; ?>
  <?php if(!$deposits): ?><tr><td colspan="4" class="text-muted">No security deposit entries.</td></tr><?php endif; ?>
  </tbody></table></div></div></div>
</div>

<div class="card mt-4"><div class="card-header">Customer Payments / Receipts</div><div class="card-body table-responsive"><table class="table table-sm"><thead><tr><th>Date</th><th>Receipt</th><th>Amount</th><th>Mode</th></tr></thead><tbody><?php foreach($receipts as $r): ?><tr><td><?=esc($r['receipt_at'])?></td><td><?=esc($r['receipt_no'])?></td><td>Rs. <?=number_format((float)$r['amount'],2)?></td><td><?=esc(ucfirst($r['payment_mode']))?></td></tr><?php endforeach; ?></tbody></table></div></div>
<?= $this->endSection() ?>