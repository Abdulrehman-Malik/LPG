<?php

namespace App\Controllers;

use App\Services\DatabaseBackupService;
use CodeIgniter\Controller;

class DatabaseBackup extends Controller
{
    private function guard()
    {
        return \App\Services\PermissionService::allows('BACKUP_MANAGE')
            ? null
            : $this->response->setStatusCode(403)->setBody('Forbidden');
    }

    public function index()
    {
        if ($r = $this->guard()) return $r;

        $service = new DatabaseBackupService();
        return view('database-backup/index', [
            'title' => 'Database Backup & Restore',
            'backups' => $service->listBackups(),
            'backupDirectory' => $service->directory(),
            'maxUploadSize' => config('Backup')->maxUploadSize,
            'emailRecipients' => config('Email')->recipients,
        ]);
    }

    public function create()
    {
        if ($r = $this->guard()) return $r;

        try {
            $result = (new DatabaseBackupService())->createBackup(
                $this->request->getPost('send_email') === '1'
            );

            $message = 'Database backup created successfully: ' . $result['name'];
            if ($result['email_sent']) {
                $message .= ' Email sent successfully.';
            }
            return redirect()->to('/database-backup')->with('success', $message);
        } catch (\Throwable $e) {
            log_message('error', 'Database backup failed: {error}', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function restore()
    {
        if ($r = $this->guard()) return $r;

        $file = $this->request->getFile('backup_file');
        if (!$file || !$file->isValid()) {
            return redirect()->back()->with('error', 'Please select a valid .sql backup file.');
        }

        $extension = strtolower($file->getClientExtension());
        if ($extension !== 'sql') {
            return redirect()->back()->with('error', 'Only .sql backup files are allowed.');
        }

        if ($file->getSize() > config('Backup')->maxUploadSize) {
            return redirect()->back()->with('error', 'The selected backup file is larger than the configured upload limit.');
        }

        $temporary = WRITEPATH . 'backups' . DIRECTORY_SEPARATOR . 'restore_' . bin2hex(random_bytes(8)) . '.sql';
        if (!is_dir(dirname($temporary))) {
            mkdir(dirname($temporary), 0775, true);
        }

        try {
            if (!$file->move(dirname($temporary), basename($temporary), true)) {
                throw new \RuntimeException('The backup file could not be uploaded.');
            }

            // Always create a local safety backup immediately before restore.
            (new DatabaseBackupService())->createBackup(false);
            $result = (new DatabaseBackupService())->restore($temporary);

            return redirect()->to('/database-backup')->with(
                'success',
                'Database restored successfully from ' . $result['name'] . '. A safety backup was created before the restore.'
            );
        } catch (\Throwable $e) {
            log_message('error', 'Database restore failed: {error}', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', $e->getMessage());
        } finally {
            @unlink($temporary);
        }
    }
}
