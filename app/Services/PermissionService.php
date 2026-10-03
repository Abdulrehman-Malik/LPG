<?php

namespace App\Services;

use Config\Database;

class PermissionService
{
    /**
     * Return all permission codes granted to the currently authenticated user.
     * The result is cached for the lifetime of the request so the sidebar can
     * render every authorized menu item without issuing one query per item.
     */
    public static function current(): array
    {
        static $permissions = null;

        if ($permissions !== null) {
            return $permissions;
        }

        $userId=(int)(session()->get('user_id') ?? 0);
        if($userId<=0) return $permissions=[];

        $rows=Database::connect()->table('users u')
            ->select('permissions.code')
            ->join('role_permissions rp','rp.role_id=u.role_id')
            ->join('permissions','permissions.id=rp.permission_id')
            ->where('u.id',$userId)
            ->get()->getResultArray();

        $permissions=[];
        foreach($rows as $row){
            $code=trim((string)($row['code']??''));
            if($code!=='') $permissions[$code]=true;
        }

        return $permissions;
    }

    public static function allows(string $permission): bool
    {
        return isset(self::current()[$permission]);
    }
}
