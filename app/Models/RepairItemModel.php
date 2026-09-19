<?php

namespace App\Models;

use CodeIgniter\Model;

class RepairItemModel extends Model
{
    protected $table            = 'repair_items';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'repair_job_id',
        'item_type', // part, service
        'product_id',
        'serial_id',
        'item_name',
        'qty',
        'cost_price',
        'unit_price',
        'total_price',
        'status', // requested, approved, used, returned
        'approved_by',
        'approved_at',
        'returned_at',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * ช่างขอเบิกอะไหล่ (หรือตัดสต็อกทันทีขึ้นอยู่กับการตั้งค่าร้าน)
     */
    public function addPart(int $jobId, array $data, bool $requireApproval = true, ?int $userId = null): int|false
    {
        $now = date('Y-m-d H:i:s');
        $status = $requireApproval ? 'requested' : 'approved';

        $itemId = $this->insert([
            'repair_job_id' => $jobId,
            'item_type'     => $data['item_type'] ?? 'part',
            'product_id'    => $data['product_id'] ?? null,
            'serial_id'     => $data['serial_id'] ?? null,
            'item_name'     => $data['item_name'],
            'qty'           => $data['qty'] ?? 1.00,
            'cost_price'    => $data['cost_price'] ?? 0.00,
            'unit_price'    => $data['unit_price'] ?? 0.00,
            'total_price'   => ($data['qty'] ?? 1.00) * ($data['unit_price'] ?? 0.00),
            'status'        => $status,
            'approved_by'   => $requireApproval ? null : $userId,
            'approved_at'   => $requireApproval ? null : $now,
            'created_at'    => $now,
            'updated_at'    => $now,
        ]);

        if ($itemId && !$requireApproval && !empty($data['product_id'])) {
            // ตัดสต็อกทันทีถ้าโหมดร้านไม่ต้องรอ Approve
            $this->deductStockForPart($jobId, (int)$itemId, $data, $userId);
        }

        (new RepairJobModel())->recalculateTotals($jobId);
        return $itemId;
    }

    /**
     * อนุมัติการเบิกอะไหล่ (สำหรับโหมดร้านที่เปิด Approval)
     */
    public function approvePart(int $itemId, int $userId): bool
    {
        $item = $this->find($itemId);
        if (!$item || $item['status'] !== 'requested') {
            return false;
        }

        $now = date('Y-m-d H:i:s');
        $this->update($itemId, [
            'status'      => 'approved',
            'approved_by' => $userId,
            'approved_at' => $now,
        ]);

        // บันทึกตัดสต็อก
        $this->deductStockForPart((int)$item['repair_job_id'], $itemId, $item, $userId);

        (new RepairJobModel())->recalculateTotals((int)$item['repair_job_id']);
        return true;
    }

    /**
     * คืนอะไหล่กลับเข้าคลังสินค้า (กรณีเบิกผิดรุ่น หรือเปลี่ยนใจไม่ใช้)
     * ยอดสต็อกจะคืนกลับเข้าคลัง และยอดเงินในบิลซ่อมจะถูกหักออกอัตโนมัติ
     */
    public function returnPart(int $itemId, ?string $reason = null, ?int $userId = null): bool
    {
        $item = $this->find($itemId);
        if (!$item || $item['status'] === 'returned') {
            return false;
        }

        $now = date('Y-m-d H:i:s');
        $job = (new RepairJobModel())->find($item['repair_job_id']);

        // 1. อัปเดตสถานะไอเทมเป็น returned
        $this->update($itemId, [
            'status'      => 'returned',
            'returned_at' => $now,
        ]);

        // 2. ถ้าเป็นอะไหล่ที่เคยตัดสต็อกไปแล้ว (approved หรือ used) ให้นำคืนเข้าสต็อก
        if (!empty($item['product_id']) && in_array($item['status'], ['approved', 'used'])) {
            // คืนจำนวน Qty เข้าคลังหน้าร้าน (หรือคลังเดิม)
            $this->db->table('stock_transactions')->insert([
                'tenant_id'     => $job['tenant_id'],
                'branch_id'     => $job['branch_id'],
                'warehouse_id'  => 1, // default main warehouse
                'product_id'    => $item['product_id'],
                'serial_id'     => $item['serial_id'],
                'movement_type' => 'repair_returned',
                'qty'           => (float)$item['qty'], // คืนยอดสต็อกเป็นบวก
                'cost_price'    => (float)$item['cost_price'],
                'ref_type'      => 'repair_job',
                'ref_id'        => $job['id'],
                'notes'         => 'คืนอะไหล่จากงานซ่อม ' . $job['job_no'] . ($reason ? ' (' . $reason . ')' : ''),
                'created_by'    => $userId,
                'created_at'    => $now,
            ]);

            // คืนสถานะ Serial Number เป็น in_stock
            if (!empty($item['serial_id'])) {
                (new ProductSerialModel())->setStatus((int)$item['serial_id'], 'in_stock', 'คืนเข้าคลังจากงานซ่อม ' . $job['job_no']);
            }
        }

        // 3. คำนวณยอดเงินรวมของบิลงานซ่อมใหม่ (ยอดอะไหล่ที่คืนจะไม่ถูกคิดเงิน)
        (new RepairJobModel())->recalculateTotals((int)$item['repair_job_id']);
        return true;
    }

    private function deductStockForPart(int $jobId, int $itemId, array $item, ?int $userId): void
    {
        $job = (new RepairJobModel())->find($jobId);
        $now = date('Y-m-d H:i:s');

        // ตัดสต็อก Qty เป็นลบ
        $this->db->table('stock_transactions')->insert([
            'tenant_id'     => $job['tenant_id'],
            'branch_id'     => $job['branch_id'],
            'warehouse_id'  => 1, // default storefront warehouse
            'product_id'    => $item['product_id'],
            'serial_id'     => $item['serial_id'] ?? null,
            'movement_type' => 'repair_used',
            'qty'           => -(float)$item['qty'], // ตัดสต็อกติดลบ
            'cost_price'    => (float)$item['cost_price'],
            'ref_type'      => 'repair_job',
            'ref_id'        => $jobId,
            'notes'         => 'เบิกอะไหล่ใช้ในงานซ่อม ' . $job['job_no'],
            'created_by'    => $userId,
            'created_at'    => $now,
        ]);

        // อัปเดตสถานะ Serial เป็น used_in_repair
        if (!empty($item['serial_id'])) {
            (new ProductSerialModel())->setStatus((int)$item['serial_id'], 'used_in_repair', 'ใช้ในงานซ่อม ' . $job['job_no']);
        }
    }
}
