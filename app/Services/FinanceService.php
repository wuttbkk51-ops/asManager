<?php

namespace App\Services;

use App\Models\WalletModel;

class FinanceService
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    /**
     * บันทึกธุรกรรมการเงินทุกการเคลื่อนไหวของร้าน (Unified Financial Ledger)
     */
    public function recordTransaction(
        int $tenantId,
        int $branchId,
        string $type, // income, expense
        string $category, // pos_sale, repair_fee, repair_deposit, shop_expense, supplier_payment
        float $amount,
        string $paymentMethod = 'cash', // cash, bank_transfer, credit_card, store_credit, debt
        ?string $refType = null,
        ?int $refId = null,
        ?string $notes = null,
        ?int $userId = null
    ): int {
        $now = date('Y-m-d H:i:s');

        $this->db->table('financial_transactions')->insert([
            'tenant_id'      => $tenantId,
            'branch_id'      => $branchId,
            'type'           => $type,
            'category'       => $category,
            'payment_method' => $paymentMethod,
            'amount'         => $amount,
            'ref_type'       => $refType,
            'ref_id'         => $refId,
            'notes'          => $notes,
            'created_by'     => $userId,
            'created_at'     => $now,
        ]);

        $txnId = $this->db->insertID();

        // หากเป็นการจ่ายเงินสดหน้าร้าน ให้อัปเดตยอดในกะที่เปิดอยู่ (Cash Shift) อัตโนมัติ
        if ($paymentMethod === 'cash') {
            $this->updateActiveCashShift($tenantId, $branchId, $type, $amount);
        }

        return $txnId;
    }

    /**
     * คืนค่าสินค้า/บริการ แต่ไม่คืนเงินสด โดยโอนเข้าเป็นวงเงินฝากสะสม (Store Credit) ไว้ซื้อรอบต่อไป
     */
    public function refundToStoreCredit(int $tenantId, int $customerId, float $amount, ?string $refType = null, ?int $refId = null, ?string $notes = null, ?int $userId = null): bool
    {
        $walletModel = new WalletModel();
        $wallet = $walletModel->getOrCreateWallet($tenantId, 'customer', $customerId);

        return $walletModel->adjustBalance(
            (int)$wallet['id'],
            'credit_deposit',
            $amount,
            $refType,
            $refId,
            $notes ?: 'คืนสินค้า/บริการเข้าเป็นวงเงินฝากสะสม (Store Credit)',
            $userId
        );
    }

    /**
     * ลูกค้าชำระเงินโดยใช้วงเงินฝากสะสม (Store Credit)
     */
    public function payWithStoreCredit(int $tenantId, int $branchId, int $customerId, float $amount, ?string $refType = null, ?int $refId = null, ?string $notes = null, ?int $userId = null): bool
    {
        $walletModel = new WalletModel();
        $wallet = $walletModel->getOrCreateWallet($tenantId, 'customer', $customerId);

        if ((float)$wallet['credit_balance'] < $amount) {
            return false; // ยอดเงินฝากไม่พอ
        }

        // 1. ตัดเงินออกจาก Wallet
        $deducted = $walletModel->adjustBalance(
            (int)$wallet['id'],
            'credit_use',
            $amount,
            $refType,
            $refId,
            $notes ?: 'ชำระเงินด้วยวงเงินฝากสะสม (Store Credit)',
            $userId
        );

        if ($deducted) {
            // 2. บันทึก Financial Transaction รับชำระด้วย store_credit
            $this->recordTransaction(
                $tenantId,
                $branchId,
                'income',
                $refType === 'repair_job' ? 'repair_fee' : 'pos_sale',
                $amount,
                'store_credit',
                $refType,
                $refId,
                $notes ?: 'ชำระด้วย Store Credit ของลูกค้า ID: ' . $customerId,
                $userId
            );
            return true;
        }

        return false;
    }

    /**
     * เปิดกะเงินสดหน้าร้าน (Open Cash Shift)
     */
    public function openShift(int $tenantId, int $branchId, int $userId, float $openingCash, ?string $notes = null): int|false
    {
        // ตรวจสอบว่ามีกะที่เปิดค้างอยู่หรือไม่
        $existing = $this->db->table('cash_shifts')
                             ->where('tenant_id', $tenantId)
                             ->where('branch_id', $branchId)
                             ->where('status', 'open')
                             ->get()
                             ->getRowArray();

        if ($existing) {
            return (int)$existing['id']; // คืนค่ากะเดิมที่ยังเปิดอยู่
        }

        $now = date('Y-m-d H:i:s');
        $this->db->table('cash_shifts')->insert([
            'tenant_id'     => $tenantId,
            'branch_id'     => $branchId,
            'user_id'       => $userId,
            'opened_at'     => $now,
            'closed_at'     => null,
            'opening_cash'  => $openingCash,
            'cash_sales'    => 0.00,
            'cash_expenses' => 0.00,
            'cash_drops'    => 0.00,
            'expected_cash' => $openingCash,
            'status'        => 'open',
            'notes'         => $notes,
        ]);

        return $this->db->insertID();
    }

    /**
     * ปิดกะเงินสดหน้าร้าน (Close Cash Shift) พร้อมคำนวณส่วนต่าง ขาด/เกิน
     */
    public function closeShift(int $shiftId, float $countedCash, ?string $notes = null): array|false
    {
        $shift = $this->db->table('cash_shifts')->where('id', $shiftId)->get()->getRowArray();
        if (!$shift || $shift['status'] !== 'open') {
            return false;
        }

        $expectedCash = (float)$shift['expected_cash'];
        $difference   = $countedCash - $expectedCash;
        $now = date('Y-m-d H:i:s');

        $this->db->table('cash_shifts')->where('id', $shiftId)->update([
            'closed_at'            => $now,
            'closing_cash_counted' => $countedCash,
            'difference'           => $difference,
            'status'               => 'closed',
            'notes'                => $notes ?: ($shift['notes'] ?? ''),
        ]);

        return [
            'shift_id'      => $shiftId,
            'opening_cash'  => (float)$shift['opening_cash'],
            'cash_sales'    => (float)$shift['cash_sales'],
            'cash_expenses' => (float)$shift['cash_expenses'],
            'expected_cash' => $expectedCash,
            'counted_cash'  => $countedCash,
            'difference'    => $difference, // บวก = เงินเกิน, ลบ = เงินขาด
        ];
    }

    private function updateActiveCashShift(int $tenantId, int $branchId, string $type, float $amount): void
    {
        $shift = $this->db->table('cash_shifts')
                          ->where('tenant_id', $tenantId)
                          ->where('branch_id', $branchId)
                          ->where('status', 'open')
                          ->get()
                          ->getRowArray();

        if ($shift) {
            if ($type === 'income') {
                $this->db->query("UPDATE cash_shifts SET cash_sales = cash_sales + ?, expected_cash = expected_cash + ? WHERE id = ?", [
                    $amount, $amount, $shift['id']
                ]);
            } elseif ($type === 'expense') {
                $this->db->query("UPDATE cash_shifts SET cash_expenses = cash_expenses + ?, expected_cash = expected_cash - ? WHERE id = ?", [
                    $amount, $amount, $shift['id']
                ]);
            }
        }
    }
}
