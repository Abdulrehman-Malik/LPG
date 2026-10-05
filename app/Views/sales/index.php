<?= $this->extend('layouts/app') ?>
<?= $this->section('styles') ?>
<style>
.pos-workspace > .pos-lines-panel { flex: 0 0 75%; max-width: 75%; }
.pos-workspace > .pos-summary-panel { flex: 0 0 25%; max-width: 25%; }
#lines { width:100%; table-layout:fixed; }
#lines th,#lines td { padding:.65rem .5rem; vertical-align:middle; }
/* Gas Sale: 7 columns */
#lines.gas-sale-mode th:nth-child(1){width:27%} #lines.gas-sale-mode th:nth-child(2){width:20%} #lines.gas-sale-mode th:nth-child(3){width:14%}
#lines.gas-sale-mode th:nth-child(4){width:13%} #lines.gas-sale-mode th:nth-child(5){width:15%} #lines.gas-sale-mode th:nth-child(6){width:8%} #lines.gas-sale-mode th:nth-child(7){width:3%}
/* Cylinder Sale: 8 columns — keep every field readable */
#lines.cylinder-sale-mode th:nth-child(1){width:22%} #lines.cylinder-sale-mode th:nth-child(2){width:14%} #lines.cylinder-sale-mode th:nth-child(3){width:8%}
#lines.cylinder-sale-mode th:nth-child(4){width:10%} #lines.cylinder-sale-mode th:nth-child(5){width:14%} #lines.cylinder-sale-mode th:nth-child(6){width:14%} #lines.cylinder-sale-mode th:nth-child(7){width:11%} #lines.cylinder-sale-mode th:nth-child(8){width:7%}
#lines.cylinder-sale-mode .cylStatus { min-width:0; }
#lines.cylinder-sale-mode select { min-width:0; width:100%; }
#lines .form-select,#lines .form-control { min-height:42px; }
#lines .lineTotal { font-size:1.05rem; white-space:nowrap; }
.pos-lines-panel > .card > .card-body { padding: .6rem; }
.pos-summary-card .card-body { padding: .75rem; }
.pos-header-row { margin-bottom:.5rem !important; row-gap:.45rem !important; }
.pos-header-row .form-label { margin-bottom:.25rem; }
.pos-header-row .credit-status-row { margin-top:-.15rem; }
#standardTransaction { margin-top:0 !important; padding-top:0 !important; }
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
.cylinderPickerRow { display:none; }
.cylinderPickerRow.is-visible { display:table-row; }
.cylinderPickerRow td { padding:.15rem .35rem !important; border-top:0 !important; }
.cylinderPicker { padding:.35rem !important; max-height:230px; overflow:auto; }
.cylinder-picker-title { font-size:.82rem; margin-bottom:.3rem !important; }
.cylinder-options { gap:.35rem !important; }
.cylinder-option { width:150px; min-width:150px !important; max-width:150px; min-height:76px; padding:.35rem .45rem !important; border-radius:.55rem !important; display:flex; align-items:center; gap:.4rem; }
.cylinder-option:hover { transform:translateY(-1px); }
.cylinder-option .cylinder-art { flex:0 0 34px; width:34px; height:42px; }
.cylinder-option .cylinder-copy { min-width:0; line-height:1.08; }
.cylinder-option .cylinder-code { display:block; font-size:.86rem; font-weight:800; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.cylinder-option .cylinder-name { display:block; font-size:.68rem; font-weight:700; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.cylinder-option .cylinder-gas { display:block; font-size:.67rem; margin-top:.15rem; white-space:nowrap; }
.cylinder-option .cylinder-status { font-size:.62rem; font-weight:700; }
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
  .pos-workspace > .pos-lines-panel,.pos-workspace > .pos-summary-panel{flex:0 0 100%;max-width:100%}
}
input[type="number"]::-webkit-outer-spin-button,input[type="number"]::-webkit-inner-spin-button{ -webkit-appearance:none; margin:0; }
input[type="number"]{ -moz-appearance:textfield; appearance:textfield; }
</style>
<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <?php if($cashSession): ?><div class="small text-success fw-semibold">Cash session: OPEN — <?=esc($cashSession["register_code"])?></div>
    <?php else: ?><div class="small text-warning fw-semibold">Cash session: NOT OPEN — open Counter Cash before cash sales</div><?php endif; ?>
  </div>
  <span class="badge text-bg-secondary">POS</span>
</div>
<?php if(session()->getFlashdata('success')): ?><div class="alert alert-success"><?= session()->getFlashdata('success') ?></div><?php endif; ?>
<?php if(session()->getFlashdata('error')): ?><div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div><?php endif; ?>

<div class="d-flex gap-2 mb-3">
  <button type="button" class="btn btn-primary" id="newSaleTab">New Sale</button>
  <button type="button" class="btn btn-outline-primary" id="saleHistoryTab">Sale History</button>
</div>
<div id="saleHistoryPanel" style="display:none">
  <div class="card mb-2"><div class="card-body py-2"><div class="row g-2 align-items-end">
    <div class="col-md-2"><label class="form-label">From Date</label><input type="date" id="historyFrom" class="form-control" value="<?=date('Y-m-d')?>"></div>
    <div class="col-md-2"><label class="form-label">To Date</label><input type="date" id="historyTo" class="form-control" value="<?=date('Y-m-d')?>"></div>
    <div class="col-md-2"><label class="form-label">Sale No.</label><input type="text" id="historySaleNo" class="form-control" placeholder="Sale number"></div>
    <div class="col-md-2"><label class="form-label">Customer</label><input type="text" id="historyCustomer" class="form-control" placeholder="Name / code"></div>
    <div class="col-md-2"><label class="form-label">Transaction Type</label><select id="historyType" class="form-select"><option value="">All Types</option><option value="gas_sale">Gas Sale</option><option value="cylinder_sale">Cylinder Sale</option><option value="security_deposit">Security Deposit</option><option value="cylinder_return">Cylinder Return</option><option value="refill_service">Refill Service</option><option value="cylinder_exchange">Cylinder Exchange</option><option value="filled_cylinder">Filled Cylinder</option><option value="empty_sale">Empty Cylinder</option><option value="mixed">Mixed</option></select></div>
    <div class="col-md-2"><label class="form-label">Status</label><select id="historyStatus" class="form-select"><option value="">All</option><option value="posted">Posted</option><option value="voided">Void</option></select></div>
    <div class="col-12 d-flex justify-content-between align-items-center mt-1"><div class="d-flex gap-2"><button type="button" class="btn btn-sm btn-primary" id="historySearch">Search</button><button type="button" class="btn btn-sm btn-outline-secondary" id="historyAll">All History</button><button type="button" class="btn btn-sm btn-outline-success" id="historyExport">Export CSV</button></div><span class="small text-muted" id="historyCount"></span></div>
  </div></div></div>
  <div class="card"><div class="table-responsive"><table class="table table-sm table-hover align-middle mb-0"><thead><tr>
    <th>Sale No.</th><th>Date / Time</th><th>Customer</th><th>Type</th><th class="text-end">Subtotal</th><th class="text-end">Discount</th><th class="text-end">Current Sale</th><th class="text-end">Previous OS</th><th class="text-end">Receipt</th><th class="text-end">Net Receivable</th><th class="text-end">OS Balance</th><th>Status</th><th>User</th><th class="text-end">Action</th>
  </tr></thead><tbody id="saleHistoryBody"><tr><td colspan="14" class="text-center text-muted py-4">Loading...</td></tr></tbody></table></div></div>
</div>
<div class="modal fade" id="saleDetailsModal" tabindex="-1"><div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Sale Details</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body" id="saleDetailsBody"></div></div></div></div>
<form method="post" action="<?=site_url('sales/save')?>" id="saleForm"><?=csrf_field()?>
<input type="hidden" name="lines_json" id="lines_json">
<input type="hidden" name="payments_json" id="payments_json">
<input type="hidden" name="stock_override_confirmed" id="stock_override_confirmed" value="0">

<div class="row g-3 pos-workspace">
<div class="col-lg-9 pos-lines-panel"><div class="card"><div class="card-body">

<div class="row g-2 pos-header-row mb-2">
  <div class="col-md-3">
    <label class="form-label fw-semibold">Transaction Type</label>
    <select name="transaction_type" id="transactionType" class="form-select">
      <option value="gas_sale">Gas Sale / Refill</option>
      <option value="cylinder_sale">Cylinder Sale</option>
      <option value="security_deposit">Security Deposit / Issue Cylinder</option>
      <option value="cylinder_return">Cylinder Return / Refund Deposit</option>
    </select>
  </div>
  <div class="col-md-3">
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

<div id="standardTransaction">
  <div class="table-responsive">
    <table class="table table-sm align-middle" id="lines">
      <thead id="lineHead"></thead>
      <tbody></tbody>
    </table>
  </div>
  <button type="button" class="btn btn-outline-primary" id="addLine">Add Line</button>
</div>

<div id="securityTransaction" style="display:none">
  <div class="card border-warning">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="mb-0">Cylinder Custody — Issue</h6><span class="badge text-bg-warning">Issued / Held</span>
      </div>
      <label class="form-label">Company Cylinder(s) to Issue on Custody</label>
      <select name="custody_unit_ids[]" id="custodyUnits" class="form-select custody-list" multiple></select>
    </div>
  </div>
</div>

<div id="returnTransaction" style="display:none">
  <div class="card border-info">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="mb-0">Cylinder Custody — Return</h6><span class="badge text-bg-info">Returned</span>
      </div>
      <label class="form-label">Customer Custody Cylinder(s)</label>
      <select name="return_unit_ids[]" id="returnUnits" class="form-select custody-list" multiple></select>
      <div class="mt-3 p-2 bg-light rounded">Refundable Deposit: <strong><span id="refundPreview">0.00</span></strong></div>
    </div>
  </div>
</div>

</div></div></div>

<div class="col-lg-3 pos-summary-panel"><div class="card pos-summary-card"><div class="card-body">
<div class="pos-summary-metrics">
  <div class="pos-summary-metric"><label class="form-label fw-semibold">Current Sale</label><div class="form-control bg-light fw-semibold"><span id="saleTotal">0.00</span></div></div>
  <div class="pos-summary-metric"><label class="form-label">OS Balance</label><div class="form-control bg-light"><span id="previousOs">0.00</span></div></div>
  <div class="pos-summary-metric"><label class="form-label">Discount</label><input name="discount_amount" id="discount" type="number" min="0" step="any" value="0" class="form-control"></div>
</div>
<div class="mb-3" id="securityDepositBox" style="display:none">
  <label class="form-label fw-semibold">Security Deposit Amount</label>
  <input name="security_deposit_amount" id="securityDeposit" type="number" min="0" step="any" value="0" class="form-control">
</div>
<div class="mb-3">
  <label class="form-label">Net Receivable Amount</label>
  <div class="form-control bg-light fw-bold"><span id="netPayable">0.00</span></div>
</div>
<div class="mb-3"><label class="form-label">Receipt Amount</label><div class="form-control bg-light fw-bold"><span id="receiptAmountValue">0.00</span></div></div>
<div class="mb-3"><label class="form-label fw-semibold">OS Balance</label><div class="form-control bg-light fw-bold text-primary"><span id="customerOsBalanceValue">0.00</span></div></div>
<div class="mb-3" id="refundBox" style="display:none">
  <div class="form-control bg-light text-danger fw-semibold">Customer Refund: <span id="refundAmount">0.00</span></div>
</div>

<div id="paymentSection">
  <div id="payments"></div>
  <button type="button" class="btn btn-outline-secondary mb-3" id="addPayment">Add Payment</button>
</div>

<textarea name="notes" class="form-control mb-3" placeholder="Notes"></textarea>
<button class="btn btn-primary w-100" id="saveBtn">Post Transaction</button>
</div></div></div>
</div>
</form>

<script>
const canVoidSales=<?=json_encode($canVoidSales??false)?>;
document.addEventListener('wheel',e=>{if(document.activeElement?.matches('input[type="number"]'))e.preventDefault();},{passive:false});
const historyBody=document.getElementById('saleHistoryBody');
const historyTypeLabel=t=>({gas_sale:'Gas Sale',cylinder_sale:'Cylinder Sale',security_deposit:'Security Deposit',cylinder_return:'Cylinder Return',refill_service:'Refill Service',cylinder_exchange:'Cylinder Exchange',filled_cylinder:'Filled Cylinder',empty_sale:'Empty Cylinder',mixed:'Mixed'}[t]||t||'-');
function historyQuery(){const p=new URLSearchParams({from:document.getElementById('historyFrom').value,to:document.getElementById('historyTo').value,sale_no:document.getElementById('historySaleNo').value,customer:document.getElementById('historyCustomer').value,transaction_type:document.getElementById('historyType').value,status:document.getElementById('historyStatus').value});return p.toString();}
function csvCell(v){const s=String(v??'');return '"'+s.replace(/"/g,'""')+'"';}
function exportSaleHistory(){const rows=[...historyBody.querySelectorAll('tr.sale-history-row')];if(!rows.length){alert('There are no sales to export for the current filters.');return;}const headers=['Sale No.','Date / Time','Customer','Type','Subtotal','Discount','Current Sale','Previous OS','Receipt','Net Receivable','OS Balance','Status','User'];const data=rows.map(row=>[...row.querySelectorAll('td')].slice(0,13).map(td=>td.innerText.replace(/\s+/g,' ').trim()));const csv=[headers,...data].map(r=>r.map(csvCell).join(',')).join('\r\n');const blob=new Blob(['\uFEFF'+csv],{type:'text/csv;charset=utf-8;'});const url=URL.createObjectURL(blob);const a=document.createElement('a');const from=document.getElementById('historyFrom').value||'all';const to=document.getElementById('historyTo').value||'all';a.href=url;a.download='sale-history-'+from+'-to-'+to+'.csv';document.body.appendChild(a);a.click();a.remove();URL.revokeObjectURL(url);}
async function loadSaleHistory(){historyBody.innerHTML='<tr><td colspan="14" class="text-center text-muted py-4">Loading...</td></tr>';try{const res=await fetch('<?=site_url('sales/history')?>?'+historyQuery(),{headers:{Accept:'application/json'}});const d=await res.json();if(!res.ok)throw new Error(d.error||'Unable to load sale history.');const rows=d.sales||[];document.getElementById('historyCount').textContent=rows.length+' sale(s)';if(!rows.length){historyBody.innerHTML='<tr><td colspan="14" class="text-center text-muted py-4">No sales found.</td></tr>';return;}historyBody.innerHTML=rows.map(s=>{const received=Number(s.receipt_amount??(Number(s.total_amount||0)-Number(s.credit_amount||0)));const st=s.status==='voided'?'<span class="badge text-bg-danger">VOID</span>':'<span class="badge text-bg-success">POSTED</span>';const action=canVoidSales&&s.status==='posted'?'<button type="button" class="btn btn-sm btn-outline-danger void-sale" data-id="'+s.id+'" data-no="'+escapeHtml(s.sale_no)+'">Delete</button>':'';return '<tr class="sale-history-row" data-id="'+s.id+'" style="cursor:pointer"><td class="fw-semibold">'+escapeHtml(s.sale_no)+'</td><td>'+escapeHtml(s.transaction_at||'')+'</td><td>'+escapeHtml(s.customer_name||'Walk-in / Cash')+'</td><td>'+escapeHtml(historyTypeLabel(s.transaction_type))+'</td><td class="text-end">'+Number(s.subtotal||0).toFixed(2)+'</td><td class="text-end">'+Number(s.discount_amount||0).toFixed(2)+'</td><td class="text-end">'+Number(s.total_amount||0).toFixed(2)+'</td><td class="text-end">'+Number(s.previous_os_balance||0).toFixed(2)+'</td><td class="text-end">'+Number(received).toFixed(2)+'</td><td class="text-end">'+Number(s.net_receivable_amount??s.total_amount??0).toFixed(2)+'</td><td class="text-end">'+Number(s.os_balance??s.credit_amount??0).toFixed(2)+'</td><td>'+st+'</td><td>'+escapeHtml(s.created_by_name||'-')+'</td><td class="text-end">'+action+'</td></tr>';}).join('');historyBody.querySelectorAll('.sale-history-row').forEach(row=>row.addEventListener('click',e=>{if(!e.target.closest('.void-sale'))showSaleDetails(Number(row.dataset.id));}));historyBody.querySelectorAll('.void-sale').forEach(b=>b.addEventListener('click',e=>{e.stopPropagation();voidSale(Number(b.dataset.id),b.dataset.no);}));}catch(e){historyBody.innerHTML='<tr><td colspan="14" class="text-center text-danger py-4">'+escapeHtml(e.message)+'</td></tr>';}}
async function showSaleDetails(id){const body=document.getElementById('saleDetailsBody');body.innerHTML='<div class="text-center py-4">Loading...</div>';new bootstrap.Modal(document.getElementById('saleDetailsModal')).show();try{const res=await fetch('<?=site_url('sales/details')?>/'+id,{headers:{Accept:'application/json'}});const d=await res.json();if(!res.ok)throw new Error(d.error||'Unable to load sale details.');const s=d.sale,ms=d.movements||[];const received=Number(s.receipt_amount??(Number(s.total_amount||0)-Number(s.credit_amount||0)));const items=(d.items||[]).map(i=>{const src=ms.find(m=>Number(m.source_line_id)===Number(i.id)&&m.inventory_type==='gas_kg'&&m.direction==='out');const rateLabel=s.transaction_type==='cylinder_sale'?(i.line_type==='filled_cylinder'?'Gas '+Number(i.gas_rate||0).toFixed(2)+' / Cyl '+Number(i.cylinder_price||0).toFixed(2):'Cyl '+Number(i.cylinder_price||0).toFixed(2)):Number(i.applied_rate||0).toFixed(2);return '<tr><td>'+i.line_no+'</td><td>'+escapeHtml(i.cylinder_code||'-')+' — '+escapeHtml(i.cylinder_name||'-')+'</td><td>'+escapeHtml(i.line_type||'-')+'</td><td>'+escapeHtml(src?.unit_code||i.customer_unit_code||'-')+'</td><td class="text-end">'+Number(i.quantity||0).toFixed(3)+'</td><td class="text-end">'+Number(i.gas_weight_kg||0).toFixed(3)+'</td><td class="text-end">'+rateLabel+'</td><td class="text-end">'+Number(i.line_total||0).toFixed(2)+'</td></tr>';}).join('');const pays=(d.payments||[]).map(p=>'<tr><td>'+escapeHtml(p.payment_mode||'')+'</td><td class="text-end">'+Number(p.amount||0).toFixed(2)+'</td><td>'+escapeHtml(p.reference_no||'-')+'</td></tr>').join('');const movs=ms.map(m=>'<tr><td>'+escapeHtml(m.unit_code||'-')+'</td><td>'+escapeHtml(m.inventory_type||'')+'</td><td>'+escapeHtml(m.direction||'')+'</td><td class="text-end">'+Number(m.quantity||0).toFixed(3)+'</td><td>'+escapeHtml(m.source_type||'')+'</td><td>'+escapeHtml(m.notes||'')+'</td></tr>').join('');const voidInfo=s.status==='voided'?'<div class="alert alert-danger py-2"><strong>VOID</strong> — '+escapeHtml(s.voided_at||'')+' by '+escapeHtml(s.voided_by_name||'-')+'<br>Reason: '+escapeHtml(s.void_reason||'-')+'</div>':'';body.innerHTML=voidInfo+'<div class="row g-2 mb-3"><div class="col-md-3"><strong>Sale No.</strong><br>'+escapeHtml(s.sale_no)+'</div><div class="col-md-3"><strong>Date / Time</strong><br>'+escapeHtml(s.transaction_at)+'</div><div class="col-md-3"><strong>Customer</strong><br>'+escapeHtml(s.customer_name||'Walk-in / Cash')+'</div><div class="col-md-3"><strong>Type</strong><br>'+escapeHtml(historyTypeLabel(s.transaction_type))+'</div></div><div class="row g-2 mb-3"><div class="col-md-3">Previous OS Balance<br><strong>'+Number(s.previous_os_balance||0).toFixed(2)+'</strong></div><div class="col-md-3">Subtotal<br><strong>'+Number(s.subtotal||0).toFixed(2)+'</strong></div><div class="col-md-3">Discount<br><strong>'+Number(s.discount_amount||0).toFixed(2)+'</strong></div><div class="col-md-3">Current Sale<br><strong>'+Number(s.total_amount||0).toFixed(2)+'</strong></div></div><div class="row g-2 mb-3"><div class="col-md-3">Net Receivable<br><strong>'+Number(s.net_receivable_amount??s.total_amount??0).toFixed(2)+'</strong></div><div class="col-md-3">Receipt Amount<br><strong>'+received.toFixed(2)+'</strong></div><div class="col-md-3">OS Balance<br><strong>'+Number(s.os_balance??s.credit_amount??0).toFixed(2)+'</strong></div><div class="col-md-3">Credit / OS (legacy)<br><strong>'+Number(s.credit_amount||0).toFixed(2)+'</strong></div></div><h6>Sale Lines</h6><div class="table-responsive"><table class="table table-sm"><thead><tr><th>#</th><th>Cylinder Type</th><th>Line Type</th><th>Physical / Customer Cylinder</th><th>Qty</th><th>Gas KG</th><th>Rate</th><th>Amount</th></tr></thead><tbody>'+items+'</tbody></table></div><h6 class="mt-3">Payments</h6><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Mode</th><th class="text-end">Amount</th><th>Reference</th></tr></thead><tbody>'+pays+'</tbody></table></div><h6 class="mt-3">Inventory / Reversal Movements</h6><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Physical Cylinder</th><th>Inventory</th><th>Direction</th><th>Qty</th><th>Source</th><th>Notes</th></tr></thead><tbody>'+movs+'</tbody></table></div>';}catch(e){body.innerHTML='<div class="alert alert-danger">'+escapeHtml(e.message)+'</div>';}}
function voidSale(id,no){if(!canVoidSales)return;if(!confirm('Void sale '+no+'? Stock, cash and customer ledger effects will be reversed. The original sale will remain as VOID.'))return;const reason=prompt('Enter void reason for '+no+':','');if(reason===null)return;if(!reason.trim()){alert('Void reason is required.');return;}const f=document.createElement('form');f.method='post';f.action='<?=site_url('sales/void')?>/'+id;const c=document.createElement('input');c.type='hidden';c.name='<?=csrf_token()?>';c.value='<?=csrf_hash()?>';f.appendChild(c);const x=document.createElement('input');x.type='hidden';x.name='void_reason';x.value=reason.trim();f.appendChild(x);document.body.appendChild(f);f.submit();}
document.getElementById('newSaleTab').onclick=()=>{document.getElementById('saleForm').style.display='';document.getElementById('saleHistoryPanel').style.display='none';document.getElementById('newSaleTab').className='btn btn-primary';document.getElementById('saleHistoryTab').className='btn btn-outline-primary';};
document.getElementById('saleHistoryTab').onclick=()=>{document.getElementById('saleForm').style.display='none';document.getElementById('saleHistoryPanel').style.display='';document.getElementById('newSaleTab').className='btn btn-outline-primary';document.getElementById('saleHistoryTab').className='btn btn-primary';loadSaleHistory();};
document.getElementById('historySearch').onclick=loadSaleHistory;
document.getElementById('historyAll').onclick=()=>{document.getElementById('historyFrom').value='2000-01-01';document.getElementById('historyTo').value=new Date().toISOString().slice(0,10);loadSaleHistory();};
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
 if(creditLimitMode==='none'){box.className='small mt-1 text-success';box.textContent='Credit Sale: Allowed — Credit Limit: Unlimited';return c;}
 if(creditLimitMode==='shop'){const available=Math.max(0,shopCreditLimit-shopOutstanding);if(available<=0){box.className='small mt-1 text-danger';box.textContent='Credit Sale: Not Allowed — Shop credit limit reached.';return c;}box.className='small mt-1 text-success';box.textContent='Credit Sale: Allowed — Credit Limit: '+shopCreditLimit.toFixed(2);return c;}
 const available=Math.max(0,c.limit-c.balance);if(available<=0){box.className='small mt-1 text-danger';box.textContent='Credit Sale: Not Allowed — Customer credit limit reached.';return c;}box.className='small mt-1 text-success';box.textContent='Credit Sale: Allowed — Credit Limit: '+c.limit.toFixed(2);return c;
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
 tr.innerHTML='<td><select class="form-select cyl">'+cylinderTypeOptions()+'</select></td><td class="sourceCell"><select class="form-select sourceCyl" disabled><option value="">Select source filled cylinder</option></select></td><td><input class="form-control entryValue" type="number" min="0" step="any" value="0" placeholder="KG"><input class="qty" type="hidden" value="0"><input class="enteredAmount" type="hidden"></td><td><div class="input-group input-group-sm"><span class="input-group-text"></span><input class="form-control gasRate" type="number" min="0" step="any"></div></td><td><select class="form-select targetCyl" disabled></select></td><td class="lineTotal">0.00</td><td><button type="button" class="btn btn-sm btn-outline-danger remove">×</button></td>';
 const mode=()=>document.getElementById('gasEntryMode').value;
 tr.dataset.entryMode=mode();tr.querySelector('.gasRate').value=kgRate!==null?Number(kgRate).toFixed(2):'';tr.querySelector('.gasRate').disabled=mode()==='amount';
 tr.querySelector('.cyl').onchange=()=>{refreshDuplicateTypeOptions();refreshGasSourceLine(tr,true);const t=tr.querySelector('.targetCyl');t.disabled=!selectedCustomerId();t.innerHTML=refillLineOptions(tr.querySelector('.cyl').value);recalc();};
 tr.querySelector('.entryValue').oninput=()=>{const m=mode(),v=Number(tr.querySelector('.entryValue').value||0),rate=Number(tr.querySelector('.gasRate').value||0);tr.dataset.entryMode=m;if(m==='amount'){tr.querySelector('.enteredAmount').value=v>0?v.toFixed(2):'';tr.querySelector('.qty').value=rate>0&&v>0?(v/rate).toFixed(3):'0';}else{tr.querySelector('.qty').value=v;tr.querySelector('.enteredAmount').value=rate>0&&v>0?(v*rate).toFixed(2):'';}refreshGasSourceLine(tr,false);recalc();};
 tr.querySelector('.gasRate').oninput=()=>{if(mode()==='amount')return;const rate=Number(tr.querySelector('.gasRate').value||0),q=Number(tr.querySelector('.qty').value||0);tr.querySelector('.enteredAmount').value=rate>0&&q>0?(q*rate).toFixed(2):'';recalc();};
 tr.querySelector('.targetCyl').innerHTML=refillLineOptions('');tr.querySelector('.targetCyl').disabled=!selectedCustomerId();tr.querySelector('.sourceCyl').onchange=()=>recalc();tr.querySelector('.remove').onclick=()=>{tr.remove();recalc();};tbody.appendChild(tr);
 tr.querySelector('.sourceCell').style.display=allowPosSourceCylinderSelection?'':'none';refreshGasSourceLine(tr,true);refreshDuplicateTypeOptions();
}
function rebuildLines(){
 clearLines();const t=transactionType();lineHead.innerHTML='';document.getElementById('lines').classList.remove('gas-sale-mode','cylinder-sale-mode');
 if(t==='gas_sale'){document.getElementById('lines').classList.add('gas-sale-mode');lineHead.innerHTML='<tr><th>Cylinder Type</th><th class="sourceHead" style="display:'+(allowPosSourceCylinderSelection?'table-cell':'none')+'">Source Filled Cylinder</th><th>Qty / KG</th><th>Gas Rate</th><th>Customer Cylinder</th><th>Amount</th><th></th></tr>';addGasLine();document.getElementById('addLine').style.display='inline-block';}
 else if(t==='cylinder_sale'){document.getElementById('lines').classList.add('cylinder-sale-mode');lineHead.innerHTML='<tr><th>Cylinder Type</th><th>Status</th><th>Qty</th><th>Gas KG</th><th>Gas Rate</th><th>Cylinder Rate</th><th>Amount</th><th></th></tr>';addCylinderSaleLine();document.getElementById('addLine').style.display='inline-block';}
 else {document.getElementById('addLine').style.display='none';}
}
function setCustodyLists(){
 const sel=document.getElementById('custodyUnits');
 const current=[...sel.selectedOptions].map(o=>o.value);
 sel.innerHTML=availableCustodyUnits.map(u=>'<option value="'+u.id+'">'+u.unit_code+' — '+u.cylinder_code+' — '+u.cylinder_name+' — '+(u.status==='filled'?'Filled '+Number(u.gas_weight_kg||0).toFixed(2)+' KG':'Empty')+'</option>').join('');
 current.forEach(v=>{const o=[...sel.options].find(x=>x.value===v);if(o)o.selected=true;});
 const ret=document.getElementById('returnUnits'),old=[...ret.selectedOptions].map(o=>o.value);
 ret.innerHTML=allCustomerCustody.filter(u=>String(u.customer_id)===String(selectedCustomerId())).map(u=>'<option value="'+u.unit_id+'">'+u.unit_code+' — '+u.cylinder_code+' — '+u.cylinder_name+' — Gas '+Number(u.gas_weight_kg||0).toFixed(2)+' KG — Deposit '+Number(u.deposit_amount||0).toFixed(2)+'</option>').join('');
 old.forEach(v=>{const o=[...ret.options].find(x=>x.value===v);if(o)o.selected=true;});
 updateRefund();
}
function updateRefund(){
 const ids=[...document.getElementById('returnUnits').selectedOptions].map(o=>Number(o.value));
 const refund=allCustomerCustody.filter(u=>ids.includes(Number(u.unit_id))).reduce((s,u)=>s+Number(u.deposit_amount||0),0);
 document.getElementById('refundPreview').textContent=refund.toFixed(2);document.getElementById('refundAmount').textContent=refund.toFixed(2);
}
function refreshPaymentModes(){const walkIn=!selectedCustomerId(),customer=selectedCustomer();payments.querySelectorAll('.payment').forEach(p=>{const m=p.querySelector('.mode');[...m.options].forEach(o=>o.disabled=walkIn&&o.value!=='cash'||(!walkIn&&!customer?.allowCredit&&o.value==='credit'));if(walkIn||(!customer?.allowCredit&&m.value==='credit'))m.value='cash';});}
function addPayment(){
 const div=document.createElement('div');div.className='input-group mb-2 payment';
 div.innerHTML='<select class="form-select mode"><option value="cash">Cash</option><option value="cheque">Cheque</option><option value="online">Online</option><option value="credit">Credit</option></select><input class="form-control amount" type="number" min="0.01" step="any" placeholder="Amount"><input class="form-control ref" placeholder="Ref"><button type="button" class="btn btn-outline-danger remove">×</button>';
 payments.appendChild(div);div.querySelector('.mode').value=defaultPaymentMode;div.querySelector('.mode').onchange=()=>{refreshPaymentModes();recalc();};div.querySelector('.amount').oninput=recalc;div.querySelector('.remove').onclick=()=>{div.remove();recalc();};refreshPaymentModes();
}
function paymentTotal(){return [...payments.querySelectorAll('.payment')].reduce((s,p)=>s+Number(p.querySelector('.amount').value||0),0);}
function statusForCylinderSale(tr){return tr.querySelector('.cylStatus')?.value||'empty';}
function cylinderTypeById(typeId){return types.find(t=>String(t.id)===String(typeId))||null;}
function selectedFilledUnits(tr){const ids=Array.isArray(tr._selectedUnitIds)?tr._selectedUnitIds:[],typeId=tr.querySelector('.cyl').value,available=filledUnits[typeId]||[];return ids.map(id=>available.find(u=>String(u.id)===String(id))).filter(Boolean);}
function renderFilledCylinderPicker(tr){const typeId=tr.querySelector('.cyl').value,picker=tr._pickerRow.querySelector('.cylinderPicker'),status=tr.querySelector('.cylStatus').value;if(status!=='filled'||!typeId){picker.innerHTML='<div class="text-muted small">Select a cylinder type to view available physical cylinders.</div>';return;}const units=(filledUnits[typeId]||[]).filter(u=>Number(u.gas_weight_kg||0)>0.00001);if(!units.length){picker.innerHTML='<div class="alert alert-warning py-2 mb-0">No filled/partially filled cylinders are currently available for this cylinder type.</div>';tr._selectedUnitIds=[];return;}const selected=new Set((tr._selectedUnitIds||[]).map(String));picker.innerHTML='<div class="d-flex justify-content-between align-items-center cylinder-picker-title"><strong>Select physical cylinder(s)</strong><span class="small text-muted">'+units.length+' available</span></div><div class="d-flex flex-wrap cylinder-options">'+units.map(u=>{const checked=selected.has(String(u.id))?' checked':'';const gas=Number(u.gas_weight_kg||0),cap=Number(u.capacity_kg||0);const statusLabel=gas>=cap-0.00001?'Filled':'Partial';const statusClass=statusLabel==='Filled'?'text-success':'text-warning';return '<label class="btn btn-sm btn-outline-secondary text-start cylinder-option position-relative"><input class="filledUnitCheck" type="checkbox" value="'+u.id+'"'+checked+'><svg class="cylinder-art" viewBox="0 0 40 50" aria-hidden="true"><rect x="9" y="6" width="22" height="38" rx="7" fill="currentColor" opacity=".12"></rect><rect x="11" y="8" width="18" height="34" rx="6" fill="none" stroke="currentColor" stroke-width="2"></rect><path d="M15 8V4h10v4M17 4V1h6v3" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path><path d="M14 14h12M14 18h12" stroke="currentColor" stroke-width="1.5" opacity=".65"></path></svg><span class="cylinder-copy"><span class="cylinder-code">'+escapeHtml(u.cylinder_code||u.unit_code||'Cylinder')+'</span><span class="cylinder-name">'+escapeHtml(u.cylinder_name||'')+'</span><span class="cylinder-gas"><strong>'+gas.toFixed(3)+' KG</strong> <span class="cylinder-status '+statusClass+'">'+statusLabel+'</span></span></span></label>';}).join('')+'</div>';picker.querySelectorAll('.filledUnitCheck').forEach(cb=>cb.onchange=()=>{tr._selectedUnitIds=[...picker.querySelectorAll('.filledUnitCheck:checked')].map(x=>Number(x.value));syncFilledCylinderLine(tr);});}
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
 const discount=Math.max(0,Number(document.getElementById('discount').value||0));saleTotal=Math.max(0,saleTotal-discount);
 const customer=selectedCustomer(),previousOs=customer?Math.max(0,customer.balance):0;
 const deposit=t==='security_deposit'?Math.max(0,Number(document.getElementById('securityDeposit').value||0)):0;
 const netReceivable=t==='security_deposit'?deposit:(t==='cylinder_return'?0:(saleTotal+previousOs));
 document.getElementById('saleTotal').textContent=saleTotal.toFixed(2);
 document.getElementById('previousOs').textContent=previousOs.toFixed(2);
 document.getElementById('netPayable').textContent=netReceivable.toFixed(2);
 const paid=t==='cylinder_return'?0:paymentTotal();
 const balanceAfter=Math.max(0,netReceivable-paid);
 document.getElementById('receiptAmountValue').textContent=paid.toFixed(2);
 document.getElementById('customerOsBalanceValue').textContent=balanceAfter.toFixed(2);
 document.getElementById('securityDepositBox').style.display=t==='security_deposit'?'block':'none';
 updateRefund();
}
function refreshForm(){
 const t=transactionType(),standard=['gas_sale','cylinder_sale'].includes(t);
 document.getElementById('standardTransaction').style.display=standard?'block':'none';document.getElementById('gasEntryMode').closest('.col-md-3').style.display=t==='gas_sale'?'block':'none';
 document.getElementById('securityTransaction').style.display=t==='security_deposit'?'block':'none';
 document.getElementById('returnTransaction').style.display=t==='cylinder_return'?'block':'none';
 document.getElementById('securityDeposit').disabled=t!=='security_deposit'; document.getElementById('securityDepositBox').style.display=t==='security_deposit'?'block':'none'; document.getElementById('discount').disabled=!standard; if(!standard)document.getElementById('discount').value='0';
 if(t!=='security_deposit')document.getElementById('securityDeposit').value='0';
 document.getElementById('refundBox').style.display=t==='cylinder_return'?'block':'none';
 document.getElementById('paymentSection').style.display=t==='cylinder_return'?'none':'block';
 document.getElementById('saveBtn').textContent=t==='cylinder_return'?'Return Cylinder / Refund Deposit':t==='security_deposit'?'Receive Deposit / Issue Cylinder':'Post Transaction';
 if(standard)rebuildLines();else{clearLines();lineHead.innerHTML='';}
 setCustodyLists();refreshPaymentModes();recalc();
}
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
  document.getElementById('custodyUnits').selectedIndex=-1;
  document.getElementById('returnUnits').selectedIndex=-1;
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
document.getElementById('discount').oninput=recalc;document.getElementById('securityDeposit').oninput=recalc;document.getElementById('returnUnits').onchange=updateRefund;document.getElementById('addPayment').onclick=addPayment;
document.getElementById('saleForm').onsubmit=()=>{
 const t=transactionType(),customerId=selectedCustomerId();
 const earlyModes=[...payments.querySelectorAll('.payment .mode')].map(x=>x.value);
 if(['gas_sale','cylinder_sale'].includes(t)&&!customerId&&earlyModes.includes('credit')){alert('Credit sale is not allowed for Walk-in / Cash customer.');return false;}
 let lines=[];
 if(t==='gas_sale'){
   lines=[...tbody.querySelectorAll('tr')].map(tr=>({cylinder_type_id:tr.querySelector('.cyl').value,source_cylinder_unit_id:tr.querySelector('.sourceCyl').value,quantity:tr.querySelector('.qty').value,gas_rate:tr.querySelector('.gasRate').value,entered_amount:document.getElementById('gasEntryMode').value==='amount'?tr.querySelector('.enteredAmount').value:'',customer_cylinder_unit_id:tr.querySelector('.targetCyl').value}));
   if(!lines.length){alert('Add at least one gas line.');return false;}
   const seenTypes=new Set(),seenSources=new Set();
   for(const [i,l] of lines.entries()){
     const q=Number(l.quantity||0),a=Number(l.entered_amount||0);
     if(!l.cylinder_type_id || (document.getElementById('gasEntryMode').value==='amount' ? a<=0 : q<=0)){alert('Gas line '+(i+1)+' is invalid. Enter a valid '+(document.getElementById('gasEntryMode').value==='amount'?'amount':'quantity')+'.');return false;}
     if(seenTypes.has(String(l.cylinder_type_id))){alert('Cylinder type cannot be used on multiple gas sale lines. Combine the quantity into one line.');return false;}
     seenTypes.add(String(l.cylinder_type_id));
     if(allowPosSourceCylinderSelection){
       if(!l.source_cylinder_unit_id){alert('Gas line '+(i+1)+' requires a source filled cylinder.');return false;}
       if(seenSources.has(String(l.source_cylinder_unit_id))){alert('The same source filled cylinder cannot be selected on multiple gas sale lines.');return false;}
       seenSources.add(String(l.source_cylinder_unit_id));
       const src=(filledUnits[l.cylinder_type_id]||[]).find(u=>String(u.id)===String(l.source_cylinder_unit_id));
       if(!src){alert('Gas line '+(i+1)+' source cylinder is no longer available.');return false;}
       if(q>Number(src.gas_weight_kg||0)+0.00001){alert('Gas line '+(i+1)+' cannot exceed the selected source cylinder gas stock of '+Number(src.gas_weight_kg||0).toFixed(2)+' KG.');return false;}
     }else{
       l.source_cylinder_unit_id='';
       const available=(filledUnits[l.cylinder_type_id]||[]).reduce((s,u)=>s+Number(u.gas_weight_kg||0),0);
       if(q>available+0.00001){alert('Gas line '+(i+1)+' cannot exceed available stock of this cylinder type: '+available.toFixed(2)+' KG.');return false;}
     }
   }
 }else if(t==='cylinder_sale'){
   lines=[...tbody.querySelectorAll('tr:not(.cylinderPickerRow)')].map(tr=>({cylinder_type_id:tr.querySelector('.cyl').value,cylinder_status:tr.querySelector('.cylStatus').value,quantity:tr.querySelector('.qty').value,gas_weight_kg:tr.querySelector('.gasQty').value,gas_rate:tr.querySelector('.gasRate').value,cylinder_rate:tr.querySelector('.cylRate').value,selected_cylinder_unit_ids:tr._selectedUnitIds||[]}));
   if(!lines.length){alert('Add at least one cylinder sale line.');return false;}
   for(const [i,l] of lines.entries()){const q=Number(l.quantity||0);if(!l.cylinder_type_id||q<=0||q!==Math.floor(q)){alert('Cylinder sale line '+(i+1)+' requires a whole-number quantity.');return false;}if(l.cylinder_status==='filled'&&(!Array.isArray(l.selected_cylinder_unit_ids)||l.selected_cylinder_unit_ids.length!==q)){alert('Cylinder sale line '+(i+1)+' requires selecting exactly '+q+' physical cylinder(s).');return false;}}
 }else if(t==='security_deposit'){
   const units=[...document.getElementById('custodyUnits').selectedOptions].map(o=>Number(o.value));if(!customerId){alert('Select a customer for Security Deposit.');return false;}if(!units.length){alert('Select at least one cylinder to issue on custody.');return false;}if(Number(document.getElementById('securityDeposit').value||0)<=0){alert('Enter a Security Deposit Amount.');return false;}
 }else if(t==='cylinder_return'){
   const units=[...document.getElementById('returnUnits').selectedOptions].map(o=>Number(o.value));if(!customerId){alert('Select a customer for Cylinder Return.');return false;}if(!units.length){alert('Select at least one customer custody cylinder to return.');return false;}
 }
 const pays=t==='cylinder_return'?[]:[...payments.querySelectorAll('.payment')].map(p=>({payment_mode:p.querySelector('.mode').value,amount:p.querySelector('.amount').value,reference_no:p.querySelector('.ref').value}));
 // Serialize immediately after building the two payload arrays. This keeps the server
 // payload valid even if a later client-side validation check throws unexpectedly.
 document.getElementById('lines_json').value=JSON.stringify(lines);
 document.getElementById('payments_json').value=JSON.stringify(pays);
 const saleTotal=Number(document.getElementById('saleTotal').textContent||0),previousOs=customerId?Number(document.getElementById('previousOs').textContent||0):0,deposit=t==='security_deposit'?Number(document.getElementById('securityDeposit').value||0):0;
 const expected=t==='security_deposit'?deposit:(t==='cylinder_return'?0:saleTotal+previousOs);
 if(t!=='cylinder_return'&&!pays.length){alert('Add at least one payment.');return false;}
 if(t!=='cylinder_return'&&paymentTotal()>expected+0.01){alert('Payment cannot exceed the Net Amount Receivable of '+expected.toFixed(2)+'.');return false;}
 if(!customerId&&pays.some(p=>p.payment_mode!=='cash')){alert('Walk-in transactions are cash only.');return false;}
 if(customerId&&pays.some(p=>p.payment_mode==='credit')&&!selectedCustomer()?.allowCredit){alert('Credit sale is not allowed for this customer. Enable Allow Credit Sale on the customer record.');return false;}
 if(['gas_sale','cylinder_sale'].includes(t)&&customerId){
   const customer=selectedCustomer();
   const newOs=Math.max(0,expected-paymentTotal());
   if(newOs>previousOs+0.01&&!customer?.allowCredit){alert('Credit sale is not allowed for this customer. Enable Allow Credit Sale on the customer record.');return false;}
   if(customer?.allowCredit){
     const limit=Number(customer.limit||0);
     if(newOs>limit+0.01){alert('Credit limit exceeded. Current OS is '+previousOs.toFixed(2)+', resulting OS would be '+newOs.toFixed(2)+', and the allowed limit is '+limit.toFixed(2)+'.');return false;}
   }
 }
 if(!customerId&&['gas_sale','cylinder_sale'].includes(t)&&Math.abs(paymentTotal()-expected)>0.01){alert('Walk-in sale must be fully paid. Received amount must equal sale total.');return false;}
 let gasRequired=0; if(t==='gas_sale')gasRequired=lines.reduce((s,l)=>s+Number(l.quantity||0),0); else if(t==='cylinder_sale')tbody.querySelectorAll('tr').forEach(tr=>{gasRequired+=gasForCylinderSale(tr);});
 if((t==='gas_sale'||t==='cylinder_sale')&&gasRequired>Number(gasStock||0)+0.00001){
   if(!confirm('Available gas stock is '+Number(gasStock||0).toFixed(2)+' KG, but this transaction requires '+gasRequired.toFixed(2)+' KG. Continue?'))return false;
   document.getElementById('stock_override_confirmed').value='1';
 }
 return true;
};
addPayment();document.getElementById('transactionType').value=defaultTransactionType;refreshCustomer();refreshForm();
</script>
<?= $this->endSection() ?>