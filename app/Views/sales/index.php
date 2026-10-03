<?= $this->extend('layouts/app') ?>
<?= $this->section('styles') ?>
<style>
.pos-workspace > .pos-lines-panel { flex: 0 0 75%; max-width: 75%; }
.pos-workspace > .pos-summary-panel { flex: 0 0 25%; max-width: 25%; }
#lines { width:100%; table-layout:fixed; }
#lines th,#lines td { padding:.65rem .5rem; vertical-align:middle; }
#lines th:nth-child(1){width:24%} #lines th:nth-child(2){width:12%} #lines th:nth-child(3){width:16%}
#lines th:nth-child(4){width:16%} #lines th:nth-child(5){width:16%} #lines th:nth-child(6){width:11%} #lines th:nth-child(7){width:5%}
#lines .form-select,#lines .form-control { min-height:42px; }
#lines .lineTotal { font-size:1.05rem; white-space:nowrap; }
#lines .remove { min-width:38px; min-height:38px; }
.custody-list { max-height:250px; overflow:auto; }
.custody-list option { padding:4px; }
@media (max-width:1199.98px){
  .pos-workspace > .pos-lines-panel,.pos-workspace > .pos-summary-panel{flex:0 0 100%;max-width:100%}
}
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

<form method="post" action="<?=site_url('sales/save')?>" id="saleForm"><?=csrf_field()?>
<input type="hidden" name="lines_json" id="lines_json">
<input type="hidden" name="payments_json" id="payments_json">
<input type="hidden" name="stock_override_confirmed" id="stock_override_confirmed" value="0">

<div class="row g-3 pos-workspace">
<div class="col-lg-9 pos-lines-panel"><div class="card"><div class="card-body">

<div class="row g-3 mb-3">
  <div class="col-md-4">
    <label class="form-label fw-semibold">Transaction Type</label>
    <select name="transaction_type" id="transactionType" class="form-select">
      <option value="gas_sale">Gas Sale / Refill</option>
      <option value="cylinder_sale">Cylinder Sale</option>
      <option value="security_deposit">Security Deposit / Issue Cylinder</option>
      <option value="cylinder_return">Cylinder Return / Refund Deposit</option>
    </select>
    <div class="form-text">One transaction type per invoice.</div>
  </div>
  <div class="col-md-4">
    <label class="form-label">Customer</label>
    <select name="customer_id" id="customer_id" class="form-select">
      <option value="">Walk-in / Cash</option>
      <?php foreach($customers as $c): ?><option value="<?=$c['id']?>"><?=esc(($c['code']?$c['code'].' — ':'').$c['name'])?></option><?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-4">
    <label class="form-label">Transaction Time</label>
    <input name="transaction_at" type="datetime-local" class="form-control" value="<?=date('Y-m-d\TH:i')?>">
  </div>
</div>
<div id="customerInfo" class="alert alert-light border py-2 small mb-3">Walk-in: cash sales are allowed. Security Deposit and Cylinder Return require a named customer.</div>

<div id="standardTransaction">
  <div class="d-flex justify-content-between align-items-center mb-2">
    <h6 class="mb-0" id="linesTitle">Gas / Refill Lines</h6>
    <span class="badge text-bg-light border" id="typeHint">Gas is deducted from company stock.</span>
  </div>
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
      <div class="form-text">The selected physical cylinders remain company property and move to customer custody. Security deposit is held as a refundable liability.</div>
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
      <div class="form-text">Only the actual physical cylinder can be returned. It must be empty before the security deposit is refunded.</div>
      <div class="mt-3 p-2 bg-light rounded">Refundable Deposit: <strong>Rs. <span id="refundPreview">0.00</span></strong></div>
    </div>
  </div>
</div>

</div></div></div>

<div class="col-lg-3 pos-summary-panel"><div class="card pos-summary-card"><div class="card-body">
<div class="mb-3">
  <label class="form-label fw-semibold">Current Sale</label>
  <div class="form-control bg-light fw-semibold">Rs. <span id="saleTotal">0.00</span></div>
</div>
<div class="mb-3">
  <label class="form-label">Previous OS Balance</label>
  <div class="form-control bg-light">Rs. <span id="previousOs">0.00</span></div>
</div>
<div class="mb-3">
  <label class="form-label">Discount</label>
  <input name="discount_amount" id="discount" type="number" min="0" step="any" value="0" class="form-control">
</div>
<div class="mb-3">
  <label class="form-label fw-semibold">Security Deposit Amount</label>
  <input name="security_deposit_amount" id="securityDeposit" type="number" min="0" step="any" value="0" class="form-control">
  <div class="form-text" id="depositHelp">Used only for Security Deposit / Issue Cylinder.</div>
</div>
<div class="mb-3">
  <label class="form-label">Net Amount Payable</label>
  <div class="form-control bg-light fw-bold">Rs. <span id="netPayable">0.00</span></div>
  <div class="small text-muted mt-1">Current Sale + Previous OS + Security Deposit</div>
</div>
<div class="mb-3" id="refundBox" style="display:none">
  <div class="form-control bg-light text-danger fw-semibold">Customer Refund: Rs. <span id="refundAmount">0.00</span></div>
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
const types=<?=json_encode(array_values($types),JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP)?>;
const rates=<?=json_encode($cylinderRates)?>,kgRate=<?=json_encode($kgRate)?>;
const filledStock=<?=json_encode($filledStock)?>,filledUnits=<?=json_encode($filledUnits)?>,emptyStock=<?=json_encode($emptyStock)?>,gasStock=<?=json_encode($gasStock)?>;
const balances=<?=json_encode($balances)?>,creditLimits=<?=json_encode($creditLimits)?>;
const availableCustodyUnits=<?=json_encode($availableCustodyUnits)?>,allCustomerCustody=<?=json_encode($custodyUnits)?>;
const defaultTransactionType='gas_sale',defaultPaymentMode=<?=json_encode($shopSettings['default_payment_mode']??'cash')?>;
const tbody=document.querySelector('#lines tbody'),lineHead=document.getElementById('lineHead'),payments=document.getElementById('payments');

function transactionType(){return document.getElementById('transactionType').value;}
function selectedCustomerId(){return document.getElementById('customer_id').value;}
function selectedCustomer(){const id=selectedCustomerId();return id?{balance:Number(balances[id]||0),limit:Number(creditLimits[id]||0)}:null;}
function refreshCustomer(){
  const c=selectedCustomer(),box=document.getElementById('customerInfo');
  if(!c){box.textContent='Walk-in: cash sales are allowed. Security Deposit and Cylinder Return require a named customer.';return;}
  const available=Math.max(0,c.limit-c.balance);
  box.textContent='Previous OS: Rs. '+c.balance.toFixed(2)+' | Credit Limit: Rs. '+c.limit.toFixed(2)+' | Available Credit: Rs. '+available.toFixed(2);
}
function companyUnitsFor(status,typeId){
  return availableCustodyUnits.filter(u=>(!status||u.status===status)&&(!typeId||String(u.cylinder_type_id)===String(typeId)));
}
function custodyOptions(){
  return '<option value="">Select customer cylinder (optional)</option>'+allCustomerCustody.filter(u=>String(u.customer_id)===String(selectedCustomerId())).map(u=>'<option value="'+u.unit_id+'">'+u.unit_code+' — '+u.cylinder_code+' — '+u.cylinder_name+' — '+Number(u.gas_weight_kg||0).toFixed(2)+' KG — Deposit Rs. '+Number(u.deposit_amount||0).toFixed(2)+'</option>').join('');
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
function addGasLine(){
 const tr=document.createElement('tr');
 tr.innerHTML='<td><select class="form-select cyl">'+cylinderTypeOptions()+'</select></td><td><input class="form-control qty" type="number" min="0" step="any" value="1"></td><td><div class="input-group input-group-sm"><span class="input-group-text">Rs.</span><input class="form-control gasRate" type="number" min="0" step="any"></div></td><td><select class="form-select targetCyl" disabled></select></td><td class="lineTotal">Rs. 0.00</td><td><button type="button" class="btn btn-sm btn-outline-danger remove">×</button></td>';
 tr.querySelector('.cyl').onchange=()=>{const typeId=tr.querySelector('.cyl').value;tr.querySelector('.gasRate').value=kgRate!==null?Number(kgRate).toFixed(2):'';const t=tr.querySelector('.targetCyl');t.disabled=!selectedCustomerId();t.innerHTML=refillLineOptions(typeId);recalc();};
 tr.querySelector('.qty').oninput=recalc;tr.querySelector('.gasRate').value=kgRate!==null?Number(kgRate).toFixed(2):'';
 tr.querySelector('.qty').oninput=recalc;tr.querySelector('.gasRate').oninput=recalc;tr.querySelector('.targetCyl').innerHTML=refillLineOptions('');tr.querySelector('.targetCyl').disabled=!selectedCustomerId();tr.querySelector('.remove').onclick=()=>{tr.remove();recalc();};tbody.appendChild(tr);
}
function addCylinderSaleLine(){
 const tr=document.createElement('tr');
 tr.innerHTML='<td><select class="form-select cyl">'+cylinderTypeOptions()+'</select></td><td><select class="form-select cylStatus"><option value="filled">Filled</option><option value="empty">Empty</option></select></td><td><input class="form-control qty" type="number" min="0" step="1" value="1"></td><td><div class="input-group input-group-sm gasWrap"><span class="input-group-text">Rs/KG</span><input class="form-control gasRate" type="number" min="0" step="any"></div></td><td><div class="input-group input-group-sm"><span class="input-group-text">Rs.</span><input class="form-control cylRate" type="number" min="0" step="any"></div></td><td class="lineTotal">Rs. 0.00</td><td><button type="button" class="btn btn-sm btn-outline-danger remove">×</button></td>';
 tr.querySelector('.cyl').onchange=refreshCylinderSaleLine;tr.querySelector('.cylStatus').onchange=refreshCylinderSaleLine;tr.querySelector('.qty').oninput=recalc;tr.querySelector('.gasRate').oninput=recalc;tr.querySelector('.cylRate').oninput=recalc;tr.querySelector('.remove').onclick=()=>{tr.remove();recalc();};tbody.appendChild(tr);refreshCylinderSaleLine.call(tr);
}
function refreshCylinderSaleLine(){
 const tr=this.tagName==='TR'?this:tbody.querySelector('tr:last-child'),typeId=tr.querySelector('.cyl').value,status=tr.querySelector('.cylStatus').value;
 tr.querySelector('.gasWrap').style.display=status==='filled'?'flex':'none';
 tr.querySelector('.gasRate').value=status==='filled'&&kgRate!==null?Number(kgRate).toFixed(2):'';
 tr.querySelector('.cylRate').value=typeId&&rates[typeId]!=null?Number(rates[typeId]).toFixed(2):'';
 recalc();
}
function rebuildLines(){
 clearLines();const t=transactionType();lineHead.innerHTML='';
 if(t==='gas_sale'){lineHead.innerHTML='<tr><th>Cylinder Type</th><th>Qty / KG</th><th>Gas Rate</th><th>Customer Cylinder</th><th>Amount</th><th></th></tr>';addGasLine();document.getElementById('linesTitle').textContent='Gas / Refill Lines';document.getElementById('typeHint').textContent='Company gas stock is deducted; customer custody cylinder is optional.';document.getElementById('addLine').style.display='inline-block';}
 else if(t==='cylinder_sale'){lineHead.innerHTML='<tr><th>Cylinder Type</th><th>Status</th><th>Qty</th><th>Gas Rate</th><th>Cylinder Rate</th><th>Amount</th><th></th></tr>';addCylinderSaleLine();document.getElementById('linesTitle').textContent='Cylinder Sale Lines';document.getElementById('typeHint').textContent='Physical cylinders are sold outright and leave company ownership.';document.getElementById('addLine').style.display='inline-block';}
 else {document.getElementById('addLine').style.display='none';}
}
function setCustodyLists(){
 const sel=document.getElementById('custodyUnits');
 const current=[...sel.selectedOptions].map(o=>o.value);
 sel.innerHTML=availableCustodyUnits.map(u=>'<option value="'+u.id+'">'+u.unit_code+' — '+u.cylinder_code+' — '+u.cylinder_name+' — '+(u.status==='filled'?'Filled '+Number(u.gas_weight_kg||0).toFixed(2)+' KG':'Empty')+'</option>').join('');
 current.forEach(v=>{const o=[...sel.options].find(x=>x.value===v);if(o)o.selected=true;});
 const ret=document.getElementById('returnUnits'),old=[...ret.selectedOptions].map(o=>o.value);
 ret.innerHTML=allCustomerCustody.filter(u=>String(u.customer_id)===String(selectedCustomerId())).map(u=>'<option value="'+u.unit_id+'">'+u.unit_code+' — '+u.cylinder_code+' — '+u.cylinder_name+' — Gas '+Number(u.gas_weight_kg||0).toFixed(2)+' KG — Deposit Rs. '+Number(u.deposit_amount||0).toFixed(2)+'</option>').join('');
 old.forEach(v=>{const o=[...ret.options].find(x=>x.value===v);if(o)o.selected=true;});
 updateRefund();
}
function updateRefund(){
 const ids=[...document.getElementById('returnUnits').selectedOptions].map(o=>Number(o.value));
 const refund=allCustomerCustody.filter(u=>ids.includes(Number(u.unit_id))).reduce((s,u)=>s+Number(u.deposit_amount||0),0);
 document.getElementById('refundPreview').textContent=refund.toFixed(2);document.getElementById('refundAmount').textContent=refund.toFixed(2);
}
function refreshPaymentModes(){const walkIn=!selectedCustomerId();payments.querySelectorAll('.payment').forEach(p=>{const m=p.querySelector('.mode');[...m.options].forEach(o=>o.disabled=walkIn&&o.value!=='cash');if(walkIn)m.value='cash';});}
function addPayment(){
 const div=document.createElement('div');div.className='input-group mb-2 payment';
 div.innerHTML='<select class="form-select mode"><option value="cash">Cash</option><option value="cheque">Cheque</option><option value="online">Online</option><option value="credit">Credit</option></select><input class="form-control amount" type="number" min="0.01" step="any" placeholder="Amount"><input class="form-control ref" placeholder="Ref"><button type="button" class="btn btn-outline-danger remove">×</button>';
 payments.appendChild(div);div.querySelector('.mode').value=defaultPaymentMode;div.querySelector('.mode').onchange=refreshPaymentModes;div.querySelector('.remove').onclick=()=>{div.remove();};refreshPaymentModes();
}
function paymentTotal(){return [...payments.querySelectorAll('.payment')].reduce((s,p)=>s+Number(p.querySelector('.amount').value||0),0);}
function gasForCylinderSale(tr){
 const typeId=tr.querySelector('.cyl').value,status=tr.querySelector('.cylStatus').value,qty=Math.floor(Number(tr.querySelector('.qty').value||0));
 if(status!=='filled'||!typeId||qty<=0)return 0;
 return (filledUnits[typeId]||[]).slice(0,qty).reduce((s,u)=>s+Number(u.gas_weight_kg||0),0);
}
function recalc(){
 let saleTotal=0,gasRequired=0;
 const t=transactionType();
 tbody.querySelectorAll('tr').forEach(tr=>{
   const qty=Math.max(0,Number(tr.querySelector('.qty')?.value||0));
   let amount=0,gas=0;
   if(t==='gas_sale'){gas=qty;const rate=Number(tr.querySelector('.gasRate').value||0);amount=gas*rate;}
   else if(t==='cylinder_sale'){gas=gasForCylinderSale(tr);const gr=Number(tr.querySelector('.gasRate').value||0),cr=Number(tr.querySelector('.cylRate').value||0),q=Math.floor(qty);amount=gas*gr+q*cr;}
   gasRequired+=gas;saleTotal+=amount;tr.querySelector('.lineTotal').textContent='Rs. '+amount.toFixed(2);
 });
 const discount=Math.max(0,Number(document.getElementById('discount').value||0));saleTotal=Math.max(0,saleTotal-discount);
 const customer=selectedCustomer(),previousOs=customer?Math.max(0,customer.balance):0;
 const deposit=t==='security_deposit'?Math.max(0,Number(document.getElementById('securityDeposit').value||0)):0;
 document.getElementById('saleTotal').textContent=saleTotal.toFixed(2);
 document.getElementById('previousOs').textContent=previousOs.toFixed(2);
 document.getElementById('netPayable').textContent=(saleTotal+previousOs+deposit).toFixed(2);
 const paid=t==='cylinder_return'?0:paymentTotal();
 const balanceAfter=Math.max(0,saleTotal+previousOs+deposit-paid);
 document.getElementById('depositHelp').textContent=t==='security_deposit'?'Enter the refundable amount collected for the selected custody cylinders.':'Security deposit is entered through the Security Deposit transaction.';
 document.getElementById('typeHint').title='Balance after entered payment: Rs. '+balanceAfter.toFixed(2);
 const after=Number(gasStock||0)-gasRequired;
 const hint=document.getElementById('typeHint');
 if(t==='gas_sale'||t==='cylinder_sale'){hint.classList.toggle('text-danger',after<0);hint.textContent='Gas stock after transaction: '+after.toFixed(2)+' KG';}
 updateRefund();
}
function refreshForm(){
 const t=transactionType(),standard=['gas_sale','cylinder_sale'].includes(t);
 document.getElementById('standardTransaction').style.display=standard?'block':'none';
 document.getElementById('securityTransaction').style.display=t==='security_deposit'?'block':'none';
 document.getElementById('returnTransaction').style.display=t==='cylinder_return'?'block':'none';
 document.getElementById('securityDeposit').disabled=t!=='security_deposit'; document.getElementById('discount').disabled=!standard; if(!standard)document.getElementById('discount').value='0';
 if(t!=='security_deposit')document.getElementById('securityDeposit').value='0';
 document.getElementById('depositHelp').textContent=t==='security_deposit'?'Enter the refundable amount collected for the selected custody cylinders.':'Security deposit is entered through the Security Deposit transaction.';
 document.getElementById('refundBox').style.display=t==='cylinder_return'?'block':'none';
 document.getElementById('paymentSection').style.display=t==='cylinder_return'?'none':'block';
 document.getElementById('saveBtn').textContent=t==='cylinder_return'?'Return Cylinder / Refund Deposit':t==='security_deposit'?'Receive Deposit / Issue Cylinder':'Post Transaction';
 if(standard)rebuildLines();else{clearLines();lineHead.innerHTML='';}
 setCustodyLists();refreshPaymentModes();recalc();
}
document.getElementById('transactionType').onchange=refreshForm;
document.getElementById('customer_id').onchange=()=>{refreshCustomer();setCustodyLists();tbody.querySelectorAll('.targetCyl').forEach(s=>{const typeId=s.closest('tr').querySelector('.cyl').value;s.disabled=!selectedCustomerId();s.innerHTML=refillLineOptions(typeId);});refreshPaymentModes();};
document.getElementById('addLine').onclick=()=>{if(transactionType()==='gas_sale')addGasLine();else if(transactionType()==='cylinder_sale')addCylinderSaleLine();};
document.getElementById('discount').oninput=recalc;document.getElementById('securityDeposit').oninput=recalc;document.getElementById('returnUnits').onchange=updateRefund;document.getElementById('addPayment').onclick=addPayment;
document.getElementById('saleForm').onsubmit=()=>{
 const t=transactionType(),customerId=selectedCustomerId();
 let lines=[];
 if(t==='gas_sale'){
   lines=[...tbody.querySelectorAll('tr')].map(tr=>({cylinder_type_id:tr.querySelector('.cyl').value,quantity:tr.querySelector('.qty').value,gas_rate:tr.querySelector('.gasRate').value,customer_cylinder_unit_id:tr.querySelector('.targetCyl').value}));
   if(!lines.length){alert('Add at least one gas line.');return false;}
   for(const [i,l] of lines.entries()){const q=Number(l.quantity||0);if(!l.cylinder_type_id||q<=0){alert('Gas line '+(i+1)+' is invalid.');return false;}}
 }else if(t==='cylinder_sale'){
   lines=[...tbody.querySelectorAll('tr')].map(tr=>({cylinder_type_id:tr.querySelector('.cyl').value,cylinder_status:tr.querySelector('.cylStatus').value,quantity:tr.querySelector('.qty').value,gas_rate:tr.querySelector('.gasRate').value,cylinder_rate:tr.querySelector('.cylRate').value}));
   if(!lines.length){alert('Add at least one cylinder sale line.');return false;}
   for(const [i,l] of lines.entries()){const q=Number(l.quantity||0);if(!l.cylinder_type_id||q<=0||q!==Math.floor(q)){alert('Cylinder sale line '+(i+1)+' requires a whole-number quantity.');return false;}}
 }else if(t==='security_deposit'){
   const units=[...document.getElementById('custodyUnits').selectedOptions].map(o=>Number(o.value));if(!customerId){alert('Select a customer for Security Deposit.');return false;}if(!units.length){alert('Select at least one cylinder to issue on custody.');return false;}if(Number(document.getElementById('securityDeposit').value||0)<=0){alert('Enter a Security Deposit Amount.');return false;}
 }else if(t==='cylinder_return'){
   const units=[...document.getElementById('returnUnits').selectedOptions].map(o=>Number(o.value));if(!customerId){alert('Select a customer for Cylinder Return.');return false;}if(!units.length){alert('Select at least one customer custody cylinder to return.');return false;}
 }
 const pays=t==='cylinder_return'?[]:[...payments.querySelectorAll('.payment')].map(p=>({payment_mode:p.querySelector('.mode').value,amount:p.querySelector('.amount').value,reference_no:p.querySelector('.ref').value}));
 const saleTotal=Number(document.getElementById('saleTotal').textContent||0),previousOs=customerId?Number(document.getElementById('previousOs').textContent||0):0,deposit=t==='security_deposit'?Number(document.getElementById('securityDeposit').value||0):0;
 const expected=t==='security_deposit'?deposit:saleTotal+previousOs;
 if(t!=='cylinder_return'&&!pays.length){alert('Add at least one payment.');return false;}
 if(t!=='cylinder_return'&&paymentTotal()>expected+0.01){alert('Payment cannot exceed the Net Amount Receivable of Rs. '+expected.toFixed(2)+'.');return false;}
 if(!customerId&&pays.some(p=>p.payment_mode!=='cash')){alert('Walk-in transactions are cash only.');return false;}
 let gasRequired=0; if(t==='gas_sale')gasRequired=lines.reduce((s,l)=>s+Number(l.quantity||0),0); else if(t==='cylinder_sale')tbody.querySelectorAll('tr').forEach(tr=>{gasRequired+=gasForCylinderSale(tr);});
 if((t==='gas_sale'||t==='cylinder_sale')&&gasRequired>Number(gasStock||0)+0.00001){
   if(!confirm('Available gas stock is '+Number(gasStock||0).toFixed(2)+' KG, but this transaction requires '+gasRequired.toFixed(2)+' KG. Continue?'))return false;
   document.getElementById('stock_override_confirmed').value='1';
 }
 document.getElementById('lines_json').value=JSON.stringify(lines);
 document.getElementById('payments_json').value=JSON.stringify(pays);
 return true;
};
addPayment();document.getElementById('transactionType').value=defaultTransactionType;refreshCustomer();refreshForm();
</script>
<?= $this->endSection() ?>