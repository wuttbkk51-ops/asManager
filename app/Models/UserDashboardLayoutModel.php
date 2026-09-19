<?php

namespace App\Models;

use CodeIgniter\Model;

class UserDashboardLayoutModel extends Model
{
    protected $table            = 'user_dashboard_layouts';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'user_id',
        'tenant_id',
        'layout_data',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * ดึง Layout ของ User ตาม user_id และ tenant_id
     */
    public function getLayout(int $userId, ?int $tenantId): ?array
    {
        $builder = $this->db->table($this->table)->where('user_id', $userId);
        if ($tenantId !== null) {
            $builder->where('tenant_id', $tenantId);
        } else {
            $builder->where('tenant_id IS NULL');
        }

        $row = $builder->get()->getRowArray();
        if ($row && !empty($row['layout_data'])) {
            $decoded = json_decode($row['layout_data'], true);
            return is_array($decoded) ? $decoded : null;
        }

        return null;
    }

    /**
     * บันทึกหรืออัปเดต Layout ของ User
     */
    public function saveLayout(int $userId, ?int $tenantId, array $layout): bool
    {
        $now = date('Y-m-d H:i:s');
        $json = json_encode($layout, JSON_UNESCAPED_UNICODE);

        $builder = $this->db->table($this->table)->where('user_id', $userId);
        if ($tenantId !== null) {
            $builder->where('tenant_id', $tenantId);
        } else {
            $builder->where('tenant_id IS NULL');
        }

        $existing = $builder->get()->getRowArray();

        if ($existing) {
            return $this->db->table($this->table)
                            ->where('id', $existing['id'])
                            ->update([
                                'layout_data' => $json,
                                'updated_at'  => $now,
                            ]);
        }

        return $this->db->table($this->table)->insert([
            'user_id'     => $userId,
            'tenant_id'   => $tenantId,
            'layout_data' => $json,
            'created_at'  => $now,
            'updated_at'  => $now,
        ]);
    }

    /**
     * ลบ Layout ของ User (เพื่อคืนค่า Default)
     */
    public function deleteLayout(int $userId, ?int $tenantId): bool
    {
        $builder = $this->db->table($this->table)->where('user_id', $userId);
        if ($tenantId !== null) {
            $builder->where('tenant_id', $tenantId);
        } else {
            $builder->where('tenant_id IS NULL');
        }

        return (bool)$builder->delete();
    }
}
