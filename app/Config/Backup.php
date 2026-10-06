<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Backup extends BaseConfig
{
    public string $directory = '';
    public string $mysqldumpPath = '';
    public string $mysqlPath = '';
    public int $maxUploadSize = 536870912; // 512 MB

    public function __construct()
    {
        parent::__construct();

        $configuredDirectory = trim((string) env('backup.directory', ''));
        if ($configuredDirectory === '') {
            $this->directory = WRITEPATH . 'backups';
        } elseif ($this->isAbsolutePath($configuredDirectory)) {
            $this->directory = rtrim($configuredDirectory, "\\\\/");
        } else {
            $this->directory = rtrim(ROOTPATH . str_replace(['/', '\\\\'], DIRECTORY_SEPARATOR, ltrim($configuredDirectory, '/\\\\')), "\\\\/");
        }

        $this->mysqldumpPath = trim((string) env('backup.mysqldumpPath', ''));
        $this->mysqlPath = trim((string) env('backup.mysqlPath', ''));

        if (DIRECTORY_SEPARATOR === '\\' && defined('ROOTPATH')) {
            $xamppRoot = dirname(dirname(rtrim(ROOTPATH, "\\\\/")));
            if ($this->mysqldumpPath === '') {
                $candidate = $xamppRoot . DIRECTORY_SEPARATOR . 'mysql' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'mysqldump.exe';
                if (is_file($candidate)) {
                    $this->mysqldumpPath = $candidate;
                }
            }
            if ($this->mysqlPath === '') {
                $candidate = $xamppRoot . DIRECTORY_SEPARATOR . 'mysql' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'mysql.exe';
                if (is_file($candidate)) {
                    $this->mysqlPath = $candidate;
                }
            }
        }
    }

    private function isAbsolutePath(string $path): bool
    {
        return preg_match('/^(?:[A-Za-z]:[\\\\\/]|[\\\\\\/])/', $path) === 1;
    }
}
