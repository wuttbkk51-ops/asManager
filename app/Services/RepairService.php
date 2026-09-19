<?php

namespace App\Services;

use App\Models\RepairJobModel;
use App\Models\RepairDepositModel;
use App\Models\WalletModel;

class RepairService
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    /**
     * ออกใบรับเงินมัดจำงานซ่อม (Deposit Receipt)
     */
    public function issueDeposit(
        int $jobId,
        float $amount,
        string $paymentMethod = 'cash',
        ?string $notes = null,
        ?int $userId = null
    ): array {
        $jobModel = new RepairJobModel();
        $job = $jobModel->find($jobId);
        if (!$job) {
            return ['success' => false, 'message' => 'Job not found'];
        }

        $depositModel = new RepairDepositModel();
        $depositNo = $depositModel->generateDepositNo((int)$job['tenant_id']);
        $now = date('Y-m-d H:i:s');

        // 1. บันทึกลงตาราง repair_deposits
        $depositId = $depositModel->insert([
            'tenant_id'      => $job['tenant_id'],
            'repair_job_id'  => $jobId,
            'deposit_no'     => $depositNo,
            'amount'         => $amount,
            'payment_method' => $paymentMethod,
            'receipt_no'     => 'REC-' . $depositNo,
            'notes'          => $notes,
            'received_by'    => $userId,
            'received_at'    => $now,
            'created_at'     => $now,
            'updated_at'     => $now,
        ]);

        // 2. อัปเดตยอดมัดจำและเลขที่มัดจำในใบแจ้งซ่อม repair_jobs
        $currentDeposit = (float)$job['deposit_amount'];
        $newDeposit     = $currentDeposit + $amount;
        $paidAmount     = (float)$job['paid_amount'] + $amount;

        $jobModel->update($jobId, [
            'deposit_no'     => $depositNo,
            'deposit_amount' => $newDeposit,
            'paid_amount'    => $paidAmount,
            'payment_status' => 'partial',
        ]);

        // 3. บันทึกเงินเข้าใน Financial Transactions
        $financeService = new FinanceService();
        $finTxnId = $financeService->recordTransaction(
            (int)$job['tenant_id'],
            (int)$job['branch_id'],
            'income',
            'repair_deposit',
            $amount,
            $paymentMethod,
            'repair_job',
            $jobId,
            "รับเงินมัดจำใบแจ้งซ่อม {$job['job_no']} (ใบมัดจำ: {$depositNo})",
            $userId
        );

        return [
            'success'        => true,
            'deposit_id'     => $depositId,
            'deposit_no'     => $depositNo,
            'amount'         => $amount,
            'total_deposit'  => $newDeposit,
            'financial_txn'  => $finTxnId,
        ];
    }

    /**
     * คิดเงินปิดบิลซ่อม (Final Checkout & Billing)
     * ดึงอ้างอิงเงินมัดจำเดิมมาหักลบ และออกใบเสร็จฉบับสมบูรณ์ (Final Receipt)
     */
    public function checkoutFinalBill(
        int $jobId,
        string $paymentMethod = 'cash',
        ?string $notes = null,
        ?int $userId = null,
        string $excessHandling = 'refund_cash' // refund_cash หรือ refund_store_credit
    ): array {
        $jobModel = new RepairJobModel();
        $job = $jobModel->find($jobId);
        if (!$job) {
            return ['success' => false, 'message' => 'Job not found'];
        }

        // คำนวณยอดเงินรวมล่าสุดของบิล
        $totals = $jobModel->recalculateTotals($jobId);
        $job = $jobModel->find($jobId); // รีเฟรชข้อมูล

        $netTotal      = (float)$job['net_total'];
        $depositAmount = (float)$job['deposit_amount'];
        $depositNo     = $job['deposit_no'] ?? null;

        // คำนวณยอดที่ต้องชำระเพิ่ม (Net Payable)
        $netPayable   = max(0.00, $netTotal - $depositAmount);
        $excessAmount = max(0.00, $depositAmount - $netTotal); // กรณีมัดจำเกินยอดซ่อมจริง

        $now = date('Y-m-d H:i:s');
        $finalReceiptNo = 'RC' . date('ym') . '-' . str_pad((string)mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

        $financeService = new FinanceService();
        $remainingTxnId = null;
        $refundTxnId    = null;

        // 1. ถ้ามียอดต้องชำระเพิ่ม (netPayable > 0)
        if ($netPayable > 0) {
            $remainingTxnId = $financeService->recordTransaction(
                (int)$job['tenant_id'],
                (int)$job['branch_id'],
                'income',
                'repair_fee',
                $netPayable,
                $paymentMethod,
                'repair_job',
                $jobId,
                "ชำระเงินส่วนที่เหลือหลังหักมัดจำ {$depositNo} สำหรับงานซ่อม {$job['job_no']}",
                $userId
            );
        }

        // 2. ถ้ามัดจำเกินยอดซ่อมจริง (excessAmount > 0 เช่น ไม่ต้องเปลี่ยนอะไหล่บางชิ้น)
        if ($excessAmount > 0) {
            if ($excessHandling === 'refund_store_credit' && !empty($job['customer_id'])) {
                // คืนเงินส่วนต่างเข้ากระเป๋าเงินเป็น Store Credit
                $financeService->refundToStoreCredit(
                    (int)$job['tenant_id'],
                    (int)$job['customer_id'],
                    $excessAmount,
                    'repair_deposit_refund',
                    $jobId,
                    "คืนเงินมัดจำส่วนเกินจากงานซ่อม {$job['job_no']} เข้ากระเป๋าเงิน Store Credit",
                    $userId
                );
            } else {
                // คืนเงินสดให้ลูกค้า
                $refundTxnId = $financeService->recordTransaction(
                    (int)$job['tenant_id'],
                    (int)$job['branch_id'],
                    'expense',
                    'repair_deposit_refund',
                    $excessAmount,
                    'cash',
                    'repair_job',
                    $jobId,
                    "คืนเงินมัดจำส่วนเกินให้ลูกค้าสำหรับงานซ่อม {$job['job_no']}",
                    $userId
                );
            }
        }

        // 3. อัปเดตใบแจ้งซ่อมเป็นสถานะชำระครบถ้วน (paid) และออกใบเสร็จสมบูรณ์
        $jobModel->update($jobId, [
            'net_payable'      => $netPayable,
            'paid_amount'      => $netTotal,
            'final_receipt_no' => $finalReceiptNo,
            'payment_status'   => 'paid',
            'status'           => 'delivered',
            'delivered_at'     => $now,
        ]);

        return [
            'success'          => true,
            'job_no'           => $job['job_no'],
            'final_receipt_no' => $finalReceiptNo,
            'deposit_no'       => $depositNo,
            'deposit_amount'   => $depositAmount,
            'total_amount'     => $netTotal,
            'net_payable'      => $netPayable,
            'excess_amount'    => $excessAmount,
            'remaining_txn_id' => $remainingTxnId,
            'status'           => 'delivered',
            'payment_status'   => 'paid',
        ];
    }
}
