<?php

namespace App\Services;

use Config\Database;

class PermissionService
{
    public static function allows(string $permission): bool
    {
        $userId=(int)(session()->get('user_id') ?? 0);
        if($userId<=0) return false;
        $db=Database::connect();
        return $db->table('users u')
            ->select('permissions.id')
            ->join('role_permissions rp','rp.role_id=u.role_id')
            ->join('permissions','permissions.id=rp.permission_id')
            ->where('u.id',$userId)
            ->where('permissions.code',$permission)
            ->countAllResults()>0;
    }
}