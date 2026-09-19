<?php

namespace App\Models;

use CodeIgniter\Model;

class RepairJobModel extends Model
{
    protected $table            = 'repair_jobs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'tenant_id',
        'branch_id',
        'job_no',
        'tracking_token',
        'customer_id',
        'customer_name',
        'customer_phone',
        'device_type',
        'brand',
        'model',
        'serial_imei',
        'color',
        'passcode',
        'accessories',
        'problem_reported',
        'technician_notes',
        'status',
        'assigned_to',
        'estimated_price',
        'deposit_amount',
        'deposit_no',
        'total_parts_price',
        'total_labor_price',
        'discount_amount',
        'net_total',
        'net_payable',
        'paid_amount',
        'final_receipt_no',
        'payment_status',
        'warranty_days',
        'delivered_at',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * รันรหัสใบแจ้งซ่อมอัตโนมัติ (เช่น RP2609-0001)
     */
    public function generateJobNo(int $tenantId): string
    {
        $prefix = 'RP' . date('ym') . '-';
        $last = $this->where('tenant_id', $tenantId)
                     ->like('job_no', $prefix, 'after')
                     ->orderBy('id', 'DESC')
                     ->first();

        $seq = 1;
        if ($last) {
            $lastSeq = (int)substr($last['job_no'], -4);
            $seq = $lastSeq + 1;
        }

        return $prefix . str_pad((string)$seq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * เปลี่ยนสถานะงานซ่อมพร้อมบันทึกประวัติ (Audit & Reject Trail)
     * มีความยืดหยุ่นสูง: เปลี่ยนไปสถานะใดก็ได้ เช่น จาก repaired -> rejected
     */
    public function changeStatus(int $jobId, string $newStatus, ?string $notes = null, ?int $userId = null): bool
    {
        $job = $this->find($jobId);
        if (!$job) {
            return false;
        }

        $oldStatus = $job['status'];
        $now = date('Y-m-d H:i:s');

        $updateData = ['status' => $newStatus];
        if ($newStatus === 'delivered' && empty($job['delivered_at'])) {
            $updateData['delivered_at'] = $now;
        }

        $this->update($jobId, $updateData);

        // บันทึก Log การเปลี่ยน State
        $this->db->table('repair_status_logs')->insert([
            'repair_job_id' => $jobId,
            'from_status'   => $oldStatus,
            'to_status'     => $newStatus,
            'notes'         => $notes,
            'created_by'    => $userId,
            'created_at'    => $now,
        ]);

        return true;
    }

    /**
     * คำนวณยอดเงินรวมของบิลงานซ่อมใหม่ (ค่าอะไหล่ + ค่าแรง - ส่วนลด)
     */
    public function recalculateTotals(int $jobId): array
    {
        $items = $this->db->table('repair_items')
                          ->where('repair_job_id', $jobId)
                          ->whereIn('status', ['approved', 'used']) // เฉพาะอะไหล่และบริการที่อนุมัติแล้ว
                          ->get()
                          ->getResultArray();

        $partsTotal = 0.00;
        $laborTotal = 0.00;

        foreach ($items as $item) {
            if ($item['item_type'] === 'part') {
                $partsTotal += (float)$item['total_price'];
            } else {
                $laborTotal += (float)$item['total_price'];
            }
        }

        $job = $this->find($jobId);
        $discount = (float)($job['discount_amount'] ?? 0.00);
        $netTotal = max(0.00, ($partsTotal + $laborTotal) - $discount);
        $paid = (float)($job['paid_amount'] ?? 0.00);

        $paymentStatus = 'unpaid';
        if ($paid >= $netTotal && $netTotal > 0) {
            $paymentStatus = 'paid';
        } elseif ($paid > 0) {
            $paymentStatus = 'partial';
        }

        $this->update($jobId, [
            'total_parts_price' => $partsTotal,
            'total_labor_price' => $laborTotal,
            'net_total'         => $netTotal,
            'payment_status'    => $paymentStatus,
        ]);

        return [
            'parts_total'    => $partsTotal,
            'labor_total'    => $laborTotal,
            'net_total'      => $netTotal,
            'payment_status' => $paymentStatus,
        ];
    }
}
