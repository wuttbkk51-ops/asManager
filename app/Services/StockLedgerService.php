<?php

namespace App\Services;

use App\Models\ProductModel;
use App\Models\ProductSerialModel;

class StockLedgerService
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    /**
     * รับสินค้าเข้าคลังโดยตรง (Direct Inbound สำหรับร้านโชห่วยหรือโหมด Simple)
     */
    public function directInbound(
        int $tenantId,
        int $branchId,
        int $warehouseId,
        int $productId,
        float $qty,
        float $unitCost,
        ?string $serialNo = null,
        ?string $notes = null,
        ?int $userId = null,
        ?string $forcedCostingMethod = null
    ): int {
        $now = date('Y-m-d H:i:s');
        $serialId = null;

        // คำนวณต้นทุนสินค้าใหม่ตามสูตรที่เลือก (ก่อนบันทึกสต็อก เพื่อให้ได้ยอดคงเหลือก่อนรับเข้า)
        $costService = new InventoryCostService();
        $costCalc = $costService->calculateNewCost($tenantId, $productId, $qty, $unitCost, $forcedCostingMethod);

        // ถ้ามีการระบุ Serial Number หรือเป็นสินค้าที่เปิดใช้ Hybrid Serial
        if (!empty($serialNo)) {
            $serialModel = new ProductSerialModel();
            $serialId = $serialModel->insert([
                'tenant_id'    => $tenantId,
                'product_id'   => $productId,
                'branch_id'    => $branchId,
                'warehouse_id' => $warehouseId,
                'serial_no'    => trim($serialNo),
                'status'       => 'in_stock',
                'notes'        => $notes,
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);
        }

        // บันทึก Immutable Stock Ledger
        $this->db->table('stock_transactions')->insert([
            'tenant_id'     => $tenantId,
            'branch_id'     => $branchId,
            'warehouse_id'  => $warehouseId,
            'product_id'    => $productId,
            'serial_id'     => $serialId,
            'movement_type' => 'direct_in',
            'qty'           => $qty,
            'cost_price'    => $unitCost,
            'ref_type'      => 'manual',
            'ref_id'        => null,
            'notes'         => $notes ?: 'รับสินค้าเข้าคลังโดยตรง',
            'created_by'    => $userId,
            'created_at'    => $now,
        ]);
        $txnId = $this->db->insertID();

        // ปรับปรุงราคาต้นทุนใน products และบันทึกลง product_cost_logs
        if ($costCalc['success']) {
            $costService->applyCostUpdate($tenantId, $productId, $costCalc, 'direct_in', $txnId, $notes, $userId);
        }

        return $txnId;
    }

    /**
     * ตรวจรับสินค้าอ้างอิงใบสั่งซื้อ (GRN against PO สำหรับร้านใหญ่หรือโหมดเป็นระบบ)
     */
    public function receivePurchaseOrder(
        int $tenantId,
        int $poId,
        int $warehouseId,
        array $receivedItems, // [['product_id' => 1, 'qty' => 10, 'cost' => 50, 'serials' => ['SN1', 'SN2']]]
        ?string $notes = null,
        ?int $userId = null,
        float $shippingCost = 0.00,
        float $otherExpenses = 0.00,
        ?string $forcedCostingMethod = null,
        bool $allocateToCost = true,
        string $paymentMethod = 'cash'
    ): int {
        $now = date('Y-m-d H:i:s');
        $po = $this->db->table('purchase_orders')->where('id', $poId)->where('tenant_id', $tenantId)->get()->getRowArray();
        if (!$po) {
            return 0;
        }

        $costService = new InventoryCostService();

        // ตรวจสอบว่า Owner เลือกปันส่วนค่าส่งเข้าต้นทุนสินค้าหรือไม่
        if ($allocateToCost && ($shippingCost > 0 || $otherExpenses > 0)) {
            $landedSetting = $this->db->table('tenant_settings')
                                      ->where('tenant_id', $tenantId)
                                      ->where('setting_key', 'landed_cost_method')
                                      ->get()
                                      ->getRowArray();
            $landedMethod = $landedSetting['setting_value'] ?? 'by_value';
            $allocatedItems = $costService->allocateLandedCost($receivedItems, $shippingCost, $otherExpenses, $landedMethod);
        } else {
            // ไม่ปันส่วน: ยึดต้นทุนเดิมของสินค้าเพียวๆ
            $allocatedItems = $receivedItems;
            foreach ($allocatedItems as &$it) {
                $it['effective_cost'] = (float)$it['cost'];
            }
        }

        // 1. สร้างใบตรวจรับสินค้า (GRN)
        $grnNo = 'GRN' . date('ym') . '-' . str_pad((string)mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $this->db->table('goods_receipts')->insert([
            'tenant_id'         => $tenantId,
            'branch_id'         => $po['branch_id'],
            'warehouse_id'      => $warehouseId,
            'grn_no'            => $grnNo,
            'purchase_order_id' => $poId,
            'shipping_cost'     => $shippingCost,
            'other_expenses'    => $otherExpenses,
            'received_date'     => $now,
            'received_by'       => $userId,
            'notes'             => $notes,
            'created_at'        => $now,
            'updated_at'        => $now,
        ]);
        $grnId = $this->db->insertID();

        // 2. บันทึกรายการที่รับเข้าและอัปเดต PO Items + คำนวณต้นทุน
        foreach ($allocatedItems as $item) {
            $effectiveCost = (float)($item['effective_cost'] ?? $item['cost']);

            // คำนวณต้นทุนใหม่ก่อนปรับสต็อก
            $costCalc = $costService->calculateNewCost($tenantId, (int)$item['product_id'], (float)$item['qty'], $effectiveCost, $forcedCostingMethod);

            $this->db->table('goods_receipt_items')->insert([
                'goods_receipt_id' => $grnId,
                'product_id'       => $item['product_id'],
                'qty_received'     => $item['qty'],
                'unit_cost'        => $effectiveCost,
                'created_at'       => $now,
            ]);

            // อัปเดต qty_received ใน purchase_order_items
            $this->db->query("UPDATE purchase_order_items SET qty_received = qty_received + ? WHERE purchase_order_id = ? AND product_id = ?", [
                $item['qty'], $poId, $item['product_id']
            ]);

            // บันทึก Serial Numbers ถ้ามี
            if (!empty($item['serials']) && is_array($item['serials'])) {
                foreach ($item['serials'] as $sn) {
                    $serialModel = new ProductSerialModel();
                    $sId = $serialModel->insert([
                        'tenant_id'    => $tenantId,
                        'product_id'   => $item['product_id'],
                        'branch_id'    => $po['branch_id'],
                        'warehouse_id' => $warehouseId,
                        'serial_no'    => trim($sn),
                        'status'       => 'in_stock',
                        'created_at'   => $now,
                        'updated_at'   => $now,
                    ]);

                    // ลง Ledger รายชิ้น
                    $this->db->table('stock_transactions')->insert([
                        'tenant_id'     => $tenantId,
                        'branch_id'     => $po['branch_id'],
                        'warehouse_id'  => $warehouseId,
                        'product_id'    => $item['product_id'],
                        'serial_id'     => $sId,
                        'movement_type' => 'po_receive',
                        'qty'           => 1.00,
                        'cost_price'    => $effectiveCost,
                        'ref_type'      => 'goods_receipt',
                        'ref_id'        => $grnId,
                        'notes'         => 'ตรวจรับตามใบ PO: ' . $po['po_no'],
                        'created_by'    => $userId,
                        'created_at'    => $now,
                    ]);
                }
            } else {
                // ลง Ledger จำนวนรวม
                $this->db->table('stock_transactions')->insert([
                    'tenant_id'     => $tenantId,
                    'branch_id'     => $po['branch_id'],
                    'warehouse_id'  => $warehouseId,
                    'product_id'    => $item['product_id'],
                    'serial_id'     => null,
                    'movement_type' => 'po_receive',
                    'qty'           => $item['qty'],
                    'cost_price'    => $effectiveCost,
                    'ref_type'      => 'goods_receipt',
                    'ref_id'        => $grnId,
                    'notes'         => 'ตรวจรับตามใบ PO: ' . $po['po_no'],
                    'created_by'    => $userId,
                    'created_at'    => $now,
                ]);
            }

            // ปรับปรุงต้นทุนสินค้าตามสูตรที่เลือก พร้อมบันทึกลง Audit Log
            if ($costCalc['success']) {
                $costService->applyCostUpdate($tenantId, (int)$item['product_id'], $costCalc, 'po_receive', $grnId, 'GRN: ' . $grnNo, $userId);
            }
        }

        // ตรวจสอบว่า PO รับครบหรือยัง
        $poItems = $this->db->table('purchase_order_items')->where('purchase_order_id', $poId)->get()->getResultArray();
        $isComplete = true;
        foreach ($poItems as $pItem) {
            if ((float)$pItem['qty_received'] < (float)$pItem['qty_ordered']) {
                $isComplete = false;
                break;
            }
        }

        $this->db->table('purchase_orders')->where('id', $poId)->update([
            'status'     => $isComplete ? 'completed' : 'partial',
            'updated_at' => $now,
        ]);

        // 3. จัดการด้านการเงินสำหรับค่าขนส่งและค่าใช้จ่ายเพิ่มเติม (ป้องกันการบันทึกซ้ำซ้อน Zero Double-Counting)
        $totalExtra = $shippingCost + $otherExpenses;
        if ($totalExtra > 0) {
            $financeService = new FinanceService();
            if ($allocateToCost) {
                // กรณีปันส่วนเข้าสินค้า: บันทึกเป็นเงินทุนซื้อสต็อก (Capitalized Inventory Asset)
                // จะไม่ถูกนับเป็น "ค่าใช้จ่ายหน้าร้าน (Shop Expense)" ในงบกำไรขาดทุน แต่จะไปอยู่ในต้นทุนขาย (COGS) ตอนขายสินค้าจริง
                $financeService->recordTransaction(
                    $tenantId,
                    $po['branch_id'],
                    'expense',
                    'inventory_freight_capitalized',
                    $totalExtra,
                    $paymentMethod,
                    'goods_receipt',
                    $grnId,
                    "ค่าขนส่งปันส่วนเข้าต้นทุนสินค้า (Landed Cost) ใบรับ: {$grnNo}",
                    $userId
                );
            } else {
                // กรณีไม่ปันส่วน: บันทึกเป็น "ค่าใช้จ่ายหน้าร้านทันที (Operating Expense)"
                $financeService->recordTransaction(
                    $tenantId,
                    $po['branch_id'],
                    'expense',
                    'shop_expense',
                    $totalExtra,
                    $paymentMethod,
                    'goods_receipt',
                    $grnId,
                    "ค่าขนส่งสินค้า (บันทึกเป็นค่าใช้จ่ายหน้าร้านทันที) ใบรับ: {$grnNo}",
                    $userId
                );
            }
        }

        return $grnId;
    }
}
