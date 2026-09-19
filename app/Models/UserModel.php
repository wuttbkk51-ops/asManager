<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'tenant_id',
        'username',
        'email',
        'password',
        'person_id',
        'role',
        'status',
        'created_at',
        'updated_at',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * ดึงข้อมูลผู้ใช้พร้อมข้อมูลร้านค้า (Tenant) และสิทธิ์จาก user_permissions โดยตรง
     */
    public function getUserWithRoleAndPermissions(string $email): ?array
    {
        $user = $this->where('email', $email)->first();
        if (!$user) {
            return null;
        }

        $db = \Config\Database::connect();

        // 1. ดึงข้อมูลร้านค้า (Tenant) ถ้ามี (Superadmin จะไม่มี tenant_id)
        $user['tenant'] = null;
        if (!empty($user['tenant_id'])) {
            $user['tenant'] = $db->table('tenants')->where('id', $user['tenant_id'])->get()->getRowArray();
        }

        // 2. ดึงสิทธิ์จาก user_permissions โดยตรง 100%
        $userPerms = $db->table('user_permissions')
            ->select('permission')
            ->where('user_id', $user['id'])
            ->get()
            ->getResultArray();
        $user['permissions'] = array_column($userPerms, 'permission');

        // 3. ดึงข้อมูลบุคคล (Peoples) ถ้ามี
        $user['person'] = null;
        if (!empty($user['person_id'])) {
            $user['person'] = $db->table('peoples')->where('id', $user['person_id'])->get()->getRowArray();
        }

        return $user;
    }

    /**
     * สมัครสมาชิกใหม่ (SaaS Onboarding Flow):
     * กฎ:
     * - สร้าง user -> role = 'owner'
     * - สร้าง tenant ใหม่ให้ทันที ผูก tenant_id กับ user
     * - tenant_plan default = 'freePlan'
     * - ให้สิทธิ์ '*' สำหรับ Owner ใน user_permissions
     */
    public function registerUser(string $email, string $plainPassword, ?string $username = null): int|false
    {
        $db = \Config\Database::connect();

        // 1. กำหนด username
        if (empty($username)) {
            $prefix = explode('@', $email)[0];
            $baseUsername = preg_replace('/[^a-zA-Z0-9_]/', '', $prefix) ?: 'user';
            $username = $baseUsername;

            $count = 1;
            while ($this->where('username', $username)->first()) {
                $username = $baseUsername . '_' . mt_rand(100, 999);
                $count++;
                if ($count > 10) break;
            }
        }

        // 2. สร้าง Tenant ใหม่สำหรับ Owner ท่านนี้ทันที
        $tenantModel = new TenantModel();
        $baseSlug = preg_replace('/[^a-zA-Z0-9-]/', '', str_replace('_', '-', strtolower($username))) ?: 'shop';
        $slug = $baseSlug;

        $slugCount = 1;
        while ($tenantModel->where('slug', $slug)->first()) {
            $slug = $baseSlug . '-' . mt_rand(100, 999);
            $slugCount++;
            if ($slugCount > 10) break;
        }

        $now = date('Y-m-d H:i:s');
        $tenantId = $tenantModel->insert([
            'name'       => 'ร้านค้าของ ' . $username,
            'slug'       => $slug,
            'plan'       => 'freePlan', // default plan ตามโจทย์
            'status'     => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        if (!$tenantId) {
            return false;
        }

        // 3. สร้าง User ที่ผูกกับ Tenant นี้ และมี role = 'owner'
        $userData = [
            'tenant_id' => $tenantId, // ผูก tenant_id ทันที
            'username'  => strtolower($username),
            'email'     => strtolower(trim($email)),
            'password'  => password_hash($plainPassword, PASSWORD_BCRYPT),
            'person_id' => null,     // ค่อยกรอกข้อมูลบุคคลภายหลัง
            'role'      => 'owner',   // role = owner ตามโจทย์
            'status'    => 'active',  // dev: active ทันที
        ];

        $userId = $this->insert($userData);

        if ($userId) {
            // 4. มอบสิทธิ์เต็ม (*) ให้ Owner ในร้านของตนเอง
            $db->table('user_permissions')->insert([
                'user_id'    => $userId,
                'permission' => '*',
                'created_at' => $now,
            ]);

            // 5. คัดลอก (Clone) Role Templates ส่วนกลาง (tenant_id = 0) ไปเป็น Role ประจำร้านนี้ทันที
            $roleModel = new RoleModel();
            $roleModel->cloneTemplatesToTenant((int)$tenantId);
        }

        return $userId;
    }
}
