<?= $this->extend('layouts/app') ?>
<?= $this->section('styles') ?>
<style>
.pos-workspace {
  display:grid !important;
  grid-template-columns:minmax(0,1fr) 310px;
  align-items:start;
  gap:1rem;
  margin-left:0 !important;
  margin-right:0 !important;
}
.pos-workspace > .pos-lines-panel,
.pos-workspace > .pos-summary-panel {
  width:auto !important;
  max-width:none !important;
  min-width:0;
  padding-left:0 !important;
  padding-right:0 !important;
  flex:none !important;
}
.pos-workspace.mode-security_deposit,
.pos-workspace.mode-cylinder_return {
  grid-template-columns:minmax(0,1fr) 330px;
}
.pos-workspace.mode-security_deposit > .pos-lines-panel,
.pos-workspace.mode-cylinder_return > .pos-lines-panel { min-width:0; }
#lines { width:100%; table-layout:fixed; }
#lines th,#lines td { padding:.65rem .5rem; vertical-align:middle; }
/* Each standard transaction type owns its own table geometry. Hidden source cells do not reserve width. */
#lines.gas-sale-mode:not(.source-enabled) th { padding:.32rem .28rem; font-size:.68rem; line-height:1.05; white-space:normal; overflow-wrap:anywhere; }
#lines.gas-sale-mode:not(.source-enabled) { min-width:720px; }
#lines.gas-sale-mode:not(.source-enabled) th:nth-child(1){width:22%}
#lines.gas-sale-mode:not(.source-enabled) th:nth-child(2){width:14%}
#lines.gas-sale-mode:not(.source-enabled) th:nth-child(3){width:20%}
#lines.gas-sale-mode:not(.source-enabled) th:nth-child(4){width:20%}
#lines.gas-sale-mode:not(.source-enabled) th:nth-child(5){width:16%; white-space:nowrap}
#lines.gas-sale-mode:not(.source-enabled) th:nth-child(6){width:8%; text-align:right; white-space:nowrap}
#lines.gas-sale-mode:not(.source-enabled) th:nth-child(7){width:8%; text-align:right; white-space:nowrap}
#lines.gas-sale-mode:not(.source-enabled) td:nth-child(5){width:16%; text-align:right; white-space:nowrap}
#lines.gas-sale-mode:not(.source-enabled) td:nth-child(6){width:8%; text-align:right; white-space:nowrap}
#lines.gas-sale-mode:not(.source-enabled) td:nth-child(7){width:8%; text-align:right; white-space:nowrap}
#lines.gas-sale-mode:not(.source-enabled) td:nth-child(7) .remove { min-width:0; min-height:28px; width:auto; font-size:.62rem; line-height:1; padding:.18rem .32rem; white-space:nowrap; }
#lines.gas-sale-mode:not(.source-enabled) th:nth-child(7) { padding-right:.15rem; }
#lines.gas-sale-mode:not(.source-enabled) th .gas-sale-header-wrap { display:inline-block; line-height:1.05; }
#lines.gas-sale-mode.source-enabled th { font-size:.68rem; line-height:1.05; }
#lines.gas-sale-mode.source-enabled th:nth-child(1){width:18%}
#lines.gas-sale-mode.source-enabled th:nth-child(2){width:21%}
#lines.gas-sale-mode.source-enabled th:nth-child(3){width:11%}
#lines.gas-sale-mode.source-enabled th:nth-child(4){width:13%}
#lines.gas-sale-mode.source-enabled th:nth-child(5){width:18%}
#lines.gas-sale-mode.source-enabled th:nth-child(6){width:13%}
#lines.gas-sale-mode.source-enabled th:nth-child(7){width:6%; text-align:right}
#lines.gas-sale-mode.source-enabled td:nth-child(7){text-align:right}
#lines.gas-sale-mode.source-enabled td:nth-child(7) .remove { min-width:0; min-height:28px; width:auto; font-size:.62rem; line-height:1; padding:.18rem .32rem; white-space:nowrap; }
#lines.cylinder-sale-mode th { font-size:.72rem !important; line-height:1.05; padding:.28rem .3rem; white-space:nowrap; }
#lines.cylinder-sale-mode th:nth-child(1){width:21%}
#lines.cylinder-sale-mode th:nth-child(2){width:16%}
#lines.cylinder-sale-mode th:nth-child(3){width:8%}
#lines.cylinder-sale-mode th:nth-child(4){width:8%}
#lines.cylinder-sale-mode th:nth-child(5){width:15%}
#lines.cylinder-sale-mode th:nth-child(6){width:15%}
#lines.cylinder-sale-mode th:nth-child(7){width:10%; text-align:right}
#lines.cylinder-sale-mode th:nth-child(8){width:7%; text-align:right}
#lines.cylinder-sale-mode td:nth-child(5),
#lines.cylinder-sale-mode td:nth-child(6) { min-width:0; }
#lines.cylinder-sale-mode .input-group { min-width:0; width:100%; }
#lines.cylinder-sale-mode .input-group-text { flex:0 0 auto; padding:.25rem .35rem; font-size:.72rem; }
#lines.cylinder-sale-mode .input-group .form-control { min-width:0; width:1%; }
#lines.cylinder-sale-mode td:nth-child(7) { text-align:right; white-space:nowrap; padding-left:.2rem; padding-right:.2rem; }
#lines.cylinder-sale-mode td:nth-child(8) { text-align:right; white-space:nowrap; padding-left:.15rem; padding-right:.15rem; }
#lines.cylinder-sale-mode td:nth-child(8) .remove { width:28px; height:28px; min-width:28px; min-height:28px; padding:0; font-size:.8rem; line-height:1; display:inline-flex; align-items:center; justify-content:center; }
#lines.gas-sale-mode .sourceCell { display:table-cell; }
#lines.gas-sale-mode:not(.source-enabled) .sourceCell { display:none; }
#lines.gas-sale-mode:not(.source-enabled) .sourceHead { display:none !important; }
#lines.cylinder-sale-mode .cylStatus { min-width:0; }
#lines.cylinder-sale-mode select { min-width:0; width:100%; }
#lines .form-select,#lines .form-control { min-height:42px; width:100%; min-width:0; }
#lines.gas-sale-mode .lineTotal { min-width:96px; text-align:right; }
#lines.gas-sale-mode .sourceCell select,
#lines.gas-sale-mode .targetCyl,
#lines.gas-sale-mode .cyl { min-width:0; width:100%; }
#lines .lineTotal { font-size:1.05rem; white-space:nowrap; }
.pos-lines-panel > .card > .card-body { padding: .6rem; }
.pos-summary-card .card-body { padding: .75rem; }
.pos-header-row { margin-bottom:.5rem !important; row-gap:.45rem !important; }
.pos-header-row .form-label { margin-bottom:.25rem; }
.pos-header-row .credit-status-row { margin-top:-.15rem; }
.pos-workspace,
.pos-workspace .form-control,
.pos-workspace .form-select,
.pos-workspace .btn,
.pos-workspace th,
.pos-workspace td,
.pos-workspace .input-group-text {
  font-size:var(--app-font-size) !important;
}
.pos-workspace .form-label,
.pos-workspace .form-text,
.pos-workspace .small {
  font-size:calc(var(--app-font-size) * .86) !important;
}
.pos-workspace h6 { font-size:calc(var(--app-font-size) * 1.05) !important; }
.pos-workspace .badge { font-size:calc(var(--app-font-size) * .75) !important; }
#standardTransaction { margin-top:0 !important; padding-top:0 !important; }
#standardTransaction .table-responsive { overflow-x:auto; }
#securityTransaction, #returnTransaction { width:100%; }
#securityTransaction .table-responsive, #returnTransaction .table-responsive { overflow-x:auto; }
#securityTransaction table, #returnTransaction table { width:100%; table-layout:fixed; }
#securityTransaction th, #securityTransaction td,
#returnTransaction th, #returnTransaction td { white-space:normal; overflow-wrap:anywhere; }
#securityTransaction th:nth-child(1){width:18%} #securityTransaction th:nth-child(2){width:28%}
#securityTransaction th:nth-child(3){width:10%} #securityTransaction th:nth-child(4){width:11%}
#securityTransaction th:nth-child(5){width:12%} #securityTransaction th:nth-child(6){width:12%} #securityTransaction th:nth-child(7){width:5%}
#returnTransaction th:nth-child(1){width:19%} #returnTransaction th:nth-child(2){width:11%}
#returnTransaction th:nth-child(3){width:14%} #returnTransaction th:nth-child(4){width:14%}
#returnTransaction th:nth-child(5){width:13%} #returnTransaction th:nth-child(6){width:13%} #returnTransaction th:nth-child(7){width:6%}
#securityTransaction .card-body, #returnTransaction .card-body { padding:.8rem; }
#securityTransaction .table td, #securityTransaction .table th,
#returnTransaction .table td, #returnTransaction .table th { padding:.45rem .4rem; }
.pos-workspace.mode-security_deposit #standardTransaction,
.pos-workspace.mode-cylinder_return #standardTransaction { display:none !important; }
.pos-workspace.mode-security_deposit #securityTransaction,
.pos-workspace.mode-cylinder_return #returnTransaction { min-width:0; }
.pos-workspace.mode-gas_sale #securityTransaction,
.pos-workspace.mode-gas_sale #returnTransaction,
.pos-workspace.mode-cylinder_sale #securityTransaction,
.pos-workspace.mode-cylinder_sale #returnTransaction { display:none !important; }
#standardTransaction .table-responsive { margin:0 !important; padding:0 !important; }
#lines { margin-top:0 !important; margin-bottom:.25rem !important; }
#lines thead { margin:0 !important; }
#lines thead th { padding-top:.2rem !important; padding-bottom:.2rem !important; }
#lines tbody tr:first-child td { padding-top:.2rem !important; }
.pos-header-row + #standardTransaction { margin-top:-.2rem !important; }
#lines thead th { padding:.35rem .4rem; line-height:1.15; font-size:.82rem; }
#lines tbody td { padding:.4rem .35rem; }
#lines .form-select,#lines .form-control { min-height:38px; }
#lines .input-group-sm > .form-control,#lines .input-group-sm > .input-group-text { min-height:34px; }
#lines.cylinder-sale-mode .input-group-sm > .form-control { min-width:0; }
.cylinderPickerRow { display:none; }
.cylinderPickerRow.is-visible { display:table-row; }
.cylinderPickerRow td { padding:.15rem .35rem !important; border-top:0 !important; }
.cylinderPicker { padding:.35rem !important; max-height:230px; overflow:auto; }
.cylinder-picker-title { font-size:.82rem; margin-bottom:.3rem !important; }
.cylinder-options { gap:.35rem !important; }
.cylinder-option { width:118px; min-width:118px !important; max-width:118px; min-height:44px; padding:.2rem .3rem !important; border-radius:.4rem !important; display:flex; align-items:center; gap:.25rem; }
.cylinder-option:hover { transform:translateY(-1px); }
.cylinder-option .cylinder-art { flex:0 0 24px; width:24px; height:30px; }
.cylinder-option .cylinder-copy { min-width:0; line-height:1.08; }
.cylinder-option .cylinder-code { display:block; font-size:.72rem; font-weight:800; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.cylinder-option .cylinder-name { display:block; font-size:.58rem; font-weight:700; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.cylinder-option .cylinder-gas { display:block; font-size:.56rem; margin-top:.15rem; white-space:nowrap; }
.cylinder-option .cylinder-status { font-size:.55rem; font-weight:700; }
.cylinder-option .filledUnitCheck { position:absolute; opacity:0; pointer-events:none; }
.cylinder-option:has(input:checked) { border-width:2px !important; }

.pos-summary-metrics { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:.5rem; margin-bottom:.75rem; }
.pos-summary-metric .form-label { font-size:.8rem; margin-bottom:.25rem; white-space:nowrap; }
.pos-summary-metric .form-control { padding:.35rem .5rem; min-height:36px; }
#lines .remove { min-width:38px; min-height:38px; }
.cylinderPickerRow td { background:#f8f9fa; }
.cylinder-option { cursor:pointer; background:#fff; transition:.15s ease; }
.cylinder-option:hover { box-shadow:0 .125rem .25rem rgba(0,0,0,.08); }
.cylinder-option:has(input:checked) { border-color:#0d6efd !important; background:#eef5ff; }
.cylinderPicker { max-height:300px; overflow:auto; }
.custody-list { max-height:250px; overflow:auto; }
.custody-list option { padding:4px; }
@media (max-width:1199.98px){
  .pos-workspace {
    grid-template-columns:minmax(0,1fr);
  }
  .pos-workspace.mode-security_deposit,
  .pos-workspace.mode-cylinder_return {
    grid-template-columns:minmax(0,1fr);
  }
  .pos-workspace > .pos-lines-panel,
  .pos-workspace > .pos-summary-panel {
    width:100% !important;
  }
}
input[type="number"]::-webkit-outer-spin-button,input[type="number"]::-webkit-inner-spin-button{ -webkit-appearance:none; margin:0; }
input[type="number"]{ -moz-appearance:textfield; appearance:textfield; }

/* POS visual refresh — no business logic changes. */
.pos-workspace{--pos-accent:var(--lpg-accent,#0f766e)}
.pos-workspace>.pos-lines-panel>.card,.pos-summary-card{box-shadow:0 4px 18px rgba(15,23,42,.055)}
.pos-header-row{background:#f8fafc;border:1px solid #e7edf3;border-radius:.65rem;padding:.7rem .7rem .35rem}
.pos-header-row .form-label{color:#334155}.pos-header-row .form-select,.pos-header-row .form-control{background:#fff}
#standardTransaction{padding-top:.35rem!important}#lines thead th{background:#f1f5f9;color:#475569;font-size:.76rem;font-weight:700;letter-spacing:.02em;text-transform:uppercase;border-bottom:1px solid #dbe3ec}
#lines tbody tr{border-bottom:1px solid #edf1f5}#lines tbody tr:hover{background:#f8fafc}
#lines .form-control,#lines .form-select{background:#fff;border-color:#cbd5e1}
#lines .lineTotal{font-weight:700;color:#172033}.pos-summary-card{position:sticky;top:5.2rem}.pos-summary-card .card-body{padding:1rem}
.pos-summary-metrics{gap:.6rem}.pos-summary-metric .form-control{border-color:#d7e0e8;background:#f8fafc!important;font-weight:650}
#saleTotal,#netPayable,#receiptAmountValue,#customerOsBalanceValue{font-variant-numeric:tabular-nums}
#saleTotal{color:#0f766e}#netPayable{color:#172033}#customerOsBalanceValue{color:#2563eb!important}
#paymentSection{border-top:1px solid #e5eaf0;padding-top:.8rem}#paymentPurposeLabel{color:#475569!important;text-transform:uppercase;letter-spacing:.04em;font-size:.72rem!important}
#securityTransaction .card,#returnTransaction .card{border:1px solid #dbe5ec!important;box-shadow:none}#securityTransaction .card{border-top:3px solid #d97706!important}#returnTransaction .card{border-top:3px solid #0e7490!important}
#securityTransaction .table thead th,#returnTransaction .table thead th{background:#f8fafc;color:#475569;font-size:.75rem;text-transform:uppercase;letter-spacing:.02em}
#issueEmpty,#returnEmpty{background:#f8fafc;border:1px dashed #cbd5e1;border-radius:.5rem;padding:.75rem!important;text-align:center}
.cylinder-option{border:1px solid #d5dee8!important;box-shadow:0 1px 3px rgba(15,23,42,.04)}.cylinder-option:hover{border-color:#94a3b8!important}.cylinder-option:has(input:checked){border-color:var(--pos-accent)!important;background:#ecfdf5;box-shadow:0 0 0 2px color-mix(in srgb,var(--pos-accent) 15%,transparent)}
#addLine,#openIssuePicker,#openReturnPicker{font-weight:600}#saveBtn{min-height:46px;font-size:1rem;box-shadow:0 5px 12px rgba(15,23,42,.1)}
@media(max-width:1199.98px){.pos-summary-card{position:static}}@media(max-width:767.98px){.pos-header-row{padding:.65rem .55rem .25rem}.pos-summary-metrics{grid-template-columns:1fr}.pos-workspace .btn{min-height:40px}#saveBtn{min-height:48px}}
</style>
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?php
$posVisibleTypes = (new \App\Models\ShopSettingsModel())->visibleTransactionTypes($shopSettings);
$initialTransactionType = in_array((string)$defaultTransactionType, $posVisibleTypes, true)
    ? (string)$defaultTransactionType
    : (string)($posVisibleTypes[0] ?? 'gas_sale');
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <?php if($cashSession): ?><div class="small text-success fw-semibold">Cash session: OPEN — <?=esc($cashSession["register_code"])?></div>
    <?php else: ?><div class="small text-warning fw-semibold">Cash session: NOT OPEN — open Counter Cash before cash sales</div><?php endif; ?>
  </div>
  <span class="badge text-bg-secondary">POS</span>
</div>
<div id="posValidationAlert" class="alert alert-danger d-none mb-3" role="alert"></div>

<form method="post" action="<?=site_url('sales/save')?>" id="saleForm"><?=csrf_field()?>
<input type="hidden" name="lines_json" id="lines_json">
<input type="hidden" name="payments_json" id="payments_json">
<input type="hidden" name="stock_override_confirmed" id="stock_override_confirmed" value="0">

<div class="row g-3 pos-workspace mode-<?= esc($initialTransactionType) ?>" id="posWorkspace">
<div class="col-lg-9 pos-lines-panel"><div class="card"><div class="card-body">

<div class="row g-2 pos-header-row mb-2">
  <div class="col-md-3">
    <label class="form-label fw-semibold">Transaction Type</label>
    <select name="transaction_type" id="transactionType" class="form-select">
      <?php foreach(\App\Models\ShopSettingsModel::TRANSACTION_TYPE_LABELS as $value=>$label): ?>
        <?php if (in_array($value, (new \App\Models\ShopSettingsModel())->visibleTransactionTypes($shopSettings), true)): ?>
          <option value="<?= esc($value) ?>" <?= $value === $initialTransactionType ? 'selected' : '' ?>><?= esc($label) ?></option>
        <?php endif; ?>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-3" id="gasEntryModeWrap" style="display:<?= $initialTransactionType === 'gas_sale' ? 'block' : 'none' ?>">
    <label class="form-label fw-semibold">Gas Entry</label>
    <select id="gasEntryMode" class="form-select"><option value="quantity">KG</option><option value="amount">Amount</option></select>
  </div>
  <div class="col-md-3">
    <label class="form-label">Customer</label>
    <select name="customer_id" id="customer_id" class="form-select">
      <option value="">Walk-in / Cash</option>
      <?php foreach($customers as $c): ?><option value="<?=$c['id']?>"><?=esc(($c['code']?$c['code'].' — ':'').$c['name'])?></option><?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-3">
    <label class="form-label">Transaction Time</label>
    <input name="transaction_at" type="datetime-local" class="form-control" value="<?=date('Y-m-d\TH:i')?>">
  </div>
  <div class="col-12">
    <div id="customerCreditStatus" class="small mt-1 text-muted">Walk-in / Cash: credit sale not allowed.</div>
  </div>
</div>

<div id="standardTransaction" style="display:<?= in_array($initialTransactionType,['gas_sale','cylinder_sale'],true) ? 'block' : 'none' ?>">
  <div class="table-responsive">
    <table class="table table-sm align-middle" id="lines">
      <thead id="lineHead"></thead>
      <tbody></tbody>
    </table>
  </div>
  <button type="button" class="btn btn-outline-primary" id="addLine">Add Line</button>
</div>

<div id="securityTransaction" style="display:<?= $initialTransactionType === 'security_deposit' ? 'block' : 'none' ?>">
  <div class="card border-warning">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-2"><h6 class="mb-0">Security Deposit / Issue Cylinder</h6><button type="button" class="btn btn-sm btn-primary" id="openIssuePicker">Add Line / Select Cylinders</button></div>
      <div class="small text-muted mb-2">Select physical cylinders by type and Filled / Empty. Filled includes partially-filled cylinders. The same Type + condition cannot be added twice.</div>
      <div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead><tr><th>Type / Status</th><th>Physical Cylinder(s)</th><th class="text-end">Gas KG</th><th class="text-end">Gas Rate</th><th class="text-end">Cylinder Rate</th><th class="text-end">Amount</th><th></th></tr></thead><tbody id="issueLinesBody"></tbody></table></div>
      <div id="issueEmpty" class="text-muted small py-3">No cylinders selected. Click Add Line / Select Cylinders.</div>
    </div>
  </div>
</div>

<div id="returnTransaction" style="display:<?= $initialTransactionType === 'cylinder_return' ? 'block' : 'none' ?>">
  <div class="card border-info">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-2"><h6 class="mb-0">Cylinder Return / Refund Deposit</h6><button type="button" class="btn btn-sm btn-primary" id="openReturnPicker">Select Cylinders</button></div>
      <div class="small text-muted mb-2">Only the selected customer's pending cylinders are available. Return gas is entered separately per cylinder.</div>
      <div class="row g-2 mb-3 align-items-end">
        <div class="col-md-4 col-lg-3"><label class="form-label mb-1 fw-semibold">Overall Return Gas Rate</label><input id="returnOverallRate" type="number" min="0" step="0.01" class="form-control form-control-sm"></div>
        <div class="col-auto"><button type="button" class="btn btn-sm btn-outline-primary" id="applyReturnRate">Apply to All</button></div>
      </div>
      <div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead><tr><th>Cylinder</th><th>Issued KG</th><th>Return KG</th><th>Return Rate</th><th>Consumed KG</th><th>Status</th><th></th></tr></thead><tbody id="returnLinesBody"></tbody></table></div>
      <div id="returnEmpty" class="text-muted small py-3">No cylinders selected. Click Select Cylinders.</div>
    </div>
  </div>
</div>

<div class="modal fade" id="issuePickerModal" tabindex="-1"><div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title">Select Cylinders to Issue</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <div class="row g-2 mb-3"><div class="col-md-5"><label class="form-label">Cylinder Type</label><select id="issuePickerType" class="form-select"><option value="">Select</option><?php foreach($types as $t): $unitsForType=array_values(array_filter($availableCustodyUnits ?? [], static fn($u)=>(int)($u['cylinder_type_id']??0)===(int)$t['id'])); $gasForType=(float)($filledStock[$t['id']]??0); $qtyForType=count($unitsForType); ?><option value="<?= (int)$t['id'] ?>"><?= esc($t['code'].' — '.$t['name'].' — Available Stock: '.number_format($gasForType,2).' KG ('.$qtyForType.' cylinder'.($qtyForType===1?'':'s').')') ?></option><?php endforeach; ?></select></div><div class="col-md-4"><label class="form-label">Filled / Empty</label><select id="issuePickerStatus" class="form-select"><option value="filled">Filled / Partially Filled</option><option value="empty">Empty</option></select></div><div class="col-md-3 d-flex align-items-end"><span class="small text-muted" id="issuePickerCount"></span></div></div>
    <div id="issuePickerCards" class="d-flex flex-wrap gap-2"></div>
  </div>
  <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-primary" id="confirmIssuePicker">OK / Add Line</button></div>
</div></div></div>

<div class="modal fade" id="returnPickerModal" tabindex="-1"><div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title">Select Customer Cylinders to Return</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <div class="row g-2 mb-3"><div class="col-md-5"><label class="form-label">Cylinder Type</label><select id="returnPickerType" class="form-select"><option value="">All Types</option><?php foreach($types as $t): ?><option value="<?= (int)$t['id'] ?>"><?= esc($t['code'].' — '.$t['name']) ?></option><?php endforeach; ?></select></div><div class="col-md-4"><label class="form-label">Filled / Empty</label><select id="returnPickerStatus" class="form-select"><option value="">All</option><option value="filled">Filled / Partially Filled</option><option value="empty">Empty</option></select></div><div class="col-md-3 d-flex align-items-end"><span class="small text-muted" id="returnPickerCount"></span></div></div>
    <div id="returnPickerCards" class="d-flex flex-wrap gap-2"></div>
  </div>
  <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-primary" id="confirmReturnPicker">OK / Add Selected</button></div>
</div></div></div></div>

</div></div>

<div class="col-lg-3 pos-summary-panel"><div class="card pos-summary-card"><div class="card-body">
<div class="pos-summary-metrics">
  <div class="pos-summary-metric"><label class="form-label fw-semibold">Current Sale</label><div class="form-control bg-light fw-semibold"><span id="saleTotal">0.00</span></div></div>
  <div class="pos-summary-metric"><label class="form-label">OS Balance</label><div class="form-control bg-light"><span id="previousOs">0.00</span></div></div>
  <div class="pos-summary-metric"><label class="form-label">Discount</label><input name="discount_amount" id="discount" type="number" min="0" step="any" value="0" class="form-control"></div>
</div>
<div class="mb-3" id="securityDepositBox" style="display:<?= $initialTransactionType === 'security_deposit' ? 'block' : 'none' ?>">
  <label class="form-label fw-semibold">Security Deposit Amount</label>
  <input name="security_deposit_amount" id="securityDeposit" type="number" min="0" step="any" value="0" class="form-control">
</div>
<div class="mb-3">
  <label class="form-label">Net Receivable Amount</label>
  <div class="form-control bg-light fw-bold"><span id="netPayable">0.00</span></div>
</div>
<div class="mb-3"><label class="form-label">Receipt Amount</label><div class="form-control bg-light fw-bold"><span id="receiptAmountValue">0.00</span></div></div>
<div class="mb-3"><label class="form-label fw-semibold">OS Balance</label><div class="form-control bg-light fw-bold text-primary"><span id="customerOsBalanceValue">0.00</span></div></div>
<div class="mb-3" id="refundBox" style="display:<?= $initialTransactionType === 'cylinder_return' ? 'block' : 'none' ?>"><label class="form-label fw-semibold">Security Deposit Refund</label><input name="security_deposit_refund_amount" id="refundAmount" type="number" min="0" step="0.01" value="0" class="form-control"><div class="form-text">Refundable deposit balance: Rs. <span id="refundAvailable">0.00</span></div></div>

<div id="paymentSection" style="display:<?= $initialTransactionType === 'cylinder_return' ? 'none' : 'block' ?>">
  <div id="paymentPurposeLabel" class="small fw-semibold text-muted mb-2">Sale Payment</div>
  <div id="payments"></div>
  <button type="button" class="btn btn-outline-secondary mb-3" id="addPayment">Add Payment</button>
</div>

<textarea name="notes" class="form-control mb-3" placeholder="Notes"></textarea>
<button class="btn btn-primary w-100" id="saveBtn">Post Transaction</button>
</div></div></div>
</div>
</form>

<script>
const types=<?=json_encode(array_values($types),JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP)?>;
const rates=<?=json_encode($cylinderRates)?>,kgRate=<?=json_encode($kgRate)?>;
const filledStock=<?=json_encode($filledStock)?>,filledUnits=<?=json_encode($filledUnits)?>,emptyStock=<?=json_encode($emptyStock)?>,gasStock=<?=json_encode($gasStock)?>;
const balances=<?=json_encode($balances)?>,creditLimits=<?=json_encode($creditLimits)?>,creditSaleAllowed=<?=json_encode(array_reduce($customers,static function($out,$customer){$out[(string)$customer['id']]=(int)($customer['allow_credit_sale']??0);return $out;},[]))?>;
const creditLimitMode=<?=json_encode($creditLimitMode??'none')?>,shopCreditLimit=<?=json_encode((float)($shopCreditLimit??0))?>,shopOutstanding=<?=json_encode((float)($shopOutstanding??0))?>;
const availableCustodyUnits=<?=json_encode($availableCustodyUnits)?>,allCustomerCustody=<?=json_encode($custodyUnits)?>;
const allowPosSourceCylinderSelection=<?=json_encode((int)($allowPosSourceCylinderSelection??0))?>===1;
const defaultTransactionType=<?=json_encode($defaultTransactionType??'gas_sale')?>,defaultPaymentMode=<?=json_encode($shopSettings['default_payment_mode']??'cash')?>;
const tbody=document.querySelector('#lines tbody'),lineHead=document.getElementById('lineHead'),payments=document.getElementById('payments');

function transactionType(){return document.getElementById('transactionType').value;}
function selectedCustomerId(){return document.getElementById('customer_id').value;}
function selectedCustomer(){const id=selectedCustomerId();return id?{balance:Number(balances[id]||0),limit:Number(creditLimits[id]||0),allowCredit:String(creditSaleAllowed[String(id)]??'0')==='1'}:null;}
function refreshCustomer(){
 const c=selectedCustomer(),box=document.getElementById('customerCreditStatus'),osBox=document.getElementById('customerOsBalanceValue');
 if(osBox)osBox.textContent=(c?Math.max(0,c.balance):0).toFixed(2);
 if(!box)return c;
 if(!c){box.className='small mt-1 text-danger';box.textContent='Walk-in / Cash: credit sale not allowed.';return c;}
 if(!c.allowCredit){box.className='small mt-1 text-danger';box.textContent='Credit Sale: Not Allowed — Enable Allow Credit Sale on the customer record.';return c;}
 if(creditLimitMode==='none'){
   if(c.limit>0){
     const available=Math.max(0,c.limit-c.balance);
     if(available<=0){box.className='small mt-1 text-danger';box.textContent='Credit Sale: Not Allowed — Customer credit limit reached.';return c;}
     box.className='small mt-1 text-success';box.textContent='Credit Sale: Allowed — Credit Limit: '+c.limit.toFixed(2)+' | Available: '+available.toFixed(2);return c;
   }
   box.className='small mt-1 text-success';box.textContent='Credit Sale: Allowed — Credit Limit: Unlimited';return c;
 }
 if(creditLimitMode==='shop'){const available=Math.max(0,shopCreditLimit-shopOutstanding);if(available<=0){box.className='small mt-1 text-danger';box.textContent='Credit Sale: Not Allowed — Shop credit limit reached.';return c;}box.className='small mt-1 text-success';box.textContent='Credit Sale: Allowed — Credit Limit: '+shopCreditLimit.toFixed(2)+' | Available: '+available.toFixed(2);return c;}
 const available=Math.max(0,c.limit-c.balance);if(available<=0){box.className='small mt-1 text-danger';box.textContent='Credit Sale: Not Allowed — Customer credit limit reached.';return c;}box.className='small mt-1 text-success';box.textContent='Credit Sale: Allowed — Credit Limit: '+c.limit.toFixed(2)+' | Available: '+available.toFixed(2);return c;
}
function companyUnitsFor(status,typeId){
  return availableCustodyUnits.filter(u=>(!status||u.status===status)&&(!typeId||String(u.cylinder_type_id)===String(typeId)));
}
function custodyOptions(){
  return '<option value="">Select customer cylinder (optional)</option>'+allCustomerCustody.filter(u=>String(u.customer_id)===String(selectedCustomerId())).map(u=>'<option value="'+u.unit_id+'">'+u.unit_code+' — '+u.cylinder_code+' — '+u.cylinder_name+' — '+Number(u.gas_weight_kg||0).toFixed(2)+' KG — Deposit '+Number(u.deposit_amount||0).toFixed(2)+'</option>').join('');
}
function refillLineOptions(typeId){
  return '<option value="">Select customer cylinder (optional)</option>'+allCustomerCustody.filter(u=>String(u.customer_id)===String(selectedCustomerId())&&(!typeId||String(u.cylinder_type_id)===String(typeId))).map(u=>'<option value="'+u.unit_id+'">'+u.unit_code+' — '+u.cylinder_code+' — '+Number(u.gas_weight_kg||0).toFixed(2)+' KG</option>').join('');
}
function cylinderTypeOptions(){
  return '<option value="">Select</option>'+types.map(t=>{
    const units=filledUnits[t.id]||[];
    const gas=units.reduce((s,u)=>s+Number(u.gas_weight_kg||0),0);
    const stock=units.length;
    return '<option value="'+t.id+'">'+t.code+' — '+t.name+' — Available Stock: '+gas.toFixed(2)+' KG ('+stock+' cylinder'+(stock===1?'':'s')+')</option>';
  }).join('');
}
function clearLines(){tbody.innerHTML='';}
function escapeHtml(v){return String(v??'').replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));}
function filledSourceOptions(typeId){
  const units=(filledUnits[typeId]||[]).filter(u=>Number(u.gas_weight_kg||0)>0.00001);
  return '<option value="">Select source filled cylinder</option>'+units.map(u=>'<option value="'+u.id+'">'+escapeHtml(u.unit_code)+' — '+escapeHtml(u.cylinder_code)+' — '+escapeHtml(u.cylinder_name)+' — '+Number(u.gas_weight_kg||0).toFixed(2)+' KG</option>').join('');
}
function refreshGasSourceLine(tr, resetSource=true){
  const typeId=tr.querySelector('.cyl').value,source=tr.querySelector('.sourceCyl'),qty=tr.querySelector('.qty');
  const previous=resetSource?'':source.value;
  source.innerHTML=filledSourceOptions(typeId);
  source.value=previous;
  source.disabled=!typeId;
  const unit=(filledUnits[typeId]||[]).find(u=>String(u.id)===String(source.value));
  if(unit){
    const max=Number(unit.gas_weight_kg||0);
    qty.max=max;
    if(tr.dataset.entryMode!=='amount' && Number(qty.value||0)>max)qty.value=max;
  }else{
    qty.removeAttribute('max');
  }
}
function refreshDuplicateTypeOptions(){
 const selected=[...tbody.querySelectorAll('.cyl')].map(s=>s.value).filter(Boolean);
 tbody.querySelectorAll('.cyl').forEach(select=>{const own=select.value;[...select.options].forEach(o=>{o.disabled=!!o.value&&o.value!==own&&selected.includes(o.value);});});
}
function addGasLine(){
 const tr=document.createElement('tr');
 tr.innerHTML='<td><select class="form-select cyl">'+cylinderTypeOptions()+'</select></td><td class="sourceCell" style="display:'+(allowPosSourceCylinderSelection?'table-cell':'none')+'"><select class="form-select sourceCyl" disabled><option value="">Select source filled cylinder</option></select></td><td><input class="form-control entryValue" type="number" min="0" step="any" value="0" placeholder="KG"><input class="qty" type="hidden" value="0"><input class="enteredAmount" type="hidden"></td><td><input class="form-control form-control-sm gasRate" type="number" min="0" step="any" aria-label="Gas Rate"></td><td><select class="form-select targetCyl" disabled></select></td><td class="lineTotal">0.00</td><td><button type="button" class="btn btn-sm btn-outline-danger remove">Delete</button></td>';
 const mode=()=>document.getElementById('gasEntryMode').value;
 tr.dataset.entryMode=mode();tr.querySelector('.gasRate').value=kgRate!==null?Number(kgRate).toFixed(2):'';tr.querySelector('.gasRate').disabled=mode()==='amount';
 tr.querySelector('.cyl').onchange=()=>{refreshDuplicateTypeOptions();refreshGasSourceLine(tr,true);const t=tr.querySelector('.targetCyl');t.disabled=!selectedCustomerId();t.innerHTML=refillLineOptions(tr.querySelector('.cyl').value);recalc();};
 tr.querySelector('.entryValue').oninput=()=>{const m=mode(),v=Number(tr.querySelector('.entryValue').value||0),rate=Number(tr.querySelector('.gasRate').value||0);tr.dataset.entryMode=m;if(m==='amount'){tr.querySelector('.enteredAmount').value=v>0?v.toFixed(2):'';tr.querySelector('.qty').value=rate>0&&v>0?(v/rate).toFixed(3):'0';}else{tr.querySelector('.qty').value=v;tr.querySelector('.enteredAmount').value=rate>0&&v>0?(v*rate).toFixed(2):'';}refreshGasSourceLine(tr,false);recalc();};
 tr.querySelector('.gasRate').oninput=()=>{if(mode()==='amount')return;const rate=Number(tr.querySelector('.gasRate').value||0),q=Number(tr.querySelector('.qty').value||0);tr.querySelector('.enteredAmount').value=rate>0&&q>0?(q*rate).toFixed(2):'';recalc();};
 tr.querySelector('.targetCyl').innerHTML=refillLineOptions('');tr.querySelector('.targetCyl').disabled=!selectedCustomerId();tr.querySelector('.sourceCyl').onchange=()=>recalc();tr.querySelector('.remove').onclick=()=>{tr.remove();recalc();};tbody.appendChild(tr);
 tr.querySelector('.sourceCell').style.display=allowPosSourceCylinderSelection?'':'none';refreshGasSourceLine(tr,true);refreshDuplicateTypeOptions();
}
function rebuildLines(){
 clearLines();const t=transactionType();lineHead.innerHTML='';document.getElementById('lines').classList.remove('gas-sale-mode','cylinder-sale-mode','source-enabled');
 if(t==='gas_sale'){document.getElementById('lines').classList.add('gas-sale-mode');if(allowPosSourceCylinderSelection)document.getElementById('lines').classList.add('source-enabled');lineHead.innerHTML='<tr><th>Cylinder Type</th><th class="sourceHead" style="display:'+(allowPosSourceCylinderSelection?'table-cell':'none')+'">Source Filled Cylinder</th><th>Qty / KG</th><th>Gas Rate</th><th><span class="gas-sale-header-wrap">Customer<br>Cylinder</span></th><th>Amount</th><th>Action</th></tr>';addGasLine();document.getElementById('addLine').style.display='inline-block';}
 else if(t==='cylinder_sale'){document.getElementById('lines').classList.add('cylinder-sale-mode');lineHead.innerHTML='<tr><th>Cylinder Type</th><th>Status</th><th>Qty</th><th>Gas KG</th><th>Gas Rate</th><th>Cylinder Rate</th><th>Amount</th><th></th></tr>';addCylinderSaleLine();document.getElementById('addLine').style.display='inline-block';}
 else {document.getElementById('addLine').style.display='none';}
}
function refreshPaymentModes(){const walkIn=!selectedCustomerId(),customer=selectedCustomer();payments.querySelectorAll('.payment').forEach(p=>{const m=p.querySelector('.mode');[...m.options].forEach(o=>o.disabled=walkIn&&o.value!=='cash'||(!walkIn&&!customer?.allowCredit&&o.value==='credit'));if(walkIn||(!customer?.allowCredit&&m.value==='credit'))m.value='cash';});}
function paymentTypeOptions(){
 const t=transactionType();
 if(t==='security_deposit')return '<option value="security_deposit">Security Deposit</option><option value="sale">Gas / Cylinder</option>';
 if(t==='cylinder_return')return '<option value="security_deposit_refund">Security Deposit Refund</option>';
 return '<option value="sale">Sale</option>';
}
function addPayment(paymentType){
 const div=document.createElement('div');div.className='input-group mb-2 payment';
 div.innerHTML='<select class="form-select paymentType" style="max-width:170px">'+paymentTypeOptions()+'</select><select class="form-select mode"><option value="cash">Cash</option><option value="cheque">Cheque</option><option value="online">Online</option><option value="credit">Credit</option></select><input class="form-control amount" type="number" min="0.01" step="any" placeholder="Amount"><input class="form-control ref" placeholder="Ref"><button type="button" class="btn btn-outline-danger remove">×</button>';
 payments.appendChild(div);
 const type=paymentType||((transactionType()==='security_deposit')?'security_deposit':(transactionType()==='cylinder_return'?'security_deposit_refund':'sale'));
 div.querySelector('.paymentType').value=type;
 div.querySelector('.paymentType').onchange=recalc;
 div.querySelector('.mode').value=defaultPaymentMode;
 div.querySelector('.mode').onchange=()=>{refreshPaymentModes();recalc();};
 div.querySelector('.amount').oninput=recalc;div.querySelector('.remove').onclick=()=>{div.remove();recalc();};refreshPaymentModes();
}
function paymentTotal(type=null){return [...payments.querySelectorAll('.payment')].reduce((s,p)=>{const pt=p.querySelector('.paymentType')?.value||'sale';return s+(type===null||pt===type?Number(p.querySelector('.amount').value||0):0);},0);}
function statusForCylinderSale(tr){return tr.querySelector('.cylStatus')?.value||'empty';}
function cylinderTypeById(typeId){return types.find(t=>String(t.id)===String(typeId))||null;}
function selectedFilledUnits(tr){const ids=Array.isArray(tr._selectedUnitIds)?tr._selectedUnitIds:[],typeId=tr.querySelector('.cyl').value,available=filledUnits[typeId]||[];return ids.map(id=>available.find(u=>String(u.id)===String(id))).filter(Boolean);}
function renderFilledCylinderPicker(tr){const typeId=tr.querySelector('.cyl').value,picker=tr._pickerRow.querySelector('.cylinderPicker'),status=tr.querySelector('.cylStatus').value;if(status!=='filled'||!typeId){picker.innerHTML='<div class="text-muted small">Select a cylinder type to view available physical cylinders.</div>';return;}const units=(filledUnits[typeId]||[]).filter(u=>Number(u.gas_weight_kg||0)>0.00001);if(!units.length){picker.innerHTML='<div class="alert alert-warning py-2 mb-0">No filled/partially filled cylinders are currently available for this cylinder type.</div>';tr._selectedUnitIds=[];return;}const selected=new Set((tr._selectedUnitIds||[]).map(String));picker.innerHTML='<div class="d-flex justify-content-between align-items-center cylinder-picker-title"><strong>Select physical cylinder(s)</strong><span class="small text-muted">'+units.length+' available</span></div><div class="d-flex flex-wrap cylinder-options">'+units.map(u=>{const checked=selected.has(String(u.id))?' checked':'';const gas=Number(u.gas_weight_kg||0),cap=Number(u.capacity_kg||0);const statusLabel=gas>=cap-0.00001?'Filled':'Partially Filled';const statusClass=statusLabel==='Filled'?'text-success':'text-warning';return '<label class="btn btn-sm btn-outline-secondary text-start cylinder-option position-relative"><input class="filledUnitCheck" type="checkbox" value="'+u.id+'"'+checked+'><svg class="cylinder-art" viewBox="0 0 40 50" aria-hidden="true"><rect x="9" y="6" width="22" height="38" rx="7" fill="currentColor" opacity=".12"></rect><rect x="11" y="8" width="18" height="34" rx="6" fill="none" stroke="currentColor" stroke-width="2"></rect><path d="M15 8V4h10v4M17 4V1h6v3" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path><path d="M14 14h12M14 18h12" stroke="currentColor" stroke-width="1.5" opacity=".65"></path></svg><span class="cylinder-copy"><span class="cylinder-code">'+escapeHtml(u.cylinder_code||u.unit_code||'Cylinder')+'</span><span class="cylinder-name">'+escapeHtml(u.cylinder_name||'')+'</span><span class="cylinder-gas"><strong>'+gas.toFixed(3)+' / '+cap.toFixed(3)+' KG</strong> <span class="cylinder-status '+statusClass+'">'+statusLabel+'</span></span></span></label>';}).join('')+'</div>';picker.querySelectorAll('.filledUnitCheck').forEach(cb=>cb.onchange=()=>{tr._selectedUnitIds=[...picker.querySelectorAll('.filledUnitCheck:checked')].map(x=>Number(x.value));syncFilledCylinderLine(tr);});}
function syncFilledCylinderLine(tr){if(statusForCylinderSale(tr)!=='filled'){recalc();return;}const selected=selectedFilledUnits(tr),qty=tr.querySelector('.qty'),gas=tr.querySelector('.gasQty');qty.value=selected.length;gas.value=selected.reduce((s,u)=>s+Number(u.gas_weight_kg||0),0).toFixed(3);recalc();}
function addCylinderSaleLine(){const tr=document.createElement('tr');tr._selectedUnitIds=[];tr.innerHTML='<td><select class="form-select cyl">'+cylinderTypeOptions()+'</select></td><td><select class="form-select cylStatus"><option value="filled">Filled / Partially Filled</option><option value="empty">Empty</option></select></td><td><input class="form-control qty" type="number" min="0" step="1" value="0"></td><td><input class="form-control gasQty" type="number" value="0.000" readonly></td><td><div class="input-group input-group-sm gasWrap"><span class="input-group-text">Rs/KG</span><input class="form-control gasRate" type="number" min="0" step="any"></div></td><td><div class="input-group input-group-sm"><span class="input-group-text">Rs</span><input class="form-control cylRate" type="number" min="0" step="any"></div></td><td class="lineTotal">0.00</td><td><button type="button" class="btn btn-sm btn-outline-danger remove">×</button></td>';const pickerRow=document.createElement('tr');pickerRow.className='cylinderPickerRow';pickerRow.innerHTML='<td colspan="8"><div class="bg-light border rounded p-1 cylinderPicker"><div class="text-muted small">Select a cylinder type to view available physical cylinders.</div></div></td>';tr.querySelector('.cyl').onchange=()=>{tr._selectedUnitIds=[];refreshCylinderSaleLine.call(tr);};tr.querySelector('.cylStatus').onchange=()=>{tr._selectedUnitIds=[];refreshCylinderSaleLine.call(tr);};tr.querySelector('.qty').oninput=recalc;tr.querySelector('.gasRate').oninput=recalc;tr.querySelector('.cylRate').oninput=recalc;tr.querySelector('.remove').onclick=()=>{tr.remove();pickerRow.remove();refreshDuplicateTypeOptions();recalc();};tbody.appendChild(tr);tbody.appendChild(pickerRow);tr._pickerRow=pickerRow;refreshCylinderSaleLine.call(tr);}
function refreshCylinderSaleLine(){const tr=this.tagName==='TR'?this:tbody.querySelector('tr:not(.cylinderPickerRow):last-child'),typeId=tr.querySelector('.cyl').value,status=tr.querySelector('.cylStatus').value,type=cylinderTypeById(typeId),pickerRow=tr._pickerRow,picker=pickerRow.querySelector('.cylinderPicker');pickerRow.classList.toggle('is-visible',!!typeId);tr.querySelector('.gasWrap').style.display=status==='filled'?'flex':'none';tr.querySelector('.gasRate').disabled=status!=='filled';tr.querySelector('.gasRate').value=status==='filled'&&kgRate!==null?Number(kgRate).toFixed(2):'';const configuredCylinderPrice=Number(rates[typeId]??0);const masterCylinderPrice=Number(type?.empty_cylinder_price??0);const defaultCylinderPrice=configuredCylinderPrice>0?configuredCylinderPrice:(masterCylinderPrice>0?masterCylinderPrice:0);tr.querySelector('.cylRate').value=defaultCylinderPrice.toFixed(2);if(status==='filled'){tr.querySelector('.qty').readOnly=true;tr.querySelector('.gasQty').title='Calculated from selected physical cylinder(s).';renderFilledCylinderPicker(tr);}else{tr.querySelector('.qty').readOnly=false;tr.querySelector('.qty').max=Number(emptyStock[typeId]||0);if(Number(tr.querySelector('.qty').value||0)>Number(emptyStock[typeId]||0))tr.querySelector('.qty').value=Number(emptyStock[typeId]||0);tr.querySelector('.gasQty').value='0.000';picker.innerHTML='<div class="text-muted small">Empty cylinder sale: enter quantity only. Gas stock is not affected.</div>';}syncFilledCylinderLine(tr);}
function gasForCylinderSale(tr){
 const status=statusForCylinderSale(tr),typeId=tr.querySelector('.cyl').value;
 if(status!=='filled'||!typeId)return 0;
 return selectedFilledUnits(tr).reduce((sum,u)=>sum+Number(u.gas_weight_kg||0),0);
}
function recalc(){
 let saleTotal=0,gasRequired=0;
 const t=transactionType();
 const usedByType={};
 tbody.querySelectorAll('tr:not(.cylinderPickerRow)').forEach(tr=>{
   const qty=Math.max(0,Number(tr.querySelector('.qty')?.value||0));
   let amount=0,gas=0;
   if(t==='gas_sale'){gas=qty;const typeId=tr.querySelector('.cyl').value;const mode=document.getElementById('gasEntryMode')?.value||'quantity';if(mode==='amount'){const amountEntered=Number(tr.querySelector('.enteredAmount')?.value||0);const rate=Number(tr.querySelector('.gasRate')?.value||0);gas=rate>0&&amountEntered>0?amountEntered/rate:0;tr.querySelector('.qty').value=gas>0?gas.toFixed(3):'0';}else{tr.querySelector('.enteredAmount').value=Number(tr.querySelector('.qty').value||0)>0&&Number(tr.querySelector('.gasRate').value||0)>0?(Number(tr.querySelector('.qty').value)*Number(tr.querySelector('.gasRate').value)).toFixed(2):'';}const available=(filledUnits[typeId]||[]).reduce((sum,u)=>sum+Number(u.gas_weight_kg||0),0);const prior=Number(usedByType[typeId]||0);const remaining=Math.max(0,available-prior);tr.querySelector('.availableGas')?.replaceChildren(document.createTextNode(remaining.toFixed(2)+' KG'));tr.querySelector('.qty').max=Math.max(0,remaining);if(gas>remaining && tr.dataset.entryMode!=='amount'){tr.querySelector('.qty').value=remaining;gas=remaining;tr.querySelector('.enteredAmount').value=(remaining*Number(tr.querySelector('.gasRate').value||0)).toFixed(2);}if(gas>remaining && tr.dataset.entryMode==='amount'){tr.querySelector('.entryValue').setCustomValidity('Amount exceeds available gas stock.');}else{tr.querySelector('.entryValue').setCustomValidity('');}usedByType[typeId]=(prior+gas);const rate=Number(tr.querySelector('.gasRate').value||0);amount=gas*rate;}
   else if(t==='cylinder_sale'){gas=gasForCylinderSale(tr);const gr=Number(tr.querySelector('.gasRate').value||0),cr=Number(tr.querySelector('.cylRate').value||0),q=Math.floor(qty);amount=statusForCylinderSale(tr)==='filled'?gas*gr+q*cr:q*cr;}
   gasRequired+=gas;saleTotal+=amount;tr.querySelector('.lineTotal').textContent=''+amount.toFixed(2);
 });
 if(t==='security_deposit'){
   issueLines.forEach(l=>{const gas=Number(l.gas_weight_kg||0),q=(l.selected_cylinder_unit_ids||[]).length;saleTotal+=l.status==='filled'?(gas*Number(l.gas_rate||0)+q*Number(l.cylinder_rate||0)):q*Number(l.cylinder_rate||0);gasRequired+=gas;});
 }else if(t==='cylinder_return'){
   returnLines.forEach(l=>{gasRequired+=Number(l.return_gas_kg||0);});
 }
 const discount=Math.max(0,Number(document.getElementById('discount').value||0));saleTotal=Math.max(0,saleTotal-discount);
 const customer=selectedCustomer(),previousOs=customer?Math.max(0,customer.balance):0;
 const deposit=t==='security_deposit'?Math.max(0,Number(document.getElementById('securityDeposit').value||0)):0;
 const netReceivable=t==='security_deposit'?deposit:(t==='cylinder_return'?0:(saleTotal+previousOs));
 document.getElementById('saleTotal').textContent=saleTotal.toFixed(2);
 document.getElementById('previousOs').textContent=previousOs.toFixed(2);
 document.getElementById('netPayable').textContent=netReceivable.toFixed(2);
 const depositPaid=t==='security_deposit'?paymentTotal('security_deposit'):0;
 const salePaid=t==='security_deposit'?paymentTotal('sale'):(t==='cylinder_return'?0:paymentTotal('sale'));
 const refundPaid=t==='cylinder_return'?paymentTotal('security_deposit_refund'):0;
 const paid=t==='security_deposit'?(depositPaid+salePaid):refundPaid;
 const gasReceivable=t==='security_deposit'?(saleTotal+previousOs):(t==='cylinder_return'?0:(saleTotal+previousOs));
 const balanceAfter=t==='security_deposit'?Math.max(0,gasReceivable-salePaid):Math.max(0,netReceivable-paid);
 document.getElementById('receiptAmountValue').textContent=paid.toFixed(2);
 document.getElementById('customerOsBalanceValue').textContent=balanceAfter.toFixed(2);
 document.getElementById('securityDepositBox').style.display=t==='security_deposit'?'block':'none';
 document.getElementById('paymentPurposeLabel').textContent=t==='security_deposit'?'Payments are separated: Security Deposit and Gas / Cylinder':(t==='cylinder_return'?'Security Deposit Refund Payment':'Sale Payment');
 updateRefund();
}
function refreshForm(){
 const t=transactionType(),standard=['gas_sale','cylinder_sale'].includes(t),workspace=document.getElementById('posWorkspace');
 if(workspace)workspace.className='row g-3 pos-workspace mode-'+t;
 payments.querySelectorAll('.payment').forEach(p=>{const pt=p.querySelector('.paymentType');if(!pt)return;pt.innerHTML=paymentTypeOptions();pt.value=t==='security_deposit'?'security_deposit':(t==='cylinder_return'?'security_deposit_refund':'sale');});
 document.getElementById('standardTransaction').style.display=standard?'block':'none';document.getElementById('gasEntryModeWrap').style.display=t==='gas_sale'?'block':'none';
 document.getElementById('securityTransaction').style.display=t==='security_deposit'?'block':'none';
 document.getElementById('returnTransaction').style.display=t==='cylinder_return'?'block':'none';
 document.getElementById('securityDeposit').disabled=t!=='security_deposit'; document.getElementById('securityDepositBox').style.display=t==='security_deposit'?'block':'none'; document.getElementById('discount').disabled=!standard; if(!standard)document.getElementById('discount').value='0';
 if(t!=='security_deposit')document.getElementById('securityDeposit').value='0';
 document.getElementById('refundBox').style.display=t==='cylinder_return'?'block':'none';
 document.getElementById('paymentSection').style.display=t==='cylinder_return'?(Number(document.getElementById('refundAmount').value||0)>0?'block':'none'):'block';document.getElementById('paymentPurposeLabel').textContent=t==='security_deposit'?'Payments are separated: Security Deposit and Gas / Cylinder':(t==='cylinder_return'?'Security Deposit Refund Payment':'Sale Payment');
 document.getElementById('saveBtn').textContent=t==='cylinder_return'?'Return Cylinder / Refund Deposit':t==='security_deposit'?'Receive Deposit / Issue Cylinder':'Post Transaction';
 if(standard)rebuildLines();else{clearLines();lineHead.innerHTML='';}
 setCustodyLists();refreshPaymentModes();recalc();
}

let issueLines=[],returnLines=[];
window.depositBalances=<?=json_encode($depositBalances??[],JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT)?>;

function issueSelectedIds(){return issueLines.flatMap(x=>x.selected_cylinder_unit_ids||[]).map(Number);}
function returnSelectedIds(){return returnLines.map(x=>Number(x.unit_id));}
function issueRateDefaults(typeId){const t=cylinderTypeById(typeId);const cylCfg=Number(rates[typeId]??0);const cylMaster=Number(t?.empty_cylinder_price??0);return {gas:kgRate!==null?Number(kgRate):0,cyl:cylCfg>0?cylCfg:cylMaster};}
function issuePickerRender(){
 const typeSelect=document.getElementById('issuePickerType'),typeId=typeSelect.value,status=document.getElementById('issuePickerStatus').value,box=document.getElementById('issuePickerCards'),used=new Set(issueSelectedIds());
 const selectedType=typeSelect.options[typeSelect.selectedIndex];
 if(selectedType&&typeId){const typeObj=cylinderTypeById(typeId),unitsForType=availableCustodyUnits.filter(u=>String(u.cylinder_type_id)===String(typeId)),gasForType=(filledUnits[typeId]||[]).reduce((s,u)=>s+Number(u.gas_weight_kg||0),0);selectedType.textContent=(typeObj?.code||'')+' — '+(typeObj?.name||'')+' — Available Stock: '+gasForType.toFixed(2)+' KG ('+unitsForType.length+' cylinder'+(unitsForType.length===1?'':'s')+')';}
 let units=availableCustodyUnits.filter(u=>String(u.cylinder_type_id)===String(typeId)&&!used.has(Number(u.id)));
 units=units.filter(u=>status==='filled'?Number(u.gas_weight_kg||0)>0.00001:u.status==='empty');
 document.getElementById('issuePickerCount').textContent=units.length+' available';
 if(!typeId){box.innerHTML='<div class="text-muted">Select a cylinder type.</div>';return;}
 if(!units.length){box.innerHTML='<div class="alert alert-warning w-100">No available cylinders match this filter.</div>';return;}
 box.innerHTML=units.map(u=>{const gas=Number(u.gas_weight_kg||0),cap=Number(u.capacity_kg||0),label=status==='filled'?(gas>=cap-0.00001?'Filled':'Partially Filled'):'Empty';return '<label class="btn btn-sm btn-outline-secondary text-start cylinder-option position-relative"><input type="checkbox" class="issuePickCheck" value="'+u.id+'" style="position:absolute;opacity:0"><span class="cylinder-copy"><span class="cylinder-code">'+escapeHtml(u.unit_code)+'</span><span class="cylinder-name">'+escapeHtml(u.cylinder_name||u.cylinder_code||'')+'</span><span class="cylinder-gas"><strong>'+gas.toFixed(3)+' KG</strong> <span class="cylinder-status">'+label+'</span></span></span></label>';}).join('');
}
function renderIssueLines(){
 const body=document.getElementById('issueLinesBody'),empty=document.getElementById('issueEmpty');
 if(!body||!empty)return;
 body.innerHTML=issueLines.map((l,i)=>{
   const gas=Number(l.gas_weight_kg||0);
   const amount=l.status==='filled'
     ?(gas*Number(l.gas_rate||0)+Number(l.cylinder_rate||0)*l.selected_cylinder_unit_ids.length)
     :(Number(l.cylinder_rate||0)*l.selected_cylinder_unit_ids.length);
   const physical=(l.selected_cylinder_unit_ids||[]).map(id=>{
     const u=availableCustodyUnits.find(x=>Number(x.id)===Number(id));
     return '<span class="badge text-bg-light border me-1">'+escapeHtml(u?.unit_code||id)+'</span>';
   }).join('');
   const gasRate=l.status==='filled'
     ?'<input class="form-control form-control-sm issueGasRateInput" data-index="'+i+'" type="number" min="0" step="0.01" value="'+Number(l.gas_rate||0).toFixed(2)+'">'
     :'';
   return '<tr>'
     +'<td><strong>'+escapeHtml(l.type_label)+'</strong><br><span class="badge text-bg-'+(l.status==='filled'?'success':'secondary')+'">'+(l.status==='filled'?'Filled / Partial':'Empty')+'</span></td>'
     +'<td>'+physical+'</td>'
     +'<td class="text-end">'+gas.toFixed(3)+'</td>'
     +'<td class="text-end">'+gasRate+'</td>'
     +'<td class="text-end">'+Number(l.cylinder_rate||0).toFixed(2)+'</td>'
     +'<td class="text-end fw-semibold">'+amount.toFixed(2)+'</td>'
     +'<td><button type="button" class="btn btn-sm btn-outline-danger issue-line-remove" data-index="'+i+'">×</button></td>'
     +'</tr>';
 }).join('');
 empty.style.display=issueLines.length?'none':'block';
 body.querySelectorAll('.issueGasRateInput').forEach(el=>el.oninput=()=>{
   const i=Number(el.dataset.index);
   if(!issueLines[i])return;
   issueLines[i].gas_rate=Math.max(0,Number(el.value||0));
   recalc();
 });
 body.querySelectorAll('.issue-line-remove').forEach(btn=>btn.onclick=()=>{
   const i=Number(btn.dataset.index);
   issueLines.splice(i,1);
   renderIssueLines();
   recalc();
 });
}
function openIssuePicker(){
 if(!selectedCustomerId()){alert('Select a customer first.');return;}
 document.getElementById('issuePickerType').value='';
 document.getElementById('issuePickerStatus').value='filled';
 issuePickerRender();
 bootstrap.Modal.getOrCreateInstance(document.getElementById('issuePickerModal')).show();
}
function addIssueSelection(){
 const typeId=Number(document.getElementById('issuePickerType').value||0),status=document.getElementById('issuePickerStatus').value,ids=[...document.querySelectorAll('.issuePickCheck:checked')].map(x=>Number(x.value));
 if(!typeId||!ids.length){alert('Select a cylinder type and at least one physical cylinder.');return;}
 const combo=typeId+'|'+status;if(issueLines.some(x=>x.combo===combo)){alert('This Cylinder Type + Filled/Empty combination is already in the issue transaction. Remove it before adding another line.');return;}
 const units=ids.map(id=>availableCustodyUnits.find(u=>Number(u.id)===id)).filter(Boolean),def=issueRateDefaults(typeId),type=cylinderTypeById(typeId);
 const gas=units.reduce((s,u)=>s+Number(u.gas_weight_kg||0),0);
 issueLines.push({combo,type_id:typeId,type_label:(type?.code||'')+' — '+(type?.name||''),status,selected_cylinder_unit_ids:ids,gas_weight_kg:gas,gas_rate:status==='filled'?def.gas:0,cylinder_rate:def.cyl});
 renderIssueLines();recalc();bootstrap.Modal.getInstance(document.getElementById('issuePickerModal')).hide();
}
function returnPickerRender(){
 const typeId=document.getElementById('returnPickerType').value,status=document.getElementById('returnPickerStatus').value,box=document.getElementById('returnPickerCards'),used=new Set(returnSelectedIds()),cid=selectedCustomerId();
 let units=allCustomerCustody.filter(u=>String(u.customer_id)===String(cid)&&!used.has(Number(u.unit_id)));
 if(typeId)units=units.filter(u=>String(u.cylinder_type_id)===String(typeId));
 if(status)units=units.filter(u=>status==='filled'?Number(u.gas_weight_kg||0)>0.00001:Number(u.gas_weight_kg||0)<=0.00001);
 document.getElementById('returnPickerCount').textContent=units.length+' available';
 if(!cid){box.innerHTML='<div class="alert alert-warning w-100">Select a customer first.</div>';return;}
 if(!units.length){box.innerHTML='<div class="alert alert-warning w-100">No pending cylinders match this filter.</div>';return;}
 box.innerHTML=units.map(u=>'<label class="btn btn-sm btn-outline-secondary text-start cylinder-option position-relative"><input type="checkbox" class="returnPickCheck" value="'+u.unit_id+'" style="position:absolute;opacity:0"><span class="cylinder-copy"><span class="cylinder-code">'+escapeHtml(u.unit_code)+'</span><span class="cylinder-name">'+escapeHtml(u.cylinder_code||u.cylinder_name||'')+'</span><span class="cylinder-gas"><strong>Issued '+Number(u.issued_gas_weight_kg??u.gas_weight_kg??0).toFixed(3)+' KG</strong> · '+(Number(u.gas_weight_kg||0)>0?'Filled/Partial':'Empty')+'</span></span></label>').join('');
}
function renderReturnLines(){
 const body=document.getElementById('returnLinesBody'),empty=document.getElementById('returnEmpty'),allowGas=<?=json_encode((int)($shopSettings['allow_return_gas_qty']??0))?>===1;
 body.innerHTML=returnLines.map((l,i)=>{const issued=Number(l.issued_gas_weight_kg||0),ret=Number(l.return_gas_kg||0),cons=Math.max(0,issued-ret);return '<tr><td><strong>'+escapeHtml(l.unit_code)+'</strong><br><span class="small text-muted">'+escapeHtml(l.cylinder_code||'')+'</span></td><td>'+issued.toFixed(3)+'</td><td><input class="form-control form-control-sm returnGasInput" data-index="'+i+'" type="number" min="0" step="0.001" value="'+ret.toFixed(3)+'" '+(allowGas?'':'disabled')+'></td><td><input class="form-control form-control-sm returnRateInput" data-index="'+i+'" type="number" min="0" step="0.01" value="'+Number(l.return_gas_rate||0).toFixed(2)+'" '+(ret>0?'':'disabled')+'></td><td class="consumedCell">'+cons.toFixed(3)+'</td><td class="statusCell">'+(ret>0?'Partially Filled':'Empty')+'</td><td><button type="button" class="btn btn-sm btn-outline-danger" onclick="returnLines.splice('+i+',1);renderReturnLines();recalc();">×</button></td></tr>';}).join('');
 empty.style.display=returnLines.length?'none':'block';
 body.querySelectorAll('.returnGasInput').forEach(el=>el.oninput=()=>{const i=Number(el.dataset.index);returnLines[i].return_gas_kg=Math.max(0,Number(el.value||0));if(returnLines[i].return_gas_kg>0&&Number(returnLines[i].return_gas_rate||0)<=0)returnLines[i].return_gas_rate=Number(returnLines[i].issued_gas_rate||0);renderReturnLines();recalc();});
 body.querySelectorAll('.returnRateInput').forEach(el=>el.oninput=()=>{returnLines[Number(el.dataset.index)].return_gas_rate=Math.max(0,Number(el.value||0));recalc();});
}
function openReturnPicker(){if(!selectedCustomerId()){alert('Select a customer first.');return;}document.getElementById('returnPickerType').value='';document.getElementById('returnPickerStatus').value='';returnPickerRender();bootstrap.Modal.getOrCreateInstance(document.getElementById('returnPickerModal')).show();}
function addReturnSelection(){const ids=[...document.querySelectorAll('.returnPickCheck:checked')].map(x=>Number(x.value));if(!ids.length){alert('Select at least one cylinder.');return;}ids.forEach(id=>{const u=allCustomerCustody.find(x=>Number(x.unit_id)===id);if(u)returnLines.push({unit_id:id,unit_code:u.unit_code,cylinder_code:u.cylinder_code,issued_gas_weight_kg:Number(u.issued_gas_weight_kg??u.gas_weight_kg??0),issued_gas_rate:Number(u.issued_gas_rate||0),return_gas_kg:0,return_gas_rate:Number(u.issued_gas_rate||0)});});renderReturnLines();recalc();bootstrap.Modal.getInstance(document.getElementById('returnPickerModal')).hide();}
function setCustodyLists(){renderIssueLines();renderReturnLines();updateRefund();}
function updateRefund(){const available=Number((window.depositBalances||{})[selectedCustomerId()]||0);const el=document.getElementById('refundAvailable');el.textContent=available.toFixed(2);el.dataset.value=available.toFixed(2);const refund=document.getElementById('refundAmount');if(Number(refund.value||0)>available)refund.value=available.toFixed(2);}


document.getElementById('issuePickerType').onchange=issuePickerRender;document.getElementById('issuePickerStatus').onchange=issuePickerRender;document.getElementById('openIssuePicker').onclick=openIssuePicker;document.getElementById('confirmIssuePicker').onclick=addIssueSelection;
document.getElementById('returnPickerType').onchange=returnPickerRender;document.getElementById('returnPickerStatus').onchange=returnPickerRender;document.getElementById('openReturnPicker').onclick=openReturnPicker;document.getElementById('confirmReturnPicker').onclick=addReturnSelection;
document.getElementById('refundAmount').oninput=recalc;
document.getElementById('applyReturnRate').onclick=()=>{const rate=Math.max(0,Number(document.getElementById('returnOverallRate').value||0));returnLines.forEach(l=>l.return_gas_rate=rate);renderReturnLines();recalc();};
document.getElementById('gasEntryMode').onchange=()=>{
 const mode=document.getElementById('gasEntryMode').value;
 tbody.querySelectorAll('tr').forEach(tr=>{if(!tr.querySelector('.entryValue'))return;const rate=Number(tr.querySelector('.gasRate').value||0),q=Number(tr.querySelector('.qty').value||0),a=Number(tr.querySelector('.enteredAmount').value||0),input=tr.querySelector('.entryValue');tr.dataset.entryMode=mode;tr.querySelector('.gasRate').disabled=mode==='amount';if(mode==='amount'){const v=a>0?a:(rate>0?q*rate:0);tr.querySelector('.enteredAmount').value=v>0?v.toFixed(2):'';input.value=v>0?v.toFixed(2):'';input.placeholder='Amount';input.step='0.01';}else{const v=q>0?q:(rate>0&&a>0?a/rate:0);tr.querySelector('.qty').value=v>0?v.toFixed(3):'0';input.value=v>0?v.toFixed(3):'';input.placeholder='KG';input.step='any';tr.querySelector('.enteredAmount').value=rate>0&&v>0?(v*rate).toFixed(2):'';}refreshGasSourceLine(tr,false);});recalc();
};
document.getElementById('transactionType').onchange=refreshForm;
document.getElementById('customer_id').onchange=()=>{
  const selected=selectedCustomerId();
  // A customer change starts a clean transaction context so no values from the
  // previous customer can remain accidentally selected.
  clearLines();
  lineHead.innerHTML='';
  document.getElementById('discount').value='0';
  document.getElementById('securityDeposit').value='0';
  payments.innerHTML='';
  addPayment();
  refreshCustomer();
  setCustodyLists();
  refreshForm();
  if(selected){
    const c=selectedCustomer();
    document.getElementById('previousOs').textContent=(c?Math.max(0,c.balance):0).toFixed(2);
    document.getElementById('customerOsBalanceValue').textContent=(c?Math.max(0,c.balance):0).toFixed(2);
  }else{
    document.getElementById('previousOs').textContent='0.00';
    document.getElementById('customerOsBalanceValue').textContent='0.00';
  }
  recalc();
};
document.getElementById('addLine').onclick=()=>{if(transactionType()==='gas_sale')addGasLine();else if(transactionType()==='cylinder_sale')addCylinderSaleLine();};
document.getElementById('discount').oninput=recalc;document.getElementById('securityDeposit').oninput=recalc;const returnUnitsEl=document.getElementById('returnUnits');if(returnUnitsEl)returnUnitsEl.onchange=updateRefund;document.getElementById('addPayment').onclick=addPayment;
document.getElementById('saleForm').onsubmit=async(e)=>{
 e.preventDefault();
 const errorBox=document.getElementById('posValidationAlert'),saveBtn=document.getElementById('saveBtn');
 const showError=(message)=>{const msg=String(message||'Validation failed.');errorBox.textContent=msg;errorBox.classList.remove('d-none');errorBox.scrollIntoView({behavior:'smooth',block:'nearest'});};
 const clearError=()=>{errorBox.textContent='';errorBox.classList.add('d-none');};
 clearError();
 const t=transactionType(),customerId=selectedCustomerId();
 const fail=(msg)=>{showError(msg);return false;};
 const earlyModes=[...payments.querySelectorAll('.payment .mode')].map(x=>x.value);
 if(['gas_sale','cylinder_sale'].includes(t)&&!customerId&&earlyModes.includes('credit'))return fail('Credit sale is not allowed for Walk-in / Cash customer.');
 let lines=[];
 if(t==='gas_sale'){
   lines=[...tbody.querySelectorAll('tr')].map(tr=>({cylinder_type_id:tr.querySelector('.cyl').value,source_cylinder_unit_id:tr.querySelector('.sourceCyl').value,quantity:tr.querySelector('.qty').value,gas_rate:tr.querySelector('.gasRate').value,entered_amount:document.getElementById('gasEntryMode').value==='amount'?tr.querySelector('.enteredAmount').value:'',customer_cylinder_unit_id:tr.querySelector('.targetCyl').value}));
   if(!lines.length)return fail('Add at least one gas line.');
   const seenTypes=new Set(),seenSources=new Set();
   for(const [i,l] of lines.entries()){
     const q=Number(l.quantity||0),a=Number(l.entered_amount||0);
     if(!l.cylinder_type_id || (document.getElementById('gasEntryMode').value==='amount' ? a<=0 : q<=0))return fail('Gas line '+(i+1)+' is invalid. Enter a valid '+(document.getElementById('gasEntryMode').value==='amount'?'amount':'quantity')+'.');
     if(seenTypes.has(String(l.cylinder_type_id)))return fail('Cylinder type cannot be used on multiple gas sale lines. Combine the quantity into one line.');
     seenTypes.add(String(l.cylinder_type_id));
     if(allowPosSourceCylinderSelection){
       if(!l.source_cylinder_unit_id)return fail('Gas line '+(i+1)+' requires a source filled cylinder.');
       if(seenSources.has(String(l.source_cylinder_unit_id)))return fail('The same source filled cylinder cannot be selected on multiple gas sale lines.');
       seenSources.add(String(l.source_cylinder_unit_id));
       const src=(filledUnits[l.cylinder_type_id]||[]).find(u=>String(u.id)===String(l.source_cylinder_unit_id));
       if(!src)return fail('Gas line '+(i+1)+' source cylinder is no longer available.');
       if(q>Number(src.gas_weight_kg||0)+0.00001)return fail('Gas line '+(i+1)+' cannot exceed the selected source cylinder gas stock of '+Number(src.gas_weight_kg||0).toFixed(2)+' KG.');
     }else{
       l.source_cylinder_unit_id='';
       const available=(filledUnits[l.cylinder_type_id]||[]).reduce((s,u)=>s+Number(u.gas_weight_kg||0),0);
       if(q>available+0.00001)return fail('Gas line '+(i+1)+' cannot exceed available stock of this cylinder type: '+available.toFixed(2)+' KG.');
     }
   }
 }else if(t==='cylinder_sale'){
   lines=[...tbody.querySelectorAll('tr:not(.cylinderPickerRow)')].map(tr=>({cylinder_type_id:tr.querySelector('.cyl').value,cylinder_status:tr.querySelector('.cylStatus').value,quantity:tr.querySelector('.qty').value,gas_weight_kg:tr.querySelector('.gasQty').value,gas_rate:tr.querySelector('.gasRate').value,cylinder_rate:tr.querySelector('.cylRate').value,selected_cylinder_unit_ids:tr._selectedUnitIds||[]}));
   if(!lines.length)return fail('Add at least one cylinder sale line.');
   for(const [i,l] of lines.entries()){const q=Number(l.quantity||0);if(!l.cylinder_type_id||q<=0||q!==Math.floor(q))return fail('Cylinder sale line '+(i+1)+' requires a whole-number quantity.');if(l.cylinder_status==='filled'&&(!Array.isArray(l.selected_cylinder_unit_ids)||l.selected_cylinder_unit_ids.length!==q))return fail('Cylinder sale line '+(i+1)+' requires selecting exactly '+q+' physical cylinder(s).');}
 }else if(t==='security_deposit'){
   const units=[...document.getElementById('custodyUnits').selectedOptions].map(o=>Number(o.value));if(!customerId)return fail('Select a customer for Security Deposit.');if(!units.length)return fail('Select at least one cylinder to issue on custody.');if(Number(document.getElementById('securityDeposit').value||0)<=0)return fail('Enter a Security Deposit Amount.');
 }else if(t==='cylinder_return'){
   const units=[...document.getElementById('returnUnits').selectedOptions].map(o=>Number(o.value));if(!customerId)return fail('Select a customer for Cylinder Return.');if(!units.length)return fail('Select at least one customer custody cylinder to return.');
 }
 const pays=t==='cylinder_return'?[]:[...payments.querySelectorAll('.payment')].map(p=>({payment_mode:p.querySelector('.mode').value,amount:p.querySelector('.amount').value,reference_no:p.querySelector('.ref').value}));
 document.getElementById('lines_json').value=JSON.stringify(lines);
 document.getElementById('payments_json').value=JSON.stringify(pays);
 const saleTotal=Number(document.getElementById('saleTotal').textContent||0),previousOs=customerId?Number(document.getElementById('previousOs').textContent||0):0,deposit=t==='security_deposit'?Number(document.getElementById('securityDeposit').value||0):0;
 const expected=t==='security_deposit'?deposit:(t==='cylinder_return'?0:saleTotal+previousOs);
 if(t!=='cylinder_return'&&!pays.length)return fail('Add at least one payment.');
 if(t!=='cylinder_return'&&t!=='security_deposit'&&paymentTotal()>expected+0.01)return fail('Payment cannot exceed the Net Amount Receivable of '+expected.toFixed(2)+'.');
 if(!customerId&&pays.some(p=>p.payment_mode!=='cash'))return fail('Walk-in transactions are cash only.');
 if(customerId&&pays.some(p=>p.payment_mode==='credit')&&!selectedCustomer()?.allowCredit)return fail('Credit sale is not allowed for this customer. Enable Allow Credit Sale on the customer record.');
 if(['gas_sale','cylinder_sale'].includes(t)&&customerId){
   const customer=selectedCustomer(),newOs=Math.max(0,expected-paymentTotal());
   if(newOs>previousOs+0.01&&!customer?.allowCredit)return fail('Credit sale is not allowed for this customer. Enable Allow Credit Sale on the customer record.');
   if(customer?.allowCredit){const limit=Number(customer.limit||0);if(limit>0&&newOs>limit+0.01)return fail('Credit limit exceeded. Current OS is '+previousOs.toFixed(2)+', resulting OS would be '+newOs.toFixed(2)+', and the allowed limit is '+limit.toFixed(2)+'.');}
 }
 if(!customerId&&['gas_sale','cylinder_sale'].includes(t)&&Math.abs(paymentTotal()-expected)>0.01)return fail('Walk-in sale must be fully paid. Received amount must equal sale total.');
 let gasRequired=0;if(t==='gas_sale')gasRequired=lines.reduce((s,l)=>s+Number(l.quantity||0),0);else if(t==='cylinder_sale')tbody.querySelectorAll('tr').forEach(tr=>{gasRequired+=gasForCylinderSale(tr);});
 if((t==='gas_sale'||t==='cylinder_sale')&&gasRequired>Number(gasStock||0)+0.00001){
   if(!confirm('Available gas stock is '+Number(gasStock||0).toFixed(2)+' KG, but this transaction requires '+gasRequired.toFixed(2)+' KG. Continue?'))return false;
   document.getElementById('stock_override_confirmed').value='1';
 }else document.getElementById('stock_override_confirmed').value='0';
 saveBtn.disabled=true;const originalText=saveBtn.textContent;saveBtn.textContent='Posting...';
 try{
   const response=await fetch(document.getElementById('saleForm').action,{method:'POST',headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'},body:new FormData(document.getElementById('saleForm'))});
   const data=await response.json().catch(()=>({}));
   if(!response.ok||data.success!==true)throw new Error(data.error||'Unable to post transaction.');
   window.location.href=data.redirect||'<?=site_url('sales')?>';
 }catch(err){
   saveBtn.disabled=false;saveBtn.textContent=originalText;showError(err.message);
 }
 return false;
};

document.getElementById('saleForm').onsubmit=async(e)=>{
 e.preventDefault();const errorBox=document.getElementById('posValidationAlert'),saveBtn=document.getElementById('saveBtn');
 const fail=msg=>{errorBox.textContent=String(msg);errorBox.classList.remove('d-none');errorBox.scrollIntoView({behavior:'smooth',block:'nearest'});return false;};errorBox.classList.add('d-none');
 const t=transactionType(),customerId=selectedCustomerId();
 let lines=[];
 if(t==='security_deposit'){
   if(!customerId)return fail('Select a customer for Security Deposit / Issue Cylinder.');
   if(!issueLines.length)return fail('Select at least one cylinder to issue.');
   lines=issueLines.map(l=>({cylinder_type_id:l.type_id,cylinder_status:l.status,selected_cylinder_unit_ids:l.selected_cylinder_unit_ids,gas_weight_kg:l.gas_weight_kg,gas_rate:l.gas_rate,cylinder_rate:l.cylinder_rate}));
 }else if(t==='cylinder_return'){
   if(!customerId)return fail('Select a customer for Cylinder Return.');
   if(!returnLines.length)return fail('Select at least one customer custody cylinder to return.');
   const allowGas=<?=json_encode((int)($shopSettings['allow_return_gas_qty']??0))?>===1;
   for(const l of returnLines){if(!allowGas&&Number(l.return_gas_kg||0)>0)return fail('Return gas quantity is disabled in Shop Settings.');if(Number(l.return_gas_kg||0)>Number(l.issued_gas_weight_kg||0)+0.00001&&!<?=json_encode((int)($shopSettings['allow_return_gas_over_issued']??0))?>)return fail('Return gas exceeds issued gas for '+l.unit_code+'.');}
   const refund=Math.max(0,Number(document.getElementById('refundAmount').value||0));
   const depositBalance=Number(document.getElementById('refundAvailable').dataset.value||0);
   if(refund>depositBalance+0.01)return fail('Refund exceeds the customer refundable security deposit balance.');
   lines=returnLines.map(l=>({unit_id:l.unit_id,return_gas_kg:Number(l.return_gas_kg||0),return_gas_rate:Number(l.return_gas_rate||0)}));
 }else if(t==='gas_sale'){
   lines=[...tbody.querySelectorAll('tr')].map(tr=>({cylinder_type_id:tr.querySelector('.cyl').value,source_cylinder_unit_id:tr.querySelector('.sourceCyl').value,quantity:tr.querySelector('.qty').value,gas_rate:tr.querySelector('.gasRate').value,entered_amount:document.getElementById('gasEntryMode').value==='amount'?tr.querySelector('.enteredAmount').value:'',customer_cylinder_unit_id:tr.querySelector('.targetCyl').value}));
   if(!lines.length)return fail('Add at least one gas line.');
 }else if(t==='cylinder_sale'){
   lines=[...tbody.querySelectorAll('tr:not(.cylinderPickerRow)')].map(tr=>({cylinder_type_id:tr.querySelector('.cyl').value,cylinder_status:tr.querySelector('.cylStatus').value,quantity:tr.querySelector('.qty').value,gas_weight_kg:tr.querySelector('.gasQty').value,gas_rate:tr.querySelector('.gasRate').value,cylinder_rate:tr.querySelector('.cylRate').value,selected_cylinder_unit_ids:tr._selectedUnitIds||[]}));if(!lines.length)return fail('Add at least one cylinder sale line.');
 }
 const pays=[...payments.querySelectorAll('.payment')].map(p=>({payment_type:p.querySelector('.paymentType')?.value||'sale',payment_mode:p.querySelector('.mode').value,amount:p.querySelector('.amount').value,reference_no:p.querySelector('.ref').value}));
 document.getElementById('lines_json').value=JSON.stringify(lines);document.getElementById('payments_json').value=JSON.stringify(pays);
 const saleTotal=Number(document.getElementById('saleTotal').textContent||0),deposit=t==='security_deposit'?Number(document.getElementById('securityDeposit').value||0):0,refund=t==='cylinder_return'?Number(document.getElementById('refundAmount').value||0):0;
 if(t==='security_deposit'&&deposit<0)return fail('Security Deposit Amount cannot be negative.');
 if(t==='cylinder_return'&&refund>0){const refundTotal=paymentTotal('security_deposit_refund');if(!pays.length){addPayment('security_deposit_refund');return fail('Add a payment method for the security deposit refund.');}if(Math.abs(refundTotal-refund)>0.01)return fail('Refund payment total must equal the refund amount.');if(pays.some(p=>p.payment_type!=='security_deposit_refund'))return fail('Cylinder Return payments must be classified as Security Deposit Refund.');}
 if(t==='security_deposit'){const depositPaid=paymentTotal('security_deposit'),salePaid=paymentTotal('sale'),expectedGas=saleTotal+Number(document.getElementById('previousOs').textContent||0);if(deposit>0&&Math.abs(depositPaid-deposit)>0.01)return fail('Security Deposit payment total must equal the Security Deposit amount.');if(Math.abs(salePaid-expectedGas)>0.01)return fail('Gas / Cylinder payment total must equal the current charge plus any previous gas OS.');if(pays.some(p=>!['security_deposit','sale'].includes(p.payment_type)))return fail('Invalid payment classification for Security Deposit transaction.');}
 if(['gas_sale','cylinder_sale'].includes(t)){const expected=saleTotal+Number(document.getElementById('previousOs').textContent||0);if(!pays.length)return fail('Add at least one payment.');if(paymentTotal()>expected+0.01)return fail('Payment cannot exceed the net receivable.');}
 document.getElementById('stock_override_confirmed').value='0';saveBtn.disabled=true;const old=saveBtn.textContent;saveBtn.textContent='Posting...';
 try{const response=await fetch(document.getElementById('saleForm').action,{method:'POST',headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'},body:new FormData(document.getElementById('saleForm'))});const data=await response.json().catch(()=>({}));if(!response.ok||data.success!==true)throw new Error(data.error||'Unable to post transaction.');window.location.href=data.redirect||'<?=site_url('sales')?>';}catch(err){saveBtn.disabled=false;saveBtn.textContent=old;return fail(err.message);}
 return false;
};
(() => {
  const tx = document.getElementById('transactionType');
  const allowed = [...tx.options].map(o => o.value);
  const initial = allowed.includes(defaultTransactionType) ? defaultTransactionType : (allowed[0] || 'gas_sale');
  tx.value = initial;
  addPayment();
  refreshCustomer();
  tx.dispatchEvent(new Event('change', {bubbles:true}));
})();
</script>
<?= $this->endSection() ?>