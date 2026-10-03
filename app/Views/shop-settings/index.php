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
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#appearance" type="button">Appearance</button></li><li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#pos" type="button">POS & Sales</button></li>
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

            <div class="tab-pane fade" id="appearance"><div class="row g-3"><div class="col-md-4"><label class="form-label">Theme</label><select name="theme_mode" class="form-select"><option value="light" <?= ($settings['theme_mode'] ?? 'light') === 'light' ? 'selected' : '' ?>>Light</option><option value="dark" <?= ($settings['theme_mode'] ?? 'light') === 'dark' ? 'selected' : '' ?>>Dark</option></select></div><div class="col-md-4"><label class="form-label">Application Font Size (px)</label><input name="pos_font_size_px" type="number" class="form-control" min="10" max="24" step="0.5" value="<?= esc($settings['pos_font_size_px'] ?? 14) ?>"></div><div class="col-md-4"><label class="form-label">Font Style</label><select name="font_family" class="form-select"><?php foreach(['system'=>'System Default','arial'=>'Arial','verdana'=>'Verdana','tahoma'=>'Tahoma','trebuchet'=>'Trebuchet MS','georgia'=>'Georgia','times'=>'Times New Roman'] as $value=>$label): ?><option value="<?= esc($value) ?>" <?= ($settings['font_family'] ?? 'system') === $value ? 'selected' : '' ?>><?= esc($label) ?></option><?php endforeach; ?></select></div><div class="col-md-4"><label class="form-label">Primary Color</label><input name="primary_color" type="color" class="form-control form-control-color" value="<?= esc($settings['primary_color'] ?? '#1b2a3a') ?>"></div><div class="col-md-4"><label class="form-label">Accent Color</label><input name="accent_color" type="color" class="form-control form-control-color" value="<?= esc($settings['accent_color'] ?? '#ff7a1a') ?>"></div><div class="col-12"><div class="alert alert-light border mb-0">These appearance settings apply to the whole application for this branch. Font size is also used by POS; there is no separate POS-only font setting.</div></div></div></div>
            <div class="tab-pane fade" id="pos">
                <div class="row g-3">
                    <div class="col-md-7">
                        <label class="form-label">Default POS Transaction Type</label>
                        <select name="default_transaction_type" class="form-select">
                            <?php foreach(AppModelsShopSettingsModel::TRANSACTION_TYPE_LABELS as $value=>$label): ?>
                                <option value="<?= esc($value) ?>" <?= ($settings['default_transaction_type'] ?? 'gas_sale') === $value ? 'selected' : '' ?>><?= esc($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">This value is selected automatically when the POS opens. The cashier can still change the transaction type for the current invoice.</div>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Individual Cylinder Tracking</label>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" name="individual_cylinder_tracking" id="individualCylinderTracking" <?= !empty($settings['individual_cylinder_tracking']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="individualCylinderTracking">Track individual physical cylinders in normal POS sales</label>
                        </div>
                        <div class="form-text">OFF: POS shows aggregate stock by cylinder type and the backend selects physical cylinders automatically. ON: POS can show/select an individual filled cylinder when required.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Credit Limit Validation</label>
                        <select name="credit_limit_validation_mode" id="creditLimitMode" class="form-select">
                            <option value="none" <?= ($settings['credit_limit_validation_mode'] ?? 'none') === 'none' ? 'selected' : '' ?>>None — no credit limit validation</option>
                            <option value="customer" <?= ($settings['credit_limit_validation_mode'] ?? 'none') === 'customer' ? 'selected' : '' ?>>Customer Level — use each customer's Credit Limit</option>
                            <option value="shop" <?= ($settings['credit_limit_validation_mode'] ?? 'none') === 'shop' ? 'selected' : '' ?>>Shop Level — use one branch-wide Credit Limit</option>
                        </select>
                        <div class="form-text">A limit of Rs. 0.00 means the customer must fully settle the current sale and any previous OS.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Shop Credit Limit</label>
                        <input name="shop_credit_limit" id="shopCreditLimit" type="number" min="0" step="any" class="form-control" value="<?= esc($settings['shop_credit_limit'] ?? 0) ?>">
                        <div class="form-text">Used only when Credit Limit Validation is set to Shop Level.</div>
                    </div>
                    <div class="col-12"><div class="alert alert-light border mb-0">Partial payment is allowed. Any unpaid amount becomes Customer OS and is carried into the next sale. Credit-limit validation applies to the resulting outstanding balance.</div></div>
                    <div class="col-12"><div class="alert alert-info border mb-0"><strong>POS transaction types:</strong> Gas Sale / Refill, Cylinder Sale, Security Deposit / Issue Cylinder, and Cylinder Return / Refund Deposit. The selected default is used when opening a new POS invoice.</div></div>
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