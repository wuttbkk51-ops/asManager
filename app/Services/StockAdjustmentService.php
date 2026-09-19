<?php

namespace App\Services;

use App\Models\StockAdjustmentModel;
use App\Models\StockAdjustmentItemModel;
use App\Models\ProductModel;
use App\Models\ProductSerialModel;
use App\Services\FinanceService;

class StockAdjustmentService
{
    protected $db;
    protected $adjustmentModel;
    protected $itemModel;
    protected $productModel;
    protected $serialModel;
    protected $financeService;

    public function __construct()
    {
        $this->db              = \Config\Database::connect();
        $this->adjustmentModel = new StockAdjustmentModel();
        $this->itemModel       = new StockAdjustmentItemModel();
        $this->productModel    = new ProductModel();
        $this->serialModel     = new ProductSerialModel();
        $this->financeService  = new FinanceService();
    }

    /**
     * สร้างเอกสารตรวจนับ/ปรับปรุงสต็อก (Create Adjustment Draft)
     */
    public function createAdjustment(
        int $tenantId,
        int $branchId,
        int $warehouseId,
        string $type, // cycle_count, damage, loss, found
        array $items, // [['product_id' => 1, 'counted_qty' => 95, 'serial_id' => null, 'reason' => 'ตรวจนับสิ้นเดือน']]
        ?string $notes = null,
        ?int $userId = null
    ): array {
        $now = date('Y-m-d H:i:s');
        if (empty($items)) {
            return ['success' => false, 'message' => 'No items specified'];
        }

        $adjNo = $this->adjustmentModel->generateAdjustmentNo($tenantId);

        $adjId = $this->adjustmentModel->insert([
            'tenant_id'        => $tenantId,
            'branch_id'        => $branchId,
            'warehouse_id'     => $warehouseId,
            'adjustment_no'    => $adjNo,
            'type'             => $type,
            'status'           => 'draft',
            'total_loss_value' => 0.00,
            'total_gain_value' => 0.00,
            'notes'            => $notes,
            'created_by'       => $userId,
            'created_at'       => $now,
            'updated_at'       => $now,
        ]);

        $totalLoss = 0.00;
        $totalGain = 0.00;

        foreach ($items as $item) {
            $productId = (int)$item['product_id'];
            $product   = $this->productModel->find($productId);
            $costPrice = $product ? (float)$product['cost_price'] : 0.00;

            // ดึงยอดในระบบปัจจุบันของคลังนี้
            $systemQty  = $this->productModel->getStockBalance($tenantId, $productId, $branchId, $warehouseId);
            $countedQty = (float)$item['counted_qty'];
            $diffQty    = round($countedQty - $systemQty, 2);
            $diffAmount = round($diffQty * $costPrice, 2);

            if ($diffQty < 0) {
                $totalLoss += abs($diffAmount);
            } elseif ($diffQty > 0) {
                $totalGain += $diffAmount;
            }

            $this->itemModel->insert([
                'stock_adjustment_id' => $adjId,
                'product_id'          => $productId,
                'serial_id'           => !empty($item['serial_id']) ? (int)$item['serial_id'] : null,
                'system_qty'          => $systemQty,
                'counted_qty'         => $countedQty,
                'diff_qty'            => $diffQty,
                'cost_price'          => $costPrice,
                'diff_amount'         => $diffAmount,
                'reason'              => $item['reason'] ?? null,
                'created_at'          => $now,
            ]);
        }

        $this->adjustmentModel->update($adjId, [
            'total_loss_value' => $totalLoss,
            'total_gain_value' => $totalGain,
            'updated_at'       => $now,
        ]);

        return [
            'success'          => true,
            'adjustment_id'    => (int)$adjId,
            'adjustment_no'    => $adjNo,
            'status'           => 'draft',
            'total_loss_value' => $totalLoss,
            'total_gain_value' => $totalGain,
        ];
    }

    /**
     * อนุมัติการปรับปรุงสต็อก (Approve Adjustment: ปรับยอดคงเหลือใน Stock Ledger & บันทึกบัญชีการเงิน)
     */
    public function approveAdjustment(int $tenantId, int $adjustmentId, ?int $userId = null): array
    {
        $now = date('Y-m-d H:i:s');
        $adj = $this->adjustmentModel->where('id', $adjustmentId)->where('tenant_id', $tenantId)->first();
        if (!$adj || $adj['status'] !== 'draft') {
            return ['success' => false, 'message' => 'Adjustment not found or not in draft status'];
        }

        $items = $this->itemModel->where('stock_adjustment_id', $adjustmentId)->findAll();

        foreach ($items as $item) {
            $diffQty = (float)$item['diff_qty'];
            if ($diffQty == 0) {
                continue;
            }

            $diffAmount = (float)$item['diff_amount'];
            $costPrice  = (float)$item['cost_price'];

            if ($diffQty < 0) {
                // สินค้าขาด / ชำรุด (ตัดสต็อกออก)
                $this->db->table('stock_transactions')->insert([
                    'tenant_id'     => $tenantId,
                    'branch_id'     => $adj['branch_id'],
                    'warehouse_id'  => $adj['warehouse_id'],
                    'product_id'    => $item['product_id'],
                    'serial_id'     => $item['serial_id'],
                    'movement_type' => 'adjustment_out',
                    'qty'           => $diffQty, // ค่าติดลบ
                    'cost_price'    => $costPrice,
                    'ref_type'      => 'stock_adjustment',
                    'ref_id'        => $adjustmentId,
                    'notes'         => 'ปรับปรุงสต็อกลด (ขาด/ชำรุด) ตามใบปรับ: ' . $adj['adjustment_no'],
                    'created_by'    => $userId,
                    'created_at'    => $now,
                ]);

                // บันทึกบัญชีเป็นค่าใช้จ่ายขาดทุนจากสต็อก (inventory_loss)
                if (abs($diffAmount) > 0) {
                    $this->financeService->recordTransaction(
                        $tenantId,
                        (int)$adj['branch_id'],
                        'expense',
                        'inventory_loss',
                        abs($diffAmount),
                        'inventory_adjustment',
                        'stock_adjustment',
                        $adjustmentId,
                        'ขาดทุนจากการตรวจนับ/ปรับสต็อก เลขที่: ' . $adj['adjustment_no'],
                        $userId
                    );
                }

                // หากมี Serial Number ให้ปรับสถานะ Serial
                if (!empty($item['serial_id'])) {
                    $serialStatus = $adj['type'] === 'damage' ? 'damaged' : 'lost';
                    $this->serialModel->update($item['serial_id'], [
                        'status'     => $serialStatus,
                        'notes'      => 'ตัดสต็อกตามใบปรับ ' . $adj['adjustment_no'] . ': ' . ($item['reason'] ?? ''),
                        'updated_at' => $now,
                    ]);
                }
            } else {
                // สินค้าเกิน / ตรวจพบ (เพิ่มสต็อกเข้า)
                $this->db->table('stock_transactions')->insert([
                    'tenant_id'     => $tenantId,
                    'branch_id'     => $adj['branch_id'],
                    'warehouse_id'  => $adj['warehouse_id'],
                    'product_id'    => $item['product_id'],
                    'serial_id'     => $item['serial_id'],
                    'movement_type' => 'adjustment_in',
                    'qty'           => $diffQty, // ค่าบวก
                    'cost_price'    => $costPrice,
                    'ref_type'      => 'stock_adjustment',
                    'ref_id'        => $adjustmentId,
                    'notes'         => 'ปรับปรุงสต็อกเพิ่ม (สินค้าเกิน/พบ) ตามใบปรับ: ' . $adj['adjustment_no'],
                    'created_by'    => $userId,
                    'created_at'    => $now,
                ]);

                // บันทึกบัญชีเป็นรายได้กำไรจากสต็อก (inventory_gain)
                if ($diffAmount > 0) {
                    $this->financeService->recordTransaction(
                        $tenantId,
                        (int)$adj['branch_id'],
                        'income',
                        'inventory_gain',
                        $diffAmount,
                        'inventory_adjustment',
                        'stock_adjustment',
                        $adjustmentId,
                        'กำไรจากการตรวจนับสต็อกเกิน เลขที่: ' . $adj['adjustment_no'],
                        $userId
                    );
                }

                if (!empty($item['serial_id'])) {
                    $this->serialModel->update($item['serial_id'], [
                        'branch_id'    => $adj['branch_id'],
                        'warehouse_id' => $adj['warehouse_id'],
                        'status'       => 'in_stock',
                        'notes'        => 'รับคืนสต็อกตามใบปรับ ' . $adj['adjustment_no'],
                        'updated_at'   => $now,
                    ]);
                }
            }
        }

        $this->adjustmentModel->update($adjustmentId, [
            'status'      => 'approved',
            'approved_by' => $userId,
            'approved_at' => $now,
            'updated_at'  => $now,
        ]);

        return [
            'success'       => true,
            'adjustment_id' => $adjustmentId,
            'adjustment_no' => $adj['adjustment_no'],
            'status'        => 'approved',
        ];
    }
}
