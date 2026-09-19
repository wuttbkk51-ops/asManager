<?php

namespace App\Services;

use App\Models\PosOrderModel;
use App\Models\PosOrderItemModel;
use App\Models\ProductModel;
use App\Models\ProductSerialModel;
use App\Models\WalletModel;
use App\Services\PriceTierService;
use App\Services\FinanceService;

class PosService
{
    protected $db;
    protected $orderModel;
    protected $orderItemModel;
    protected $productModel;
    protected $serialModel;
    protected $tierService;
    protected $financeService;

    public function __construct()
    {
        $this->db             = \Config\Database::connect();
        $this->orderModel     = new PosOrderModel();
        $this->orderItemModel = new PosOrderItemModel();
        $this->productModel   = new ProductModel();
        $this->serialModel    = new ProductSerialModel();
        $this->tierService    = new PriceTierService();
        $this->financeService = new FinanceService();
    }

    /**
     * ดำเนินการคิดเงินขายสินค้าหน้าร้าน (POS Checkout)
     * รองรับสินค้าตัดสต็อก สินค้าบริการไม่ตัดสต็อก ระบบกลุ่มราคา Tier และการชำระเงินหลายรูปแบบ
     */
    public function checkout(
        int $tenantId,
        int $branchId,
        int $warehouseId,
        ?int $customerId,
        array $items,
        string $paymentMethod = 'cash',
        float $discountAmount = 0.00,
        ?string $notes = null,
        ?int $userId = null
    ): array {
        $now = date('Y-m-d H:i:s');
        if (empty($items)) {
            return ['success' => false, 'message' => 'No items in cart'];
        }

        // 1. ตรวจสอบกะเงินสดที่เปิดอยู่ของสาขานี้ (ถ้ามี)
        $shift = $this->db->table('cash_shifts')
                          ->where('tenant_id', $tenantId)
                          ->where('branch_id', $branchId)
                          ->where('status', 'open')
                          ->get()
                          ->getRowArray();
        $cashShiftId = $shift ? (int)$shift['id'] : null;

        // 2. คำนวณราคาและรายการสินค้า
        $processedItems = [];
        $totalAmount = 0.00;

        foreach ($items as $item) {
            $productId = (int)$item['product_id'];
            $product = $this->productModel->where('id', $productId)->where('tenant_id', $tenantId)->first();
            if (!$product) {
                continue;
            }

            $qty = (float)($item['qty'] ?? 1.00);
            $trackStock = (int)($product['track_stock'] ?? 1);
            $costPrice = (float)$product['cost_price'];

            // คำนวณราคาขาย: ถ้ามีระบุ custom_price ให้ใช้ค่านั้น มิฉะนั้นคำนวณตาม Tier Price ของลูกค้า
            if (isset($item['custom_price']) && is_numeric($item['custom_price'])) {
                $unitPrice = (float)$item['custom_price'];
            } else {
                $tierCalc = $this->tierService->calculatePrice($productId, null, $customerId);
                $unitPrice = (float)$tierCalc['final_price'];
            }

            $lineTotal = round($unitPrice * $qty, 2);
            $totalAmount += $lineTotal;

            $processedItems[] = [
                'product_id'   => $productId,
                'product_name' => $product['name'],
                'serial_id'    => !empty($item['serial_id']) ? (int)$item['serial_id'] : null,
                'qty'          => $qty,
                'cost_price'   => $costPrice,
                'unit_price'   => $unitPrice,
                'total_price'  => $lineTotal,
                'track_stock'  => $trackStock,
            ];
        }

        $netAmount = max(0.00, round($totalAmount - $discountAmount, 2));

        // 3. สร้างบิลขายในตาราง pos_orders
        $orderNo = $this->orderModel->generateOrderNo($tenantId);
        $orderId = $this->orderModel->insert([
            'tenant_id'       => $tenantId,
            'branch_id'       => $branchId,
            'order_no'        => $orderNo,
            'customer_id'     => $customerId,
            'cash_shift_id'   => $cashShiftId,
            'total_amount'    => $totalAmount,
            'discount_amount' => $discountAmount,
            'net_amount'      => $netAmount,
            'payment_method'  => $paymentMethod,
            'payment_status'  => 'paid',
            'notes'           => $notes,
            'created_by'      => $userId,
            'created_at'      => $now,
            'updated_at'      => $now,
        ]);

        // 4. บันทึกรายการสินค้าในบิล pos_order_items และตัดสต็อกเฉพาะสินค้าที่มี track_stock = 1
        foreach ($processedItems as $pItem) {
            $this->orderItemModel->insert([
                'pos_order_id' => $orderId,
                'product_id'   => $pItem['product_id'],
                'serial_id'    => $pItem['serial_id'],
                'item_name'    => $pItem['product_name'],
                'qty'          => $pItem['qty'],
                'cost_price'   => $pItem['cost_price'],
                'unit_price'   => $pItem['unit_price'],
                'total_price'  => $pItem['total_price'],
                'track_stock'  => $pItem['track_stock'],
                'created_at'   => $now,
            ]);

            // สินค้าประเภทนับสต็อก (track_stock = 1) -> ทำการตัดสต็อก
            if ($pItem['track_stock'] === 1) {
                $this->db->table('stock_transactions')->insert([
                    'tenant_id'     => $tenantId,
                    'branch_id'     => $branchId,
                    'warehouse_id'  => $warehouseId,
                    'product_id'    => $pItem['product_id'],
                    'serial_id'     => $pItem['serial_id'],
                    'movement_type' => 'pos_sale',
                    'qty'           => -$pItem['qty'], // ตัดสต็อกติดลบ
                    'cost_price'    => $pItem['cost_price'],
                    'ref_type'      => 'pos_order',
                    'ref_id'        => $orderId,
                    'notes'         => 'ขายหน้าร้าน บิลเลขที่: ' . $orderNo,
                    'created_by'    => $userId,
                    'created_at'    => $now,
                ]);

                // ถ้ามี Serial Number ผูกอยู่ ให้ปรับสถานะ Serial เป็น sold
                if (!empty($pItem['serial_id'])) {
                    $this->serialModel->update($pItem['serial_id'], [
                        'status'     => 'sold',
                        'notes'      => 'ขายหน้าร้าน บิลเลขที่: ' . $orderNo,
                        'updated_at' => $now,
                    ]);
                }
            }
            // สินค้าประเภทไม่ตัดสต็อก (track_stock = 0 เช่น ค่าแรง, บริการ) -> ข้ามการตัดสต็อก 100%
        }

        // 5. บันทึกบัญชีการเงิน (Financial Ledger) ตามรูปแบบการชำระเงิน
        if ($netAmount > 0) {
            if ($paymentMethod === 'cash') {
                // เงินสด: บันทึกรับเงินสดหน้าร้าน (จะอัปเดตยอดลิ้นชักใน cash_shifts อัตโนมัติ)
                $this->financeService->recordTransaction(
                    $tenantId,
                    $branchId,
                    'income',
                    'pos_sale',
                    $netAmount,
                    'cash',
                    'pos_order',
                    (int)$orderId,
                    'ขายหน้าร้าน บิลเลขที่: ' . $orderNo,
                    $userId
                );
            } elseif ($paymentMethod === 'bank_transfer') {
                // โอนเงินผ่านธนาคาร
                $this->financeService->recordTransaction(
                    $tenantId,
                    $branchId,
                    'income',
                    'pos_sale',
                    $netAmount,
                    'bank_transfer',
                    'pos_order',
                    (int)$orderId,
                    'ขายหน้าร้าน (โอนเงิน) บิลเลขที่: ' . $orderNo,
                    $userId
                );
            } elseif ($paymentMethod === 'store_credit' && $customerId) {
                // จ่ายด้วยวงเงินฝากสะสม (Store Credit) ใน Wallet
                $this->financeService->payWithStoreCredit(
                    $tenantId,
                    $branchId,
                    $customerId,
                    $netAmount,
                    'pos_order',
                    (int)$orderId,
                    'ชำระค่าสินค้าหน้าร้านด้วยวงเงินสะสม บิล: ' . $orderNo,
                    $userId
                );
            } elseif ($paymentMethod === 'debt' && $customerId) {
                // ซื้อของติดเงินก่อน (บันทึกเพิ่มยอดหนี้ค้างชำระใน Wallet)
                $walletModel = new WalletModel();
                $wallet = $walletModel->getOrCreateWallet($tenantId, 'customer', $customerId);
                $walletModel->adjustBalance(
                    (int)$wallet['id'],
                    'debt_increase',
                    $netAmount,
                    'pos_order',
                    (int)$orderId,
                    'ซื้อสินค้าหน้าร้านค้างชำระ บิล: ' . $orderNo,
                    $userId
                );
            }
        }

        return [
            'success'         => true,
            'order_id'        => (int)$orderId,
            'order_no'        => $orderNo,
            'total_amount'    => $totalAmount,
            'discount_amount' => $discountAmount,
            'net_amount'      => $netAmount,
            'payment_method'  => $paymentMethod,
            'items_count'     => count($processedItems),
        ];
    }
}
