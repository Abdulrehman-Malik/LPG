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
        <form method="post" action="<?=site_url('inventory/adjust')?>" class="row g-3">
          <?=csrf_field()?>
          <div class="col-md-3">
            <label class="form-label fw-semibold">Stock Item</label>
            <select name="inventory_type" id="adjustType" class="form-select">
              <option value="gas_kg">Gas KG</option>
              <option value="filled_cylinder">Filled Cylinder</option>
              <option value="empty_cylinder">Empty Cylinder</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label fw-semibold">Cylinder Type</label>
            <select name="cylinder_type_id" id="adjustCylinderType" class="form-select">
              <option value="">N/A</option>
              <?php foreach($types as $t):?><option value="<?=$t['id']?>"><?=esc($t['code'].' — '.$t['name'].' ('.$t['capacity_kg'].' KG)')?></option><?php endforeach;?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label fw-semibold">Adjustment</label>
            <div class="d-flex gap-2">
              <label class="form-check border rounded px-3 py-2 flex-fill"><input class="form-check-input me-1" type="radio" name="direction" value="in" checked> + Increase</label>
              <label class="form-check border rounded px-3 py-2 flex-fill"><input class="form-check-input me-1" type="radio" name="direction" value="out"> − Decrease</label>
            </div>
          </div>
          <div class="col-md-3">
            <label class="form-label fw-semibold">Quantity</label>
            <input name="quantity" class="form-control" type="number" min=".001" step=".001" placeholder="Enter quantity" required>
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
            <div class="alert alert-light border mb-3" id="adjustHelp">For filled-cylinder increase, enter the actual gas KG contained in each new physical cylinder. Decreasing filled cylinders also removes their remaining gas from stock.</div>
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
const adjustType=document.getElementById('adjustType'),adjustCylinderType=document.getElementById('adjustCylinderType'),adjustActualGas=document.getElementById('adjustActualGas'),adjustHelp=document.getElementById('adjustHelp');
function refreshAdjustmentFields(){
  const t=adjustType.value, increase=document.querySelector('input[name="direction"]:checked')?.value==='in';
  const cylinder=t!=='gas_kg';
  adjustCylinderType.disabled=!cylinder;adjustCylinderType.required=cylinder;if(!cylinder)adjustCylinderType.value='';
  const filled=t==='filled_cylinder';
  adjustActualGas.disabled=!(filled&&increase);adjustActualGas.required=filled&&increase;
  adjustHelp.textContent=filled&&increase?'For filled-cylinder increase, enter the actual gas KG contained in each physical cylinder. It cannot exceed that cylinder type capacity.':filled?'For filled-cylinder decrease, selected physical cylinders and their remaining gas will be removed from stock.':'Gas KG adjustments do not create a physical cylinder unit.';
}
adjustType.onchange=refreshAdjustmentFields;document.querySelectorAll('input[name="direction"]').forEach(e=>e.onchange=refreshAdjustmentFields);refreshAdjustmentFields();
</script>
<?= $this->endSection() ?>
