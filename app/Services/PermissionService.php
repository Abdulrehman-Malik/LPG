<?php

namespace App\Services;

use Config\Database;

class PermissionService
{
    protected static ?array $permissionCache = null;
    protected static ?int $permissionCacheUserId = null;

    public static function all(): array
    {
        $userId=(int)(session()->get('user_id') ?? 0);

        if (self::$permissionCache !== null && self::$permissionCacheUserId === $userId) {
            return self::$permissionCache;
        }

        self::$permissionCacheUserId = $userId;
        if($userId<=0) {
            return self::$permissionCache=[];
        }

        self::$permissionCache = array_values(array_unique(array_map(
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
        )));

        return self::$permissionCache;
    }

    public static function current(): array
    {
        return array_fill_keys(self::all(), true);
    }

    public static function allows(string $permission): bool
    {
        return isset(self::current()[$permission]);
    }
}
