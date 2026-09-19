<?php

namespace App\Models;

use CodeIgniter\Model;

class RoleModel extends Model
{
    protected $table            = 'roles';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'tenant_id',
        'name',
        'title',
        'description',
        'permissions',
        'created_at',
        'updated_at',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * ดึง Default Role Templates ส่วนกลาง (tenant_id = 0)
     */
    public function getDefaultTemplates(): array
    {
        $roles = $this->where('tenant_id', 0)->findAll();
        foreach ($roles as &$role) {
            $role['permissions'] = json_decode($role['permissions'], true) ?? [];
        }
        return $roles;
    }

    /**
     * ดึง Roles ทั้งหมดของร้านที่ระบุ (tenant_id = $tenantId)
     */
    public function getTenantRoles(int $tenantId): array
    {
        $roles = $this->where('tenant_id', $tenantId)->findAll();
        foreach ($roles as &$role) {
            $role['permissions'] = json_decode($role['permissions'], true) ?? [];
        }
        return $roles;
    }

    /**
     * คัดลอก (Clone) Role Templates ส่วนกลาง (tenant_id = 0) ไปเป็น Role ของร้านที่ระบุ
     * เรียกใช้งานตอนที่มีการสมัครสมาชิกหรือเปิด Shop/Tenant ใหม่
     */
    public function cloneTemplatesToTenant(int $tenantId): int
    {
        $templates = $this->where('tenant_id', 0)->findAll();
        if (empty($templates)) {
            return 0;
        }

        $now = date('Y-m-d H:i:s');
        $batch = [];

        foreach ($templates as $tpl) {
            $batch[] = [
                'tenant_id'   => $tenantId,
                'name'        => $tpl['name'],
                'title'       => $tpl['title'],
                'description' => $tpl['description'],
                'permissions' => is_string($tpl['permissions']) ? $tpl['permissions'] : json_encode($tpl['permissions']),
                'created_at'  => $now,
                'updated_at'  => $now,
            ];
        }

        return $this->insertBatch($batch) ? count($batch) : 0;
    }

    /**
     * เพิ่ม Custom Role ประจำร้าน
     */
    public function createRoleForTenant(int $tenantId, array $data): int|false
    {
        $permissions = isset($data['permissions']) && is_array($data['permissions'])
            ? json_encode(array_values($data['permissions']))
            : ($data['permissions'] ?? '[]');

        return $this->insert([
            'tenant_id'   => $tenantId,
            'name'        => $data['name'],
            'title'       => $data['title'],
            'description' => $data['description'] ?? null,
            'permissions' => $permissions,
        ]);
    }

    /**
     * อัปเดต Role ประจำร้าน (ป้องกันการแก้ไขข้ามร้าน)
     */
    public function updateTenantRole(int $tenantId, int $roleId, array $data): bool
    {
        $role = $this->where('id', $roleId)->where('tenant_id', $tenantId)->first();
        if (!$role) {
            return false;
        }

        $updateData = [];
        if (isset($data['title']))       $updateData['title'] = $data['title'];
        if (isset($data['name']))        $updateData['name'] = $data['name'];
        if (isset($data['description'])) $updateData['description'] = $data['description'];
        if (isset($data['permissions'])) {
            $updateData['permissions'] = is_array($data['permissions'])
                ? json_encode(array_values($data['permissions']))
                : $data['permissions'];
        }

        return $this->update($roleId, $updateData);
    }

    /**
     * ลบ Role ประจำร้าน (ห้ามลบ template ส่วนกลาง tenant_id = 0 ผ่านฟังก์ชันนี้)
     */
    public function deleteTenantRole(int $tenantId, int $roleId): bool
    {
        if ($tenantId === 0) {
            return false;
        }

        $role = $this->where('id', $roleId)->where('tenant_id', $tenantId)->first();
        if (!$role) {
            return false;
        }

        return $this->delete($roleId);
    }
}
