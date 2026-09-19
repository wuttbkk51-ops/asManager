<?php

namespace App\Services;

use App\Models\StockTransferModel;
use App\Models\StockTransferItemModel;
use App\Models\ProductModel;
use App\Models\ProductSerialModel;

class StockTransferService
{
    protected $db;
    protected $transferModel;
    protected $itemModel;
    protected $productModel;
    protected $serialModel;

    public function __construct()
    {
        $this->db            = \Config\Database::connect();
        $this->transferModel = new StockTransferModel();
        $this->itemModel     = new StockTransferItemModel();
        $this->productModel  = new ProductModel();
        $this->serialModel   = new ProductSerialModel();
    }

    /**
     * สร้างใบขอโอนย้ายสินค้าข้ามสาขาหรือข้ามคลัง (Request Transfer)
     */
    public function createTransfer(
        int $tenantId,
        int $fromBranchId,
        int $fromWarehouseId,
        int $toBranchId,
        int $toWarehouseId,
        array $items, // [['product_id' => 1, 'qty' => 5, 'serial_id' => null]]
        ?string $notes = null,
        ?int $userId = null
    ): array {
        $now = date('Y-m-d H:i:s');
        if (empty($items)) {
            return ['success' => false, 'message' => 'No items specified for transfer'];
        }

        if ($fromWarehouseId === $toWarehouseId) {
            return ['success' => false, 'message' => 'Cannot transfer to the same warehouse'];
        }

        $transferNo = $this->transferModel->generateTransferNo($tenantId);
        $transferId = $this->transferModel->insert([
            'tenant_id'         => $tenantId,
            'transfer_no'       => $transferNo,
            'from_branch_id'    => $fromBranchId,
            'from_warehouse_id' => $fromWarehouseId,
            'to_branch_id'      => $toBranchId,
            'to_warehouse_id'   => $toWarehouseId,
            'status'            => 'requested',
            'notes'             => $notes,
            'created_by'        => $userId,
            'created_at'        => $now,
            'updated_at'        => $now,
        ]);

        foreach ($items as $item) {
            $this->itemModel->insert([
                'stock_transfer_id' => $transferId,
                'product_id'        => (int)$item['product_id'],
                'serial_id'         => !empty($item['serial_id']) ? (int)$item['serial_id'] : null,
                'qty'               => (float)($item['qty'] ?? 1.00),
                'created_at'        => $now,
            ]);
        }

        return [
            'success'     => true,
            'transfer_id' => (int)$transferId,
            'transfer_no' => $transferNo,
            'status'      => 'requested',
        ];
    }

    /**
     * ส่งสินค้าออกจากคลังต้นทาง (Dispatch Transfer: ตัดสต็อกต้นทาง -> สถานะ in_transit)
     */
    public function dispatchTransfer(int $tenantId, int $transferId, ?int $userId = null): array
    {
        $now = date('Y-m-d H:i:s');
        $transfer = $this->transferModel->where('id', $transferId)->where('tenant_id', $tenantId)->first();
        if (!$transfer || $transfer['status'] !== 'requested') {
            return ['success' => false, 'message' => 'Invalid transfer or already dispatched'];
        }

        $items = $this->itemModel->where('stock_transfer_id', $transferId)->findAll();

        foreach ($items as $item) {
            $product = $this->productModel->find($item['product_id']);
            $costPrice = $product ? (float)$product['cost_price'] : 0.00;

            // ตัดสต็อกจากคลังต้นทาง (movement_type = transfer_out)
            $this->db->table('stock_transactions')->insert([
                'tenant_id'     => $tenantId,
                'branch_id'     => $transfer['from_branch_id'],
                'warehouse_id'  => $transfer['from_warehouse_id'],
                'product_id'    => $item['product_id'],
                'serial_id'     => $item['serial_id'],
                'movement_type' => 'transfer_out',
                'qty'           => -$item['qty'],
                'cost_price'    => $costPrice,
                'ref_type'      => 'stock_transfer',
                'ref_id'        => $transferId,
                'notes'         => 'โอนย้ายออกตามใบโอน: ' . $transfer['transfer_no'],
                'created_by'    => $userId,
                'created_at'    => $now,
            ]);

            // ปรับสถานะ Serial เป็น in_transit
            if (!empty($item['serial_id'])) {
                $this->serialModel->update($item['serial_id'], [
                    'status'     => 'in_transit',
                    'notes'      => 'อยู่ระหว่างโอนย้าย ใบโอน: ' . $transfer['transfer_no'],
                    'updated_at' => $now,
                ]);
            }
        }

        $this->transferModel->update($transferId, [
            'status'        => 'in_transit',
            'dispatched_by' => $userId,
            'dispatched_at' => $now,
            'updated_at'    => $now,
        ]);

        return [
            'success'     => true,
            'transfer_id' => $transferId,
            'transfer_no' => $transfer['transfer_no'],
            'status'      => 'in_transit',
        ];
    }

    /**
     * ตรวจรับสินค้าเข้าคลังปลายทาง (Receive Transfer: เพิ่มสต็อกปลายทาง -> สถานะ completed)
     */
    public function receiveTransfer(int $tenantId, int $transferId, ?int $userId = null): array
    {
        $now = date('Y-m-d H:i:s');
        $transfer = $this->transferModel->where('id', $transferId)->where('tenant_id', $tenantId)->first();
        if (!$transfer || $transfer['status'] !== 'in_transit') {
            return ['success' => false, 'message' => 'Transfer must be in_transit before receiving'];
        }

        $items = $this->itemModel->where('stock_transfer_id', $transferId)->findAll();

        foreach ($items as $item) {
            $product = $this->productModel->find($item['product_id']);
            $costPrice = $product ? (float)$product['cost_price'] : 0.00;

            // เพิ่มสต็อกเข้าคลังปลายทาง (movement_type = transfer_in)
            $this->db->table('stock_transactions')->insert([
                'tenant_id'     => $tenantId,
                'branch_id'     => $transfer['to_branch_id'],
                'warehouse_id'  => $transfer['to_warehouse_id'],
                'product_id'    => $item['product_id'],
                'serial_id'     => $item['serial_id'],
                'movement_type' => 'transfer_in',
                'qty'           => $item['qty'],
                'cost_price'    => $costPrice,
                'ref_type'      => 'stock_transfer',
                'ref_id'        => $transferId,
                'notes'         => 'ตรวจรับสินค้าเข้าตามใบโอน: ' . $transfer['transfer_no'],
                'created_by'    => $userId,
                'created_at'    => $now,
            ]);

            // อัปเดตตำแหน่งสาขาและคลังของ Serial ให้เป็นของปลายทาง และเปลี่ยนสถานะกลับเป็น in_stock
            if (!empty($item['serial_id'])) {
                $this->serialModel->update($item['serial_id'], [
                    'branch_id'    => $transfer['to_branch_id'],
                    'warehouse_id' => $transfer['to_warehouse_id'],
                    'status'       => 'in_stock',
                    'notes'        => 'รับเข้าคลังปลายทางเรียบร้อย ใบโอน: ' . $transfer['transfer_no'],
                    'updated_at'   => $now,
                ]);
            }
        }

        $this->transferModel->update($transferId, [
            'status'      => 'completed',
            'received_by' => $userId,
            'received_at' => $now,
            'updated_at'  => $now,
        ]);

        return [
            'success'     => true,
            'transfer_id' => $transferId,
            'transfer_no' => $transfer['transfer_no'],
            'status'      => 'completed',
        ];
    }
}
