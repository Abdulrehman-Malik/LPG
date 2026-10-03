<?php

namespace App\Services;

use Config\Database;

class PermissionService
{
    protected static ?array $permissionCache = null;

    public static function all(): array
    {
        if (self::$permissionCache !== null) {
            return self::$permissionCache;
        }

        $userId=(int)(session()->get('user_id') ?? 0);
        if($userId<=0) {
            return self::$permissionCache=[];
        }

        self::$permissionCache = array_map(
            'strval',
            array_column(
                Database::connect()->table('users u')
                    ->select('permissions.code')
                    ->join('role_permissions rp','rp.role_id=u.role_id')
                    ->join('permissions','permissions.id=rp.permission_id')
                    ->where('u.id',$userId)
                    ->get()
                    ->getResultArray(),
                'code'
            )
        );

        return self::$permissionCache;
    }

    public static function allows(string $permission): bool
    {
        return in_array($permission, self::all(), true);
    }
}
