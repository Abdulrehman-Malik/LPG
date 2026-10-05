<?php
namespace App\Services;
class MigrationRunnerService {
    protected $db;
    public function __construct(){ $this->db = \Config\Database::connect(); }
}
