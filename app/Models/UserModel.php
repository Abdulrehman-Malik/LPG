<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
    protected $allowedFields    = [
        'full_name', 'username', 'email', 'password_hash',
        'role', 'is_active', 'last_login_at',
    ];

    /**
     * Look a user up by username OR email (whichever the login form was given).
     */
    public function findByLogin(string $login): ?array
    {
        return $this->groupStart()
                ->where('username', $login)
                ->orWhere('email', $login)
            ->groupEnd()
            ->first();
    }

    public function touchLastLogin(int $userId): void
    {
        $this->update($userId, ['last_login_at' => date('Y-m-d H:i:s')]);
    }

    /**
     * Helper for seeding/creating users with a hashed password.
     */
    public function createWithPassword(array $data): int|false
    {
        if (isset($data['password'])) {
            $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
            unset($data['password']);
        }

        return $this->insert($data);
    }
}
