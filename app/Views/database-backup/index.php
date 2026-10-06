<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-1">Database Backup & Restore</h4>
        <div class="text-muted small">Create a local MySQL backup and restore a previously created .sql backup.</div>
    </div>
</div>

<?php if (session()->getFlashdata('success')): ?>
<div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
<div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-database-down me-1"></i>Create Database Backup</div>
            <div class="card-body">
                <p class="text-muted">The complete database is exported to the configured local backup directory.</p>
                <div class="alert alert-light border">
                    <div class="small fw-semibold">Backup directory</div>
                    <code><?= esc($backupDirectory) ?></code>
                </div>
                <form method="post" action="<?= site_url('database-backup/create') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="send_email" id="sendEmailValue" value="0">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="sendEmailCheckbox">
                        <label class="form-check-label" for="sendEmailCheckbox">Also send backup as an email attachment</label>
                    </div>
                    <div class="small text-muted mb-3">
                        Configured recipients: <?= esc($emailRecipients ?: 'Not configured') ?>
                    </div>
                    <button type="submit" class="btn btn-primary" onclick="document.getElementById('sendEmailValue').value=document.getElementById('sendEmailCheckbox').checked?'1':'0';">
                        <i class="bi bi-cloud-arrow-down me-1"></i>Create Backup
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-database-up me-1"></i>Restore Database</div>
            <div class="card-body">
                <div class="alert alert-danger">
                    <strong>Warning:</strong> Restore replaces database objects/data contained in the selected SQL file.
                    A safety backup is automatically created immediately before the restore.
                </div>
                <form method="post" action="<?= site_url('database-backup/restore') ?>" enctype="multipart/form-data" onsubmit="return confirm('Restore this database backup? Current database data may be replaced. A safety backup will be created first.');">
                    <?= csrf_field() ?>
                    <label class="form-label">Backup file (.sql)</label>
                    <input type="file" name="backup_file" class="form-control mb-3" accept=".sql" required>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="restoreConfirm" required>
                        <label class="form-check-label" for="restoreConfirm">I understand that restoring will replace current database data.</label>
                    </div>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>Restore Database
                    </button>
                </form>
                <div class="form-text mt-2">Maximum upload size: <?= esc(number_format($maxUploadSize / 1048576, 0)) ?> MB, subject to PHP upload limits.</div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mt-3">
    <div class="card-header bg-white fw-semibold">Local Backup Files</div>
    <div class="card-body p-0">
        <?php if (!$backups): ?>
            <div class="p-3 text-muted">No local SQL backups found.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead><tr><th>Backup File</th><th>Size</th><th>Created</th></tr></thead>
                    <tbody>
                    <?php foreach ($backups as $backup): ?>
                        <tr>
                            <td><code><?= esc($backup['name']) ?></code></td>
                            <td><?= esc(number_format($backup['size'] / 1048576, 2)) ?> MB</td>
                            <td><?= esc(date('Y-m-d H:i:s', $backup['modified'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>
