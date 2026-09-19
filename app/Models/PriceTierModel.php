<?php

namespace App\Models;

use CodeIgniter\Model;

class PriceTierModel extends Model
{
    protected $table            = 'price_tiers';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'tenant_id',
        'code',
        'name',
        'calc_type', // fixed, discount_percent, discount_amount, cost_plus_percent, cost_plus_amount
        'default_rate',
        'is_default',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * ดึงกลุ่มราคาเริ่มต้น (Default Retail Tier)
     */
    public function getDefaultTier(int $tenantId): ?array
    {
        return $this->where('tenant_id', $tenantId)
                    ->where('is_default', 1)
                    ->first();
    }

    /**
     * ดึงรายการกลุ่มระดับราคาทั้งหมดของร้าน
     */
    public function getTenantTiers(int $tenantId): array
    {
        return $this->where('tenant_id', $tenantId)
                    ->orderBy('is_default', 'DESC')
                    ->orderBy('id', 'ASC')
                    ->findAll();
    }

    /**
     * Owner สร้างกลุ่มระดับราคาใหม่ของร้าน
     */
    public function createTier(int $tenantId, array $data): int|false
    {
        return $this->insert([
            'tenant_id'    => $tenantId,
            'code'         => strtolower(trim($data['code'] ?? 'tier_' . time())),
            'name'         => trim($data['name']),
            'calc_type'    => $data['calc_type'] ?? 'fixed',
            'default_rate' => (float)($data['default_rate'] ?? 0.00),
            'is_default'   => !empty($data['is_default']) ? 1 : 0,
        ]);
    }

    /**
     * Owner แก้ไขกลุ่มระดับราคาประจำร้าน (ป้องกันการแก้ไขข้ามร้าน)
     */
    public function updateTier(int $tenantId, int $tierId, array $data): bool
    {
        $tier = $this->where('id', $tierId)->where('tenant_id', $tenantId)->first();
        if (!$tier) {
            return false;
        }

        $updateData = [];
        if (isset($data['name']))         $updateData['name'] = trim($data['name']);
        if (isset($data['code']))         $updateData['code'] = strtolower(trim($data['code']));
        if (isset($data['calc_type']))    $updateData['calc_type'] = $data['calc_type'];
        if (isset($data['default_rate'])) $updateData['default_rate'] = (float)$data['default_rate'];
        if (isset($data['is_default']))   $updateData['is_default'] = (int)$data['is_default'];

        return $this->update($tierId, $updateData);
    }

    /**
     * Owner ลบกลุ่มระดับราคาประจำร้าน
     */
    public function deleteTier(int $tenantId, int $tierId): bool
    {
        $tier = $this->where('id', $tierId)->where('tenant_id', $tenantId)->first();
        if (!$tier) {
            return false;
        }

        // ลบข้อมูลราคา Override ที่ผูกกับ Tier นี้ด้วย
        $this->db->table('product_tier_prices')->where('tier_id', $tierId)->where('tenant_id', $tenantId)->delete();

        // รีเซ็ตลูกค้าที่ผูกกับ Tier นี้ให้กลับเป็น NULL (ดึงราคาขายหลัก sell_price)
        $this->db->table('peoples')->where('price_tier_id', $tierId)->where('tenant_id', $tenantId)->update(['price_tier_id' => null]);

        return $this->delete($tierId);
    }
}
