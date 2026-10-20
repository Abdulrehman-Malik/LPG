<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3"><h4>LPG Rates & History</h4><button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#form">New Rate</button></div>
<div class="card"><div class="card-body table-responsive"><table class="table table-sm datatable"><thead><tr><th>Effective</th><th>Type</th><th>Empty Cylinder Type</th><th>Rate</th><th>Created By</th><th data-orderable="false" class="text-end">Actions</th></tr></thead><tbody><?php foreach($rates as $r): ?><tr><td><?= esc($r['effective_from']) ?></td><td><?= $r['rate_type']==='gas_per_kg'?'Retail Gas / KG':($r['rate_type']==='wholesale_cylinder'?'Wholesale Full Cylinder':'Empty Cylinder Price') ?></td><?php $rateCylinder=$rateCylinderTypes[(string)($r['cylinder_type_id']??'')]??null; $displayCylinder=trim(($r['cylinder_code']??'').' '.($r['cylinder_name']??'')); if($displayCylinder==='') $displayCylinder=trim(($rateCylinder['code']??'').' '.($rateCylinder['name']??'')); if($displayCylinder==='') $displayCylinder='Empty Cylinder Type #'.($r['cylinder_type_id']??''); ?><td><?= $r['rate_type']==='gas_per_kg' ? 'All Cylinders' : esc($displayCylinder) ?></td><td><?= number_format((float)$r['rate_value'],2) ?></td><td><?= esc($r['created_by_name']??'') ?></td><?php
$rowTypeLabel=$r['rate_type']==='gas_per_kg'?'Retail Gas / KG':($r['rate_type']==='wholesale_cylinder'?'Wholesale Full Cylinder':'Empty Cylinder Price');
$rowCylinder=$r['rate_type']==='gas_per_kg'?'All Cylinders':$displayCylinder;
?><td class="text-end text-nowrap">
<button type="button" class="btn btn-sm btn-outline-primary rate-edit" data-bs-toggle="modal" data-bs-target="#editRate" data-id="<?= (int)$r['id'] ?>" data-type="<?= esc($rowTypeLabel,'attr') ?>" data-cylinder="<?= esc($rowCylinder,'attr') ?>" data-rate="<?= esc(number_format((float)$r['rate_value'],2,'.',''),'attr') ?>" data-effective="<?= esc(str_replace(' ','T',substr((string)$r['effective_from'],0,16)),'attr') ?>"><i class="bi bi-pencil-square me-1"></i>Edit</button>
<form method="post" action="<?= site_url('rates/delete/'.(int)$r['id']) ?>" class="d-inline" onsubmit="return confirm('Delete this rate? Sales already posted keep the price they were sold at. If this is the current rate, the previous rate becomes current.');"><?= csrf_field() ?><button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash me-1"></i>Delete</button></form>
</td></tr><?php endforeach; ?></tbody></table></div></div>
<div class="modal fade" id="form"><div class="modal-dialog"><form method="post" action="<?= site_url('rates/save') ?>" class="modal-content"><?= csrf_field() ?><div class="modal-header"><h5>New Rate</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="mb-3"><label class="form-label">Rate Type</label><select name="rate_type" id="rateType" class="form-select" required><option value="gas_per_kg">Retail — Gas per KG</option><option value="wholesale_cylinder">Wholesale — Price per Full Cylinder</option><option value="cylinder_package">Cylinder Price</option></select></div><div class="mb-3" id="typeWrap" style="display:none"><label class="form-label" id="typeLabel">Empty Cylinder Type</label><select name="cylinder_type_id" class="form-select"><option value="">Select</option><?php foreach($types as $t): ?><option value="<?= $t['id'] ?>"><?= esc($t['code'].' — '.$t['name']) ?></option><?php endforeach; ?></select></div><div class="mb-3"><label class="form-label">Rate / Price</label><input name="rate_value" type="number" step="0.01" min="0" class="form-control" required></div><div class="mb-3"><label class="form-label">Effective From</label><input name="effective_from" type="datetime-local" class="form-control" value="<?= date('Y-m-d\TH:i') ?>" required></div><div class="mb-3"><label class="form-label">Reason</label><input name="reason" class="form-control"></div></div><div class="modal-footer"><button class="btn btn-primary">Save Rate</button></div></form></div></div>
<div class="modal fade" id="editRate" tabindex="-1"><div class="modal-dialog"><form method="post" id="editRateForm" action="" class="modal-content"><?= csrf_field() ?>
<div class="modal-header"><h5>Edit Rate</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
<div class="row g-2 mb-3"><div class="col-6"><div class="small text-muted">Rate Type</div><div class="fw-semibold" id="editRateType"></div></div><div class="col-6"><div class="small text-muted">Cylinder Type</div><div class="fw-semibold" id="editRateCylinder"></div></div></div>
<div class="mb-3"><label class="form-label">Rate / Price</label><input name="rate_value" id="editRateValue" type="number" step="0.01" min="0" class="form-control" required></div>
<div class="mb-3"><label class="form-label">Effective From</label><input name="effective_from" id="editRateEffective" type="datetime-local" class="form-control" required></div>
<div class="mb-3"><label class="form-label">Reason</label><input name="reason" class="form-control"></div>
<div class="alert alert-light border small mb-0">Posted sales keep the price they were sold at. To change the rate type or cylinder type, delete this rate and add a new one.</div>
</div>
<div class="modal-footer"><button class="btn btn-primary">Update Rate</button></div></form></div></div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const editModal = document.getElementById('editRate');
    if (editModal) editModal.addEventListener('show.bs.modal', (event) => {
        const b = event.relatedTarget; if (!b) return;
        document.getElementById('editRateForm').action = '<?= site_url('rates/update') ?>/' + b.dataset.id;
        document.getElementById('editRateType').textContent = b.dataset.type;
        document.getElementById('editRateCylinder').textContent = b.dataset.cylinder;
        document.getElementById('editRateValue').value = b.dataset.rate;
        document.getElementById('editRateEffective').value = b.dataset.effective;
    });
});
</script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const rateType = document.getElementById('rateType');
    const typeWrap = document.getElementById('typeWrap');
    if (!rateType || !typeWrap) return;

    const syncCylinderVisibility = () => {
        typeWrap.style.display = rateType.value === 'gas_per_kg' ? 'none' : 'block';
        document.getElementById('typeLabel').textContent = rateType.value === 'wholesale_cylinder' ? 'Cylinder Type' : 'Empty Cylinder Type';
    };

    rateType.addEventListener('change', syncCylinderVisibility);
    syncCylinderVisibility();
});
</script>
<?= $this->endSection() ?>