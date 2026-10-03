<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <div><h4 class="mb-1">Stock Adjustment & History</h4><div class="text-muted small">Increase (+) or decrease (-) stock manually, then review stock movements, sales and purchases on the same screen.</div></div>
  <a class="btn btn-outline-secondary" href="<?=site_url('inventory')?>">Inventory</a>
</div>
<?php if(session()->getFlashdata('success')):?><div class="alert alert-success"><?=esc(session()->getFlashdata('success'))?></div><?php endif;?>
<?php if(session()->getFlashdata('error')):?><div class="alert alert-danger"><?=esc(session()->getFlashdata('error'))?></div><?php endif;?>

<div class="row g-3 mb-3">
<?php foreach($rows as $r):?>
  <div class="col-md-4 col-xl-2"><div class="card h-100"><div class="card-body py-2"><div class="small text-muted"><?=esc($r['name'])?></div><div class="fs-5 fw-bold"><?=number_format((float)$r['stock'],3)?></div></div></div></div>
<?php endforeach;?>
</div>

<div class="card shadow-sm">
  <div class="card-header bg-white">
    <ul class="nav nav-tabs card-header-tabs" role="tablist">
      <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#adjust" type="button">Stock Adjustment</button></li>
      <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#history" type="button">View History</button></li>
    </ul>
  </div>
  <div class="card-body">
    <div class="tab-content">
      <div class="tab-pane fade show active" id="adjust">
        <form method="post" action="<?=site_url('inventory/adjust')?>" class="row g-3" id="adjustmentForm">
          <?=csrf_field()?>
          <div class="col-12">
            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label fw-semibold">Adjustment Scope</label>
                <select name="adjustment_scope" id="adjustScope" class="form-select">
                  <option value="bulk">Bulk — Cylinder Type</option>
                  <option value="specific">Specific — Physical Cylinder</option>
                </select>
                <div class="form-text">Bulk changes the selected cylinder type. Specific changes only the selected physical cylinder unit.</div>
              </div>
              <div class="col-md-4">
                <label class="form-label fw-semibold">Stock Item</label>
                <select name="inventory_type" id="adjustType" class="form-select">
                  <option value="gas_kg">Gas KG</option>
                  <option value="filled_cylinder">Filled Cylinder</option>
                  <option value="empty_cylinder">Empty Cylinder</option>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label fw-semibold">Adjustment</label>
                <div class="d-flex gap-2">
                  <label class="form-check border rounded px-3 py-2 flex-fill"><input class="form-check-input me-1" type="radio" name="direction" value="in" checked> + Increase</label>
                  <label class="form-check border rounded px-3 py-2 flex-fill"><input class="form-check-input me-1" type="radio" name="direction" value="out"> − Decrease</label>
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-5">
            <label class="form-label fw-semibold">Cylinder Type</label>
            <select name="cylinder_type_id" id="adjustCylinderType" class="form-select">
              <option value="">N/A</option>
              <?php foreach($types as $t):?><option value="<?=$t['id']?>"><?=esc($t['code'].' — '.$t['name'].' ('.$t['capacity_kg'].' KG)')?></option><?php endforeach;?>
            </select>
          </div>
          <div class="col-md-7" id="sourceCylinderWrap">
            <label class="form-label fw-semibold">Source Physical Cylinder</label>
            <select name="source_cylinder_unit_id" id="sourceCylinder" class="form-select">
              <option value="">Select a physical cylinder</option>
              <?php foreach($units as $u):?>
                <option value="<?=$u['id']?>" data-type="<?=$u['cylinder_type_id']?>" data-status="<?=$u['status']?>" data-gas="<?=$u['gas_weight_kg']?>"><?=esc($u['unit_code'].' — '.$u['cylinder_code'].' — '.$u['cylinder_name'].' — '.ucfirst($u['status']).' — '.$u['gas_weight_kg'].' KG')?></option>
              <?php endforeach;?>
            </select>
            <div class="form-text" id="sourceHelp">Required only for specific physical-cylinder adjustments. The server locks this exact unit before changing it.</div>
          </div>
          <div class="col-md-3">
            <label class="form-label fw-semibold">Quantity / KG</label>
            <input name="quantity" id="adjustQuantity" class="form-control" type="number" min=".001" step=".001" placeholder="Enter quantity" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Actual Gas KG per Filled Cylinder</label>
            <input name="actual_gas_weight_kg" id="adjustActualGas" class="form-control" type="number" min=".001" step=".001" placeholder="Required for filled-cylinder +">
          </div>
          <div class="col-md-8">
            <label class="form-label">Reason / Notes</label>
            <input name="notes" class="form-control" placeholder="Why is this adjustment required?">
          </div>
          <div class="col-12">
            <div class="alert alert-light border mb-3" id="adjustHelp">Bulk mode adjusts the selected cylinder type. Specific mode changes only the selected physical cylinder and records the unit code in history.</div>
            <button class="btn btn-primary"><i class="bi bi-sliders me-1"></i>Post Stock Adjustment</button>
          </div>
        </form>
      </div>

      <div class="tab-pane fade" id="history">
        <div class="table-responsive">
          <table class="table table-sm table-striped align-middle datatable">
            <thead><tr><th>Date/Time</th><th>Source</th><th>Item</th><th>Cylinder</th><th>Direction</th><th>Qty</th><th>Notes</th><th>User</th></tr></thead>
            <tbody>
            <?php foreach($history as $h):
              $sourceType=(string)$h['source_type'];
              $source=$sourceType==='adjustment'?'Stock Adjustment':($sourceType==='purchase'?'Purchase '.($h['purchase_no']??''):($sourceType==='sale'?'Sale '.($h['sale_no']??''):ucwords(str_replace('_',' ',$sourceType)).(!empty($h['sale_no'])?' '.$h['sale_no']:'')));
              $item=$h['inventory_type']==='gas_kg'?'Gas KG':($h['inventory_type']==='filled_cylinder'?'Filled Cylinder':'Empty Cylinder');
            ?>
              <tr>
                <td><?=esc($h['movement_at'])?></td>
                <td><?=esc($source)?></td>
                <td><?=esc($item)?></td>
                <td><?=esc(($h['cylinder_code']??'').($h['cylinder_name']?' — '.$h['cylinder_name']:''))?></td>
                <td><span class="badge <?=$h['direction']==='out'?'text-bg-danger':'text-bg-success'?>"><?=esc(strtoupper($h['direction']))?></span></td>
                <td><?=number_format((float)$h['quantity'],3)?></td>
                <td><?=esc($h['notes']??'')?></td>
                <td><?=esc($h['full_name']??'')?></td>
              </tr>
            <?php endforeach;?>
            </tbody>
          </table>
        </div>
        <div class="small text-muted mt-2">Showing the latest 500 inventory movements for the current branch, including adjustments, sales, purchases, custody issue/return and reversals.</div>
      </div>
    </div>
  </div>
</div>
<script>
const adjustScope=document.getElementById('adjustScope');
const adjustType=document.getElementById('adjustType');
const adjustCylinderType=document.getElementById('adjustCylinderType');
const sourceCylinderWrap=document.getElementById('sourceCylinderWrap');
const sourceCylinder=document.getElementById('sourceCylinder');
const adjustQuantity=document.getElementById('adjustQuantity');
const adjustActualGas=document.getElementById('adjustActualGas');
const adjustHelp=document.getElementById('adjustHelp');

function refreshAdjustmentFields(){
  const scope=adjustScope.value;
  const type=adjustType.value;
  const increase=document.querySelector('input[name="direction"]:checked')?.value==='in';
  const cylinder=type!=='gas_kg';
  const filled=type==='filled_cylinder';

  adjustCylinderType.disabled=!cylinder;
  adjustCylinderType.required=cylinder;
  if(!cylinder) adjustCylinderType.value='';

  sourceCylinderWrap.style.display=scope==='specific'?'block':'none';
  sourceCylinder.disabled=scope!=='specific';
  sourceCylinder.required=scope==='specific';

  Array.from(sourceCylinder.options).forEach((opt)=>{
    if(!opt.value)return;
    const typeMatch=adjustCylinderType.value!=='' && opt.dataset.type===adjustCylinderType.value;
    const status=opt.dataset.status||'';
    let valid=typeMatch;
    if(type==='gas_kg') valid=typeMatch && (status==='filled'||status==='empty');
    else if(type==='filled_cylinder') valid=typeMatch && (increase ? status==='empty' : status==='filled');
    else if(type==='empty_cylinder') valid=typeMatch && status==='empty' && !increase;
    opt.hidden=!valid;
    if(!valid && opt.selected) sourceCylinder.value='';
  });

  if(scope==='specific'){
    adjustQuantity.max=type==='gas_kg' ? '' : '1';
    adjustQuantity.step=type==='gas_kg' ? '.001' : '1';
    adjustQuantity.placeholder=type==='gas_kg'?'Enter KG':'1';
  }else{
    adjustQuantity.removeAttribute('max');
    adjustQuantity.step=type==='gas_kg'?'.001':'1';
    adjustQuantity.placeholder=type==='gas_kg'?'Enter KG':'Enter cylinder quantity';
  }

  adjustActualGas.disabled=!(filled&&increase);
  adjustActualGas.required=filled&&increase;

  if(scope==='specific'){
    adjustHelp.textContent=type==='gas_kg'
      ? 'Specific mode changes gas only on the selected physical cylinder. Decreasing the last remaining gas moves that exact cylinder to empty; increasing gas on an empty cylinder fills that same unit.'
      : type==='filled_cylinder'
        ? (increase ? 'Select one empty physical cylinder and enter the actual gas KG added to that unit.' : 'Select one filled physical cylinder. Only that unit will leave filled-cylinder stock, and its remaining gas will be removed.')
        : 'Select one empty physical cylinder. Only that exact unit will leave empty-cylinder stock.';
  }else{
    adjustHelp.textContent=type==='gas_kg'
      ? 'Bulk mode adjusts the branch-level Gas KG balance and does not select a physical cylinder.'
      : filled&&increase
        ? 'Bulk mode creates the requested number of new filled physical cylinders of the selected type, each with the entered actual gas KG.'
        : filled
          ? 'Bulk mode removes the requested number of filled physical cylinders of the selected type and removes each unit\'s remaining gas.'
          : 'Bulk mode adds/removes physical cylinder units by the selected cylinder type.';
  }
}
adjustScope?.addEventListener('change',refreshAdjustmentFields);
adjustType?.addEventListener('change',refreshAdjustmentFields);
adjustCylinderType?.addEventListener('change',refreshAdjustmentFields);
document.querySelectorAll('input[name="direction"]').forEach(e=>e.addEventListener('change',refreshAdjustmentFields));
refreshAdjustmentFields();
</script>
<?= $this->endSection() ?>
