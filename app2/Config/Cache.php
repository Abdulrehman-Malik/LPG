<?php
namespace Config;
use CodeIgniter\Config\BaseConfig;
class Cache extends BaseConfig {
 public string $handler='file'; public string $backupHandler='dummy'; public string $prefix=''; public int $ttl=60;
 public string $reservedCharacters='{}()/\\@:'; public array $file=['storePath'=>WRITEPATH.'cache/','mode'=>0640];
 public $cacheQueryString=false; public array $cacheStatusCodes=[];
}