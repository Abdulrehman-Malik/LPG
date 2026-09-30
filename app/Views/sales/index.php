<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3"><div><?php if($cashSession): ?><div class="small text-success fw-semibold">Cash session: OPEN — <?=esc($cashSession["register_code"])?></div><?php else: ?><div class="small text-warning fw-semibold">Cash session: NOT OPEN — open Counter Cash before cash sales</div><?php endif; ?></div><span class="badge text-bg-secondary">Phase 3</span></div>
<?php if(session()->getFlashdata('success')): ?><div class="alert alert-success"><?= session()->getFlashdata('success') ?></div><?php endif; ?>
<?php if(session()->getFlashdata('error')): ?><div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div><?php endif; ?>
<form method="post" action="<?= site_url('sales/save') ?>" id="saleForm"><?= csrf_field() ?>
<input type="hidden" name="lines_json" id="lines_json"><input type="hidden" name="payments_json" id="payments_json"><input type="hidden" name="stock_override_confirmed" id="stock_override_confirmed" value="0">
<div class="row g-3">
<div class="col-lg-8"><div class="card"><div class="card-body">
<div class="row g-2 mb-3">
<div class="col-md-6"><label class="form-label">Customer</label><select name="customer_id" id="customer_id" class="form-select"><option value="">Walk-in / Cash</option><?php foreach($customers as $c): ?><option value="<?= $c['id'] ?>"><?= esc(($c['code']?$c['code'].' — ':'').$c['name']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-6"><label class="form-label">Transaction Time</label><input name="transaction_at" type="datetime-local" class="form-control" value="<?= date('Y-m-d\TH:i') ?>"></div>
</div>
<div id="customerInfo" class="alert alert-light border py-2 small">Walk-in: cash sales are allowed; cylinder returns require a named customer.</div>

<div class="row g-2 align-items-end mb-3">
<div class="col-md-8"><label class="form-label">Default Transaction for New Lines</label><select id="transactionType" class="form-select">
<option value="sell_gas_only">1 — Sell Gas Only</option>
<option value="replace_same">2 — Sell Gas by Replacing Same-Capacity Cylinder</option>
<option value="sell_filled">3 — Sell Filled Cylinder with Gas + Cylinder Price</option>
<option value="replace_different">4 — Sell Filled Cylinder with Gas + Replace Different-Capacity Cylinder</option>
<option value="sell_empty">5 — Sell Empty Cylinder Only</option>
</select></div>
<div class="col-md-4"><div class="form-check mb-2"><input class="form-check-input" type="checkbox" id="saveDefault"><label class="form-check-label" for="saveDefault">Save as my default</label></div><button type="button" class="btn btn-sm btn-outline-secondary" id="clearDefault">Clear default</button></div>
</div>
<div id="transactionHint" class="alert alert-info border py-2 small mb-2"></div>

<div class="table-responsive"><table class="table table-sm align-middle" id="lines"><thead><tr><th>Transaction</th><th>Cylinder Type</th><th>Qty / KG</th><th>Rates</th><th>Total</th><th></th></tr></thead><tbody></tbody></table></div>
<button type="button" class="btn btn-outline-primary" id="addLine">Add Line</button>
</div></div></div>

<div class="col-lg-4"><div class="card"><div class="card-body">
<div class="row g-2 mb-3">
<div class="col-6"><label class="form-label">Total Gas Stock</label><div class="form-control bg-light fw-semibold"><span id="gasStockCurrent"><?= number_format((float)$gasStock, 3) ?></span> KG</div><div class="small text-muted mt-1">After This Sale: <strong id="gasStockAfter"><?= number_format((float)$gasStock, 3) ?> KG</strong></div></div>
<div class="col-6"><label class="form-label">Discount</label><input name="discount_amount" id="discount" type="number" min="0" step="0.01" value="0" class="form-control"></div>
</div>
<div class="mb-3"><strong>Total: Rs. <span id="total">0.00</span></strong></div>
<div id="payments"></div><button type="button" class="btn btn-outline-secondary mb-3" id="addPayment">Add Payment</button>
<textarea name="notes" class="form-control mb-3" placeholder="Notes"></textarea><button class="btn btn-primary w-100" id="saveBtn">Post Sale</button>
</div></div></div>
</div></form>
<script>
const types=<?= json_encode(array_values($types),JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>;
const rates=<?= json_encode($cylinderRates) ?>, kgRate=<?= json_encode($kgRate) ?>, filledStock=<?= json_encode($filledStock) ?>, filledUnits=<?= json_encode($filledUnits) ?>, emptyStock=<?= json_encode($emptyStock) ?>, gasStock=<?= json_encode($gasStock) ?>;
const balances=<?= json_encode($balances) ?>, creditLimits=<?= json_encode($creditLimits) ?>, inventoryPolicy=<?= json_encode($inventoryPolicy) ?>;
const userDefaultKey='lpg_pos_default_txn_<?= (int)session()->get('user_id') ?>';
const modes={
 sell_gas_only:{label:'Sell Gas Only',hint:'Customer brings their own cylinder. Gas stock is reduced only. Select the filled cylinder type used as the gas source; when a source cylinder is fully consumed, that physical cylinder becomes empty automatically.'},
 replace_same:{label:'Sell Gas by Replacing Same-Capacity Cylinder',hint:'Customer returns an empty cylinder and receives a filled cylinder of the same type. Gas stock and filled-cylinder stock decrease; an empty cylinder of the same type is received.'},
 sell_filled:{label:'Sell Filled Cylinder with Gas + Cylinder Price',hint:'Customer buys a filled cylinder. Gas is charged by actual gas weight and the cylinder price is charged separately. Gas stock and filled-cylinder stock decrease.'},
 replace_different:{label:'Sell Filled Cylinder with Gas + Replace Different-Capacity Cylinder',hint:'Customer returns an empty cylinder of a different type and receives the selected filled cylinder. Gas, sold filled stock and received empty stock are updated.'},
 sell_empty:{label:'Sell Empty Cylinder Only',hint:'Only an empty physical cylinder is sold. Gas stock is not affected.'}
};
const tbody=document.querySelector('#lines tbody'), payments=document.getElementById('payments'), transactionType=document.getElementById('transactionType'), saveDefault=document.getElementById('saveDefault');
function selectedCustomer(){const id=document.getElementById('customer_id').value;return id?{balance:Number(balances[id]||0),limit:Number(creditLimits[id]||0)}:null;}
function refreshCustomer(){const c=selectedCustomer(),box=document.getElementById('customerInfo');if(!c){box.textContent='Walk-in: cash sales are allowed. Cylinder returns require a named customer.';return;}const available=Math.max(0,c.limit-c.balance);box.textContent='Previous OS: Rs. '+c.balance.toFixed(2)+' | Credit Limit: Rs. '+c.limit.toFixed(2)+' | Available Credit: Rs. '+available.toFixed(2);}
function isGasMode(mode){return ['sell_gas_only','replace_same','sell_filled','replace_different'].includes(mode);}
function isFilledMode(mode){return ['replace_same','sell_filled','replace_different'].includes(mode);}
function cylinderOptions(mode, empty=false){
 const source=empty?emptyStock:filledStock, unitsMap=empty?null:filledUnits;
 return '<option value="">Select</option>'+types.map(t=>{
   const count=Number((source||{})[t.id]||0);
   let suffix=' — Available: '+count+(empty?' empty':' filled');
   if(!empty){const units=(unitsMap[t.id]||[]);const gas=units.reduce((s,u)=>s+Number(u.gas_weight_kg||0),0);suffix+=' / '+gas.toFixed(3)+' kg gas';}
   return '<option value="'+t.id+'">'+t.code+' — '+t.name+suffix+'</option>';
 }).join('');
}
function gasForPreview(tr,usedByType){
 const mode=tr.querySelector('.kind').value,qty=Math.max(0,Number(tr.querySelector('.qty').value||0)),typeId=tr.querySelector('.cyl').value;
 if(mode==='sell_gas_only') return qty;
 if(!isFilledMode(mode)||!typeId||qty<=0) return 0;
 const units=filledUnits[typeId]||[],start=Number(usedByType[typeId]||0),count=Math.floor(qty);
 let gas=0;for(let i=0;i<count;i++){const unit=units[start+i];if(unit)gas+=Number(unit.gas_weight_kg||0);}
 usedByType[typeId]=start+count;
 return gas;
}
function refreshLineFields(tr){
 const mode=tr.querySelector('.kind').value,cyl=tr.querySelector('.cyl'),received=tr.querySelector('.receivedCyl');
 const current=cyl.value;const currentReceived=received.value;cyl.innerHTML=cylinderOptions(mode,mode==='sell_empty');if([...cyl.options].some(o=>o.value===current))cyl.value=current;
 const showReceived=mode==='replace_different';received.style.display=showReceived?'block':'none';received.required=showReceived;
 if(showReceived){received.innerHTML=cylinderOptions(mode,true);if([...received.options].some(o=>o.value===currentReceived))received.value=currentReceived;}
 tr.querySelector('.receivedWrap').style.display=showReceived?'block':'none';
 const gasRate=tr.querySelector('.gasRate'),cylRate=tr.querySelector('.cylRate');
 const typeId=cyl.value;
 gasRate.value=isGasMode(mode)&&kgRate!==null?Number(kgRate).toFixed(2):'';
 cylRate.value=(['sell_filled','replace_different','sell_empty'].includes(mode)&&typeId&&rates[typeId]!=null)?Number(rates[typeId]).toFixed(2):'';
 gasRate.style.display=isGasMode(mode)?'block':'none';
 cylRate.style.display=['sell_filled','replace_different','sell_empty'].includes(mode)?'block':'none';
 transactionType.value=transactionType.value||'sell_gas_only';
}
function addLine(mode=transactionType.value||'sell_gas_only'){
 const tr=document.createElement('tr');
 tr.innerHTML='<td><select class="form-select kind"><option value="sell_gas_only">1 — Sell Gas Only</option><option value="replace_same">2 — Sell Gas by Replacing Same-Capacity Cylinder</option><option value="sell_filled">3 — Sell Filled Cylinder with Gas + Cylinder Price</option><option value="replace_different">4 — Sell Filled Cylinder with Gas + Replace Different-Capacity Cylinder</option><option value="sell_empty">5 — Sell Empty Cylinder Only</option></select></td><td><select class="form-select cyl"></select><div class="receivedWrap mt-1" style="display:none"><small class="text-muted">Received Empty Type</small><select class="form-select receivedCyl"></select></div></td><td><input class="form-control qty" type="number" min="0.001" step="0.001" value="1"></td><td><div class="gasRateWrap small" style="display:none"><label class="form-label mb-0">Gas / KG</label><input class="form-control form-control-sm gasRate" type="number" min="0" step="0.01"></div><div class="cylRateWrap small mt-1" style="display:none"><label class="form-label mb-0">Cylinder Price</label><input class="form-control form-control-sm cylRate" type="number" min="0" step="0.01"></div></td><td class="lineGas small text-muted">Gas: 0.000 KG<br><strong class="lineTotal">Rs. 0.00</strong></td><td><button type="button" class="btn btn-sm btn-outline-danger remove">×</button></td>';
 tbody.appendChild(tr);tr.querySelector('.kind').value=mode;
 const kind=tr.querySelector('.kind');kind.onchange=()=>{refreshLineFields(tr);recalc();};tr.querySelector('.cyl').onchange=()=>{refreshLineFields(tr);recalc();};tr.querySelector('.receivedCyl').onchange=()=>recalc();tr.querySelector('.qty').oninput=()=>recalc();tr.querySelector('.gasRate').oninput=()=>recalc();tr.querySelector('.cylRate').oninput=()=>recalc();tr.querySelector('.remove').onclick=()=>{tr.remove();recalc();};refreshLineFields(tr);
}
function recalc(){
 let total=0,gasDeduction=0;const usedByType={};
 tbody.querySelectorAll('tr').forEach(tr=>{
   const mode=tr.querySelector('.kind').value,qty=Math.max(0,Number(tr.querySelector('.qty').value||0));
   const gas=gasForPreview(tr,usedByType),gasRate=Number(tr.querySelector('.gasRate').value||0),cylRate=Number(tr.querySelector('.cylRate').value||0);
   let lineTotal=0;
   if(mode==='sell_gas_only'||mode==='replace_same') lineTotal=gas*gasRate;
   else if(mode==='sell_filled'||mode==='replace_different') lineTotal=gas*gasRate+Math.floor(qty)*cylRate;
   else if(mode==='sell_empty') lineTotal=Math.floor(qty)*cylRate;
   gasDeduction+=gas;total+=lineTotal;tr.dataset.previewGas=gas.toFixed(3);tr.querySelector('.lineGas').innerHTML='Gas: '+gas.toFixed(3)+' KG<br><strong class="lineTotal">Rs. '+lineTotal.toFixed(2)+'</strong>';
 });
 total-=Number(document.getElementById('discount').value||0);document.getElementById('total').textContent=Math.max(0,total).toFixed(2);
 const after=Number(gasStock||0)-gasDeduction,afterEl=document.getElementById('gasStockAfter');afterEl.textContent=after.toFixed(3)+' KG';afterEl.classList.toggle('text-danger',after<0);afterEl.classList.toggle('text-success',after>=0);
}
function setModeHint(){document.getElementById('transactionHint').innerHTML='<strong>'+modes[transactionType.value].label+':</strong> '+modes[transactionType.value].hint;}
function applyDefault(){const saved=localStorage.getItem(userDefaultKey);if(saved&&modes[saved]){transactionType.value=saved;saveDefault.checked=true;}setModeHint();if(!tbody.children.length)addLine(transactionType.value);}
transactionType.onchange=()=>{setModeHint();if(saveDefault.checked)localStorage.setItem(userDefaultKey,transactionType.value);};
saveDefault.onchange=()=>{if(saveDefault.checked)localStorage.setItem(userDefaultKey,transactionType.value);else localStorage.removeItem(userDefaultKey);};
document.getElementById('clearDefault').onclick=()=>{localStorage.removeItem(userDefaultKey);saveDefault.checked=false;};
function addPayment(){const div=document.createElement('div');div.className='input-group mb-2 payment';div.innerHTML='<select class="form-select mode"><option value="cash">Cash</option><option value="cheque">Cheque</option><option value="online">Online</option><option value="credit">Credit</option></select><input class="form-control amount" type="number" min="0.01" step="0.01" placeholder="Amount"><input class="form-control ref" placeholder="Ref"><button type="button" class="btn btn-outline-danger remove">×</button>';payments.appendChild(div);div.querySelector('.mode').onchange=syncPaymentModes;div.querySelector('.remove').onclick=()=>div.remove();syncPaymentModes();}
function syncPaymentModes(){const walkIn=!document.getElementById('customer_id').value;payments.querySelectorAll('.payment').forEach(p=>{const mode=p.querySelector('.mode');[...mode.options].forEach(o=>o.disabled=walkIn&&o.value!=='cash');if(walkIn)mode.value='cash';});}
function paymentTotal(){return [...payments.querySelectorAll('.payment')].reduce((s,p)=>s+Number(p.querySelector('.amount').value||0),0);}
document.getElementById('customer_id').onchange=()=>{refreshCustomer();syncPaymentModes();};document.getElementById('addLine').onclick=()=>addLine();document.getElementById('discount').oninput=recalc;
document.getElementById('saleForm').onsubmit=()=>{
 const lines=[...tbody.querySelectorAll('tr')].map(tr=>({sale_mode:tr.querySelector('.kind').value,cylinder_type_id:tr.querySelector('.cyl').value,received_cylinder_type_id:tr.querySelector('.receivedCyl').value,quantity:tr.querySelector('.qty').value,gas_weight_kg:Number(tr.dataset.previewGas||0),gas_rate:tr.querySelector('.gasRate').value,cylinder_rate:tr.querySelector('.cylRate').value}));
 let valid=true;
 lines.forEach((l,i)=>{const n=i+1,mode=l.sale_mode,q=Number(l.quantity||0),typeId=l.cylinder_type_id;const filledCount=Number((filledStock||{})[typeId]||0),emptyCount=Number((emptyStock||{})[typeId]||0);if(!modes[mode]||q<=0){alert('Line '+n+' is invalid.');valid=false;return;}if(mode==='sell_gas_only'){const sourceGas=(filledUnits[typeId]||[]).reduce((s,u)=>s+Number(u.gas_weight_kg||0),0);if(!typeId||q>sourceGas+0.00001){alert('Line '+n+': requested gas exceeds the available gas in the selected source cylinder type.');valid=false;}}else if(isFilledMode(mode)){if(!typeId||q!==Math.floor(q)||q>filledCount){alert('Line '+n+': filled-cylinder quantity is invalid or exceeds available stock.');valid=false;}if(mode==='replace_different'&&(!l.received_cylinder_type_id||String(l.received_cylinder_type_id)===String(typeId))){alert('Line '+n+': select a different empty-cylinder type to receive.');valid=false;}if(['replace_same','replace_different'].includes(mode)&&!document.getElementById('customer_id').value){alert('Line '+n+': a named customer is required when an empty cylinder is returned.');valid=false;}}else if(mode==='sell_empty'&&(!typeId||q!==Math.floor(q)||q>emptyCount)){alert('Line '+n+': empty-cylinder quantity is invalid or exceeds available stock.');valid=false;}});
 if(!valid)return false;
 const pays=[...payments.querySelectorAll('.payment')].map(p=>({payment_mode:p.querySelector('.mode').value,amount:p.querySelector('.amount').value,reference_no:p.querySelector('.ref').value}));
 const total=Number(document.getElementById('total').textContent||0),paid=paymentTotal(),walkIn=!document.getElementById('customer_id').value;
 if(!lines.length||!pays.length){alert('Add at least one line and one payment.');return false;}
 if(!Number.isFinite(total)||total<0){alert('Sale total is invalid.');return false;}
 if(!Number.isFinite(paid)||Math.abs(paid-total)>0.01){alert('Payment total must equal sale total.');return false;}
 if(walkIn&&pays.some(p=>p.payment_mode!=='cash')){alert('Walk-in sales are cash only.');return false;}
 const gasRequired=lines.reduce((s,l)=>s+(isGasMode(l.sale_mode)?Number(l.gas_weight_kg||0):0),0);
 if(gasRequired>Number(gasStock||0)+0.00001&&!Number(inventoryPolicy.stock_validation_enabled)){if(!confirm('Available gas stock is '+Number(gasStock||0).toFixed(3)+' kg, but this sale requires '+gasRequired.toFixed(3)+' kg. Stock validation is OFF. Post with inventory override?'))return false;document.getElementById('stock_override_confirmed').value='1';}
 document.getElementById('lines_json').value=JSON.stringify(lines);document.getElementById('payments_json').value=JSON.stringify(pays);return true;
};
applyDefault();addPayment();refreshCustomer();recalc();
</script>
<?= $this->endSection() ?>