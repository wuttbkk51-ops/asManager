<?php

namespace App\Models;

use CodeIgniter\Model;

class UserSettingModel extends Model
{
    protected $table            = 'user_settings';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'user_id',
        'setting_key',
        'setting_value',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * ดึงค่าการตั้งค่าตาม key ของผู้ใช้
     */
    public function getSetting(int $userId, string $key, $default = null)
    {
        $row = $this->db->table($this->table)
                        ->where('user_id', $userId)
                        ->where('setting_key', $key)
                        ->get()
                        ->getRowArray();

        return (!empty($row) && isset($row['setting_value']) && $row['setting_value'] !== '') 
            ? $row['setting_value'] 
            : $default;
    }

    /**
     * ดึงการตั้งค่าทั้งหมดของผู้ใช้ในรูปแบบ [key => value]
     */
    public function getAllSettings(int $userId): array
    {
        $rows = $this->db->table($this->table)
                         ->where('user_id', $userId)
                         ->get()
                         ->getResultArray();

        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        return $settings;
    }

    /**
     * บันทึกหรืออัปเดตการตั้งค่าของผู้ใช้
     */
    public function setSetting(int $userId, string $key, $value): bool
    {
        $now = date('Y-m-d H:i:s');
        $existing = $this->db->table($this->table)
                             ->where('user_id', $userId)
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
            'user_id'       => $userId,
            'setting_key'   => $key,
            'setting_value' => (string)$value,
            'created_at'    => $now,
            'updated_at'    => $now,
        ]);
    }
}
