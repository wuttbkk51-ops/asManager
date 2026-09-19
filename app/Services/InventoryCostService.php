<?php

namespace App\Services;

use App\Models\ProductModel;
use App\Models\ProductCostLogModel;

class InventoryCostService
{
    protected $db;
    protected $productModel;
    protected $costLogModel;

    public const METHOD_MOVING_AVERAGE = 'moving_average'; // ต้นทุนเฉลี่ยถ่วงน้ำหนักเคลื่อนที่
    public const METHOD_LATEST_COST    = 'latest_cost';    // ต้นทุนซื้อครั้งล่าสุด
    public const METHOD_HIGHEST_COST   = 'highest_cost';   // ต้นทุนสูงสุดป้องกันขาดทุน
    public const METHOD_MANUAL         = 'manual';         // ต้นทุนคงที่ ปรับเองด้วยตนเอง

    public function __construct()
    {
        $this->db           = \Config\Database::connect();
        $this->productModel = new ProductModel();
        $this->costLogModel = new ProductCostLogModel();
    }

    /**
     * ดึงสูตรคำนวณต้นทุนเริ่มต้นของร้านค้า (Tenant Default Method)
     */
    public function getTenantCostingMethod(int $tenantId): string
    {
        $row = $this->db->table('tenant_settings')
                        ->where('tenant_id', $tenantId)
                        ->where('setting_key', 'inventory_costing_method')
                        ->get()
                        ->getRowArray();

        return $row['setting_value'] ?? self::METHOD_MOVING_AVERAGE;
    }

    /**
     * เจ้าของร้าน (Owner) ตั้งค่าสูตรคำนวณต้นทุนของร้าน
     */
    public function setTenantCostingMethod(int $tenantId, string $method): bool
    {
        $validMethods = [
            self::METHOD_MOVING_AVERAGE,
            self::METHOD_LATEST_COST,
            self::METHOD_HIGHEST_COST,
            self::METHOD_MANUAL,
        ];

        if (!in_array($method, $validMethods)) {
            return false;
        }

        $now = date('Y-m-d H:i:s');
        $existing = $this->db->table('tenant_settings')
                             ->where('tenant_id', $tenantId)
                             ->where('setting_key', 'inventory_costing_method')
                             ->get()
                             ->getRowArray();

        if ($existing) {
            return $this->db->table('tenant_settings')
                            ->where('id', $existing['id'])
                            ->update(['setting_value' => $method, 'updated_at' => $now]);
        } else {
            return $this->db->table('tenant_settings')->insert([
                'tenant_id'     => $tenantId,
                'setting_key'   => 'inventory_costing_method',
                'setting_value' => $method,
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);
        }
    }

    /**
     * ดึงสูตรคำนวณต้นทุนที่ใช้กับสินค้าตัวที่ระบุ (รองรับ Product Override)
     */
    public function getProductCostingMethod(int $tenantId, int $productId): string
    {
        $product = $this->productModel->where('id', $productId)
                                      ->where('tenant_id', $tenantId)
                                      ->first();

        if ($product && !empty($product['costing_method'])) {
            return $product['costing_method'];
        }

        return $this->getTenantCostingMethod($tenantId);
    }

    /**
     * คำนวณต้นทุนใหม่ตอนรับเข้าสินค้าตามสูตรที่เลือก
     */
    public function calculateNewCost(
        int $tenantId,
        int $productId,
        float $receivedQty,
        float $receivedUnitCost,
        ?string $forcedMethod = null
    ): array {
        $product = $this->productModel->where('id', $productId)
                                      ->where('tenant_id', $tenantId)
                                      ->first();

        if (!$product) {
            return [
                'success' => false,
                'message' => 'Product not found',
            ];
        }

        $currentCost = (float)$product['cost_price'];
        // ดึงจำนวนคงเหลือก่อนรับเข้าจริง
        $currentStock = $this->productModel->getStockBalance($tenantId, $productId);

        $method = $forcedMethod ?: $this->getProductCostingMethod($tenantId, $productId);
        $newCost = $currentCost;

        switch ($method) {
            case self::METHOD_MOVING_AVERAGE:
                // Weighted Moving Average Formula
                if ($currentStock <= 0) {
                    // หากสต็อกเดิมหมดหรือติดลบ ให้ใช้ราคาที่รับเข้าล็อตใหม่
                    $newCost = $receivedUnitCost;
                } else {
                    $totalQty = $currentStock + $receivedQty;
                    $totalValue = ($currentStock * $currentCost) + ($receivedQty * $receivedUnitCost);
                    $newCost = $totalQty > 0 ? round($totalValue / $totalQty, 2) : $receivedUnitCost;
                }
                break;

            case self::METHOD_LATEST_COST:
                // ใช้ราคาซื้อล็อตล่าสุดเสมอ
                $newCost = round($receivedUnitCost, 2);
                break;

            case self::METHOD_HIGHEST_COST:
                // ใช้ราคาสูงสุดระหว่างราคาเดิมกับราคาที่รับเข้า ป้องกันขาดทุน
                $newCost = round(max($currentCost, $receivedUnitCost), 2);
                break;

            case self::METHOD_MANUAL:
            default:
                // ไม่ปรับต้นทุนอัตโนมัติ ยึดราคาเดิม
                $newCost = $currentCost;
                break;
        }

        return [
            'success'        => true,
            'product_id'     => $productId,
            'product_name'   => $product['name'],
            'old_cost'       => $currentCost,
            'new_cost'       => $newCost,
            'cost_diff'      => round($newCost - $currentCost, 2),
            'received_qty'   => $receivedQty,
            'received_cost'  => $receivedUnitCost,
            'current_stock'  => $currentStock,
            'costing_method' => $method,
        ];
    }

    /**
     * บันทึกการปรับปรุงต้นทุนสินค้า พร้อมลง Log ตรวจสอบย้อนหลัง (Audit Trail)
     */
    public function applyCostUpdate(
        int $tenantId,
        int $productId,
        array $calcResult,
        string $refType,
        ?int $refId = null,
        ?string $notes = null,
        ?int $userId = null
    ): bool {
        $now = date('Y-m-d H:i:s');

        // 1. อัปเดตราคาต้นทุนในตาราง products
        $this->productModel->update($productId, [
            'cost_price' => $calcResult['new_cost'],
            'updated_at' => $now,
        ]);

        // 2. บันทึกลงตาราง product_cost_logs
        $this->costLogModel->insert([
            'tenant_id'      => $tenantId,
            'product_id'     => $productId,
            'old_cost'       => $calcResult['old_cost'],
            'new_cost'       => $calcResult['new_cost'],
            'received_qty'   => $calcResult['received_qty'],
            'received_cost'  => $calcResult['received_cost'],
            'costing_method' => $calcResult['costing_method'],
            'ref_type'       => $refType,
            'ref_id'         => $refId,
            'notes'          => $notes,
            'created_by'     => $userId,
            'created_at'     => $now,
        ]);

        return true;
    }

    /**
     * ปันส่วนค่าขนส่งและค่าใช้จ่ายเพิ่มเติม (Landed Cost Allocation) เข้ารายการสินค้า
     * 
     * @param array $items [['product_id' => 1, 'qty' => 10, 'cost' => 100]]
     * @param float $shippingCost
     * @param float $otherExpenses
     * @param string $method 'by_value' หรือ 'by_qty'
     * @return array รายการสินค้าพร้อม effective_cost ต่อหน่วยที่รวมค่าใช้จ่ายแล้ว
     */
    public function allocateLandedCost(
        array $items,
        float $shippingCost,
        float $otherExpenses = 0.00,
        string $method = 'by_value'
    ): array {
        $totalExtra = $shippingCost + $otherExpenses;
        if ($totalExtra <= 0 || empty($items)) {
            // ไม่ต้องปันส่วน
            foreach ($items as &$item) {
                $item['allocated_extra'] = 0.00;
                $item['effective_cost']  = (float)$item['cost'];
            }
            return $items;
        }

        if ($method === 'by_qty') {
            // ปันส่วนตามจำนวนชิ้น
            $totalQty = array_sum(array_column($items, 'qty'));
            foreach ($items as &$item) {
                $qtyRatio = $totalQty > 0 ? ($item['qty'] / $totalQty) : 0;
                $allocatedExtra = round($totalExtra * $qtyRatio, 2);
                $extraPerUnit   = $item['qty'] > 0 ? round($allocatedExtra / $item['qty'], 2) : 0;

                $item['allocated_extra'] = $allocatedExtra;
                $item['effective_cost']  = round((float)$item['cost'] + $extraPerUnit, 2);
            }
        } else {
            // ปันส่วนตามสัดส่วนมูลค่าสินค้า (by_value - ค่าเริ่มต้น)
            $totalValue = 0.0;
            foreach ($items as $item) {
                $totalValue += ((float)$item['cost'] * (float)$item['qty']);
            }

            foreach ($items as &$item) {
                $itemSubtotal = ((float)$item['cost'] * (float)$item['qty']);
                $valueRatio   = $totalValue > 0 ? ($itemSubtotal / $totalValue) : 0;
                $allocatedExtra = round($totalExtra * $valueRatio, 2);
                $extraPerUnit   = $item['qty'] > 0 ? round($allocatedExtra / $item['qty'], 2) : 0;

                $item['allocated_extra'] = $allocatedExtra;
                $item['effective_cost']  = round((float)$item['cost'] + $extraPerUnit, 2);
            }
        }

        return $items;
    }
}
