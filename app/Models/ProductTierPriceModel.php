<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductTierPriceModel extends Model
{
    protected $table            = 'product_tier_prices';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'tenant_id',
        'product_id',
        'tier_id',
        'calc_type', // fixed, discount_percent, discount_amount, cost_plus_percent, cost_plus_amount
        'value',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * ดึงราคาพิเศษ Override รายสินค้าใน Tier ที่ระบุ
     */
    public function getOverride(int $productId, int $tierId): ?array
    {
        return $this->where('product_id', $productId)
                    ->where('tier_id', $tierId)
                    ->first();
    }

    /**
     * Owner กำหนดหรืออัปเดตราคาพิเศษ Override เฉพาะสินค้าใน Tier ที่ระบุ
     */
    public function setProductOverride(int $tenantId, int $productId, int $tierId, string $calcType, float $value): bool
    {
        $existing = $this->where('tenant_id', $tenantId)
                         ->where('product_id', $productId)
                         ->where('tier_id', $tierId)
                         ->first();

        $now = date('Y-m-d H:i:s');
        if ($existing) {
            return $this->update($existing['id'], [
                'calc_type'  => $calcType,
                'value'      => $value,
                'updated_at' => $now,
            ]);
        } else {
            return $this->insert([
                'tenant_id'  => $tenantId,
                'product_id' => $productId,
                'tier_id'    => $tierId,
                'calc_type'  => $calcType,
                'value'      => $value,
                'created_at' => $now,
                'updated_at' => $now,
            ]) !== false;
        }
    }

    /**
     * Owner ยกเลิกราคาพิเศษ Override เฉพาะสินค้า (กลับไปใช้สูตรกลางของ Tier)
     */
    public function removeProductOverride(int $tenantId, int $productId, int $tierId): bool
    {
        return $this->where('tenant_id', $tenantId)
                    ->where('product_id', $productId)
                    ->where('tier_id', $tierId)
                    ->delete();
    }
}
