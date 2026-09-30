<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-1">Shop Settings</h4>
        <div class="text-muted small">Branch-level configuration for <?= esc($location['name'] ?? 'Current Branch') ?></div>
    </div>
    <span class="badge text-bg-secondary">Branch configuration</span>
</div>

<?php if(empty($settings['id'])): ?><div class="alert alert-warning"><strong>Shop Settings storage is not initialized.</strong> Run <code>database/migrations/20260930_shop_settings.sql</code> once on this existing database, then refresh this page.</div><?php endif; ?>
<form method="post" action="<?= site_url('shop-settings/save') ?>">
<?= csrf_field() ?>
<div class="card shadow-sm">
    <div class="card-header bg-white">
        <ul class="nav nav-tabs card-header-tabs" role="tablist">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#general" type="button">General</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#pos" type="button">POS & Sales</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#inventory" type="button">Inventory Control</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#cash" type="button">Cash & Payments</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#receipt" type="button">Receipt & Printing</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#backup" type="button">Backup & Maintenance</button></li>
        </ul>
    </div>

    <div class="card-body">
        <div class="tab-content">
            <div class="tab-pane fade show active" id="general">
                <div class="row g-3">
                    <div class="col-md-4"><label class="form-label">Branch Code</label><input class="form-control" value="<?= esc($location['code'] ?? '') ?>" readonly></div>
                    <div class="col-md-8"><label class="form-label">Shop / Branch Name</label><input name="shop_name" class="form-control" required value="<?= esc($location['name'] ?? '') ?>"></div>
                    <div class="col-md-8"><label class="form-label">Address</label><input name="shop_address" class="form-control" value="<?= esc($location['address'] ?? '') ?>"></div>
                    <div class="col-md-4"><label class="form-label">City</label><input name="shop_city" class="form-control" value="<?= esc($location['city'] ?? '') ?>"></div>
                    <div class="col-md-4"><label class="form-label">Phone</label><input name="shop_phone" class="form-control" value="<?= esc($location['phone'] ?? '') ?>"></div>
                    <div class="col-md-8"><label class="form-label">Configuration Notes</label><input name="settings_note" class="form-control" value="<?= esc($settings['settings_note'] ?? '') ?>" placeholder="Internal branch-level notes"></div>
                </div>
            </div>

            <div class="tab-pane fade" id="pos">
                <div class="row g-3">
                    <div class="col-md-7">
                        <label class="form-label">Default POS Transaction Type</label>
                        <select name="default_sale_mode" class="form-select">
                            <?php $modes = [
                                'sell_gas_only' => '1 — Sell Gas Only',
                                'replace_same' => '2 — Sell Gas by Replacing Same-Capacity Cylinder',
                                'sell_filled' => '3 — Sell Filled Cylinder with Gas + Cylinder Price',
                                'replace_different' => '4 — Sell Filled Cylinder with Gas + Replace Different-Capacity Cylinder',
                                'sell_empty' => '5 — Sell Empty Cylinder Only',
                            ]; foreach($modes as $value=>$label): ?>
                                <option value="<?= esc($value) ?>" <?= ($settings['default_sale_mode'] ?? '') === $value ? 'selected' : '' ?>><?= esc($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">This default is applied to the first POS line. Cashiers can still change the transaction type for a specific line.</div>
                    </div>
                    <div class="col-12"><div class="alert alert-light border mb-0">Default transaction is a branch setting. It is no longer configured on the POS screen.</div></div>
                </div>
            </div>

            <div class="tab-pane fade" id="inventory">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Stock Validation at Sale</label>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" name="stock_validation_enabled" id="stockValidation" <?= !empty($settings['stock_validation_enabled']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="stockValidation">Prevent sales above available gas stock</label>
                        </div>
                        <div class="form-text">The existing cylinder-type policy override remains available under Inventory Controls.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Allow Stock Override Confirmation</label>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" name="allow_stock_override" id="stockOverride" <?= !empty($settings['allow_stock_override']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="stockOverride">Allow authorized users to confirm an over-stock gas sale when validation is OFF</label>
                        </div>
                        <div class="form-text">The confirmation is recorded with the sale request. Keep this disabled for strict stock control.</div>
                    </div>
                    <div class="col-12"><div class="alert alert-info mb-0"><strong>Important:</strong> Cylinder-type overrides are managed separately in Inventory Controls so branch defaults and per-cylinder exceptions remain clear.</div></div>
                </div>
            </div>

            <div class="tab-pane fade" id="cash">
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label">Default Payment Mode</label><select name="default_payment_mode" class="form-select"><?php foreach(['cash'=>'Cash','cheque'=>'Cheque','online'=>'Online','credit'=>'Credit'] as $value=>$label): ?><option value="<?= esc($value) ?>" <?= ($settings['default_payment_mode'] ?? 'cash') === $value ? 'selected' : '' ?>><?= esc($label) ?></option><?php endforeach; ?></select><div class="form-text">New POS payment lines use this mode by default.</div></div>
                    <div class="col-md-6"><label class="form-label">Counter Cash</label><div class="form-control bg-light">Open cash session is required for cash sales and cash movements.</div></div>
                    <div class="col-12"><div class="alert alert-light border mb-0">Cash, cheque, online and credit remain supported. Non-cash payments do not increase Counter Cash.</div></div>
                </div>
            </div>

            <div class="tab-pane fade" id="receipt">
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label">Receipt Title</label><input name="receipt_title" class="form-control" value="<?= esc($settings['receipt_title'] ?? 'SALE RECEIPT') ?>"></div>
                    <div class="col-md-6"><label class="form-label">Show Shop Address</label><div class="form-check form-switch mt-2"><input class="form-check-input" type="checkbox" name="show_address_on_receipt" <?= !empty($settings['show_address_on_receipt']) ? 'checked' : '' ?>><label class="form-check-label">Print branch address on receipts</label></div></div>
                    <div class="col-12"><label class="form-label">Receipt Footer</label><textarea name="receipt_footer" class="form-control" rows="3"><?= esc($settings['receipt_footer'] ?? 'Thank you') ?></textarea></div>
                </div>
            </div>

            <div class="tab-pane fade" id="backup">
                <div class="alert alert-warning"><strong>Security:</strong> Store a backup endpoint or URL only. Do not put database usernames, passwords or API secrets into this field.</div>
                <div class="row g-3">
                    <div class="col-md-4"><label class="form-label">Backup Enabled</label><div class="form-check form-switch mt-2"><input class="form-check-input" type="checkbox" name="backup_enabled" <?= !empty($settings['backup_enabled']) ? 'checked' : '' ?>><label class="form-check-label">Enable backup integration setting</label></div></div>
                    <div class="col-md-8"><label class="form-label">Database Backup URL / Endpoint</label><input name="db_backup_url" type="url" class="form-control" value="<?= esc($settings['db_backup_url'] ?? '') ?>" placeholder="https://backup.example.com/..."></div>
                    <div class="col-12"><label class="form-label">Backup Notes</label><textarea name="backup_notes" class="form-control" rows="4" placeholder="Backup schedule, provider notes, retention policy, etc."><?= esc($settings['backup_notes'] ?? '') ?></textarea></div>
                    <div class="col-12"><div class="alert alert-light border mb-0">This setting stores the backup endpoint for future backup integration. The POS does not execute the URL automatically.</div></div>
                </div>
            </div>
        </div>
    </div>
    <div class="card-footer bg-white d-flex justify-content-end">
        <button class="btn btn-primary"><i class="bi bi-check2-circle me-1"></i>Save Shop Settings</button>
    </div>
</div>
</form>
<?= $this->endSection() ?>