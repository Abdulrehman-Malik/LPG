<?php

namespace App\Services;

use Config\Backup;
use Config\Database;

class DatabaseBackupService
{
    private Backup $config;
    private array $db;

    public function __construct()
    {
        $this->config = config('Backup');
        $this->db = (array) config('Database')->default;
    }

    public function directory(): string
    {
        return $this->config->directory;
    }

    public function listBackups(): array
    {
        $dir = $this->directory();
        if (!is_dir($dir)) {
            return [];
        }

        $files = [];
        foreach (glob($dir . DIRECTORY_SEPARATOR . '*.sql') ?: [] as $path) {
            if (!is_file($path)) {
                continue;
            }
            $files[] = [
                'name' => basename($path),
                'path' => $path,
                'size' => filesize($path) ?: 0,
                'modified' => filemtime($path) ?: 0,
            ];
        }

        usort($files, static fn(array $a, array $b): int => $b['modified'] <=> $a['modified']);
        return $files;
    }

    public function createBackup(bool $email = false, ?string $emailRecipient = null): array
    {
        $this->ensureDirectory();

        if ($this->config->mysqldumpPath === '' || !is_file($this->config->mysqldumpPath)) {
            throw new \RuntimeException('mysqldump was not found. Configure backup.mysqldumpPath in .env or install MySQL/MariaDB client tools.');
        }

        $database = trim((string) ($this->db['database'] ?? ''));
        if ($database === '') {
            throw new \RuntimeException('Database name is not configured.');
        }

        $filename = preg_replace('/[^A-Za-z0-9._-]+/', '_', $database)
            . '_' . date('Ymd_His') . '.sql';
        $path = $this->directory() . DIRECTORY_SEPARATOR . $filename;

        $exitCode = $this->runMysqlUtility($this->config->mysqldumpPath, $path, false);
        if ($exitCode !== 0 || !is_file($path) || (filesize($path) ?: 0) < 10) {
            @unlink($path);
            throw new \RuntimeException('Database backup failed. Check the MySQL client configuration and writable backup directory.');
        }

        $emailSent = false;
        if ($email) {
            $emailSent = $this->emailBackup($path, $emailRecipient);
        }

        return ['path' => $path, 'name' => $filename, 'email_sent' => $emailSent];
    }

    public function restore(string $uploadedPath): array
    {
        if ($this->config->mysqlPath === '' || !is_file($this->config->mysqlPath)) {
            throw new \RuntimeException('mysql client was not found. Configure backup.mysqlPath in .env or install MySQL/MariaDB client tools.');
        }
        if (!is_file($uploadedPath)) {
            throw new \RuntimeException('The selected backup file could not be found.');
        }

        $extension = strtolower(pathinfo($uploadedPath, PATHINFO_EXTENSION));
        if ($extension !== 'sql') {
            throw new \RuntimeException('Only .sql database backup files can be restored.');
        }

        $exitCode = $this->runMysqlUtility($this->config->mysqlPath, $uploadedPath, true);
        if ($exitCode !== 0) {
            throw new \RuntimeException('Database restore failed. The database may be partially restored; review the MySQL error log before retrying.');
        }

        return ['name' => basename($uploadedPath)];
    }

    private function ensureDirectory(): void
    {
        $dir = $this->directory();
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new \RuntimeException('Backup directory could not be created: ' . $dir);
        }
        if (!is_writable($dir)) {
            throw new \RuntimeException('Backup directory is not writable: ' . $dir);
        }
    }

    private function runMysqlUtility(string $binary, string $file, bool $restore): int
    {
        $host = (string) ($this->db['hostname'] ?? 'localhost');
        $user = (string) ($this->db['username'] ?? '');
        $password = (string) ($this->db['password'] ?? '');
        $port = (int) ($this->db['port'] ?? 3306);
        $database = (string) ($this->db['database'] ?? '');

        $optionFile = tempnam(sys_get_temp_dir(), 'lpg-db-');
        if ($optionFile === false) {
            throw new \RuntimeException('Unable to create a temporary MySQL credential file.');
        }

        $ini = "[client]\n"
            . 'host=' . $host . "\n"
            . 'port=' . $port . "\n"
            . 'user=' . $user . "\n"
            . 'password=' . $password . "\n";
        file_put_contents($optionFile, $ini);
        @chmod($optionFile, 0600);

        try {
            $command = escapeshellarg($binary)
                . ' --defaults-extra-file=' . escapeshellarg($optionFile)
                . ' ' . escapeshellarg($database);

            if ($restore) {
                $command .= ' < ' . escapeshellarg($file);
            } else {
                $command .= ' --single-transaction --routines --triggers --events --hex-blob > ' . escapeshellarg($file);
            }

            $descriptor = [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ];
            $process = proc_open($command, $descriptor, $pipes);
            if (!is_resource($process)) {
                throw new \RuntimeException('Unable to start the MySQL utility.');
            }

            fclose($pipes[0]);
            stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $exitCode = proc_close($process);

            if ($exitCode !== 0 && trim($stderr) !== '') {
                log_message('error', 'Database backup/restore MySQL error: {error}', ['error' => trim($stderr)]);
            }

            return $exitCode;
        } finally {
            @unlink($optionFile);
        }
    }

    private function emailBackup(string $path, ?string $emailRecipient = null): bool
    {
        $emailConfig = config('Email');
        $recipients = trim((string) ($emailRecipient ?? $emailConfig->recipients));
        if ($recipients === '') {
            throw new \RuntimeException('Email backup was requested, but no recipient email address is configured.');
        }
        if (trim((string) $emailConfig->fromEmail) === '') {
            throw new \RuntimeException('Email backup was requested, but email.fromEmail is not configured.');
        }

        $email = service('email');
        $email->setFrom($emailConfig->fromEmail, $emailConfig->fromName ?: 'Perfect LPG');
        $email->setTo($recipients);
        $email->setSubject('Perfect LPG Database Backup - ' . date('Y-m-d H:i:s'));
        $email->setMessage(
            'Attached is the database backup created by Perfect LPG on ' . date('Y-m-d H:i:s') . '.'
        );
        $email->attach($path);

        if (!$email->send()) {
            log_message('error', 'Database backup email failed: {debug}', ['debug' => $email->printDebugger(['headers', 'subject'])]);
            throw new \RuntimeException('Backup was created, but the email could not be sent. Check SMTP/email configuration.');
        }

        return true;
    }
}
