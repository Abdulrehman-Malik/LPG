<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Opening Inventory</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#form" onclick="prepareAdd()">
        Add Opening
    </button>
</div>

<div class="card">
    <div class="card-body table-responsive">
        <table class="table table-sm align-middle">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Cylinder</th>
                    <th>Quantity</th>
                    <th>Gas Stock (KG)</th>
                    <th>Comments</th>
                    <th class="text-nowrap">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= esc($r['inventory_date']) ?></td>
                    <td><?= esc(ucwords(str_replace('_', ' ', $r['inventory_type']))) ?></td>
                    <td><?= esc(trim(($r['cylinder_code'] ?? '') . ' ' . ($r['cylinder_name'] ?? '')) ?: '—') ?></td>
                    <td><?= number_format((float) $r['quantity'], 0) ?></td>
                    <td><?= number_format((float) ($r['gas_stock'] ?? 0), 3) ?></td>
                    <td><?= esc($r['comments'] ?? '') ?></td>
                    <td class="text-nowrap">
                        <button
                            type="button"
                            class="btn btn-sm btn-outline-primary"
                            data-bs-toggle="modal"
                            data-bs-target="#form"
                            onclick='prepareEdit(<?= json_encode([
                                "id" => (int) $r["id"],
                                "date" => $r["inventory_date"],
                                "type" => $r["inventory_type"],
                                "cylinderTypeId" => $r["cylinder_type_id"],
                                "quantity" => (float) $r["quantity"],
                                "comments" => $r["comments"] ?? "",
                            ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>)'>
                            Edit
                        </button>
                        <form method="post" action="<?= site_url('inventory/opening/delete/' . $r['id']) ?>" class="d-inline" onsubmit="return confirm('Delete this opening inventory record? This action will be recorded in the Audit Log.');">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="form" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" action="<?= site_url('inventory/opening/save') ?>" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="opening_id" id="openingId">

            <div class="modal-header">
                <h5 id="formTitle">Add Opening Inventory</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Inventory Date</label>
                    <input name="inventory_date" id="inventoryDate" type="date" value="<?= date('Y-m-d') ?>" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Inventory Type</label>
                    <select name="inventory_type" id="invType" class="form-select" required>
                        <option value="filled_cylinder">Filled Cylinder</option>
                        <option value="empty_cylinder">Empty Cylinder</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Cylinder Type</label>
                    <select name="cylinder_type_id" id="cylinderTypeId" class="form-select" required>
                        <option value="">Select</option>
                        <?php foreach ($types as $t): ?>
                            <option value="<?= $t['id'] ?>"><?= esc($t['code'] . ' — ' . $t['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Quantity</label>
                    <input name="quantity" id="quantity" type="number" min="0" step="1" class="form-control" required>
                </div>

                <div class="mb-3" id="gasWeightWrap">
                    <label class="form-label">Actual Gas per Filled Cylinder (KG)</label>
                    <input name="actual_gas_weight_kg" id="actualGasWeight" type="number" min="0" step="0.001" class="form-control">
                    <div class="form-text">Leave blank to use the selected cylinder's full capacity.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Comments</label>
                    <textarea name="comments" id="comments" maxlength="500" rows="3" class="form-control" placeholder="Reason, opening stock note, adjustment reference, etc."></textarea>
                </div>
            </div>

            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Save Opening</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleOpening() {
    const filled = document.getElementById('invType').value === 'filled_cylinder';
    document.getElementById('gasWeightWrap').style.display = filled ? 'block' : 'none';
    document.getElementById('actualGasWeight').disabled = !filled;
}

function prepareAdd() {
    document.getElementById('formTitle').textContent = 'Add Opening Inventory';
    document.getElementById('openingId').value = '';
    document.getElementById('inventoryDate').value = '<?= date('Y-m-d') ?>';
    document.getElementById('invType').value = 'filled_cylinder';
    document.getElementById('cylinderTypeId').value = '';
    document.getElementById('quantity').value = '';
    document.getElementById('actualGasWeight').value = '';
    document.getElementById('comments').value = '';
    document.getElementById('invType').disabled = false;
    document.getElementById('cylinderTypeId').disabled = false;
    toggleOpening();
}

function prepareEdit(row) {
    document.getElementById('formTitle').textContent = 'Edit Opening Inventory';
    document.getElementById('openingId').value = row.id;
    document.getElementById('inventoryDate').value = row.date;
    document.getElementById('invType').value = row.type;
    document.getElementById('cylinderTypeId').value = row.cylinderTypeId;
    document.getElementById('quantity').value = row.quantity;
    document.getElementById('actualGasWeight').value = row.type === 'filled_cylinder' && row.quantity > 0 ? '' : '';
    document.getElementById('comments').value = row.comments || '';
    document.getElementById('invType').disabled = true;
    document.getElementById('cylinderTypeId').disabled = true;
    toggleOpening();
}
document.getElementById('invType')?.addEventListener('change', toggleOpening);
toggleOpening();
</script>
<?= $this->endSection() ?>
