<?php

namespace App\Models;

use CodeIgniter\Model;

class TenantSettingModel extends Model
{
    protected $table            = 'tenant_settings';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'tenant_id',
        'setting_key',
        'setting_value',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * ดึงค่าการตั้งค่าตาม key ที่ระบุ
     */
    public function getSetting(int $tenantId, string $key, $default = null)
    {
        $row = $this->db->table($this->table)
                        ->where('tenant_id', $tenantId)
                        ->where('setting_key', $key)
                        ->get()
                        ->getRowArray();

        return (!empty($row) && isset($row['setting_value']) && $row['setting_value'] !== '') 
            ? $row['setting_value'] 
            : $default;
    }

    /**
     * ดึงการตั้งค่าทั้งหมดของ tenant ออกมาเป็น Associative Array [key => value]
     */
    public function getAllSettings(int $tenantId): array
    {
        $rows = $this->db->table($this->table)
                         ->where('tenant_id', $tenantId)
                         ->get()
                         ->getResultArray();

        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    }

    /**
     * บันทึกหรืออัปเดตการตั้งค่า
     */
    public function setSetting(int $tenantId, string $key, $value): bool
    {
        $now = date('Y-m-d H:i:s');
        $existing = $this->db->table($this->table)
                             ->where('tenant_id', $tenantId)
                             ->where('setting_key', $key)
                             ->get()
                             ->getRowArray();

        if ($existing) {
            return $this->db->table($this->table)
                            ->where('id', $existing['id'])
                            ->update([
                                'setting_value' => (string)$value,
                                'updated_at'    => $now,
                            ]);
        }

        return $this->db->table($this->table)->insert([
            'tenant_id'     => $tenantId,
            'setting_key'   => $key,
            'setting_value' => (string)$value,
            'created_at'    => $now,
            'updated_at'    => $now,
        ]);
    }
}
