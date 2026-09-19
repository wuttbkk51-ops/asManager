<?php

namespace App\Models;

use CodeIgniter\Model;

class WalletModel extends Model
{
    protected $table            = 'wallets';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'tenant_id',
        'party_type', // customer, supplier
        'party_id',
        'credit_balance',
        'debt_balance',
        'credit_limit',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * ดึงหรือสร้าง Wallet สำหรับลูกค้าหรือ Supplier
     */
    public function getOrCreateWallet(int $tenantId, string $partyType, int $partyId, float $defaultCreditLimit = 0.00): array
    {
        $wallet = $this->where('tenant_id', $tenantId)
                       ->where('party_type', $partyType)
                       ->where('party_id', $partyId)
                       ->first();

        if (!$wallet) {
            $now = date('Y-m-d H:i:s');
            $id = $this->insert([
                'tenant_id'      => $tenantId,
                'party_type'     => $partyType,
                'party_id'       => $partyId,
                'credit_balance' => 0.00,
                'debt_balance'   => 0.00,
                'credit_limit'   => $defaultCreditLimit,
                'created_at'     => $now,
                'updated_at'     => $now,
            ]);
            $wallet = $this->find($id);
        }

        return $wallet;
    }

    /**
     * ปรับปรุงยอดวงเงินฝากสะสม (Store Credit) หรือหนี้ค้างชำระ (Debt) พร้อมบันทึก Transaction
     */
    public function adjustBalance(int $walletId, string $type, float $amount, ?string $refType = null, ?int $refId = null, ?string $notes = null, ?int $userId = null): bool
    {
        $wallet = $this->find($walletId);
        if (!$wallet) {
            return false;
        }

        $creditBalance = (float)$wallet['credit_balance'];
        $debtBalance   = (float)$wallet['debt_balance'];
        $balanceAfter  = 0.00;

        switch ($type) {
            case 'credit_deposit': // เพิ่มยอดเงินฝากสะสม (เช่น คืนสินค้าแต่ไม่รับเงินสด)
                $creditBalance += $amount;
                $balanceAfter = $creditBalance;
                break;

            case 'credit_use': // นำเงินฝากสะสมมาจ่ายค่าสินค้า/ค่าซ่อม
                if ($creditBalance < $amount) {
                    return false; // ยอดเงินฝากไม่พอ
                }
                $creditBalance -= $amount;
                $balanceAfter = $creditBalance;
                break;

            case 'debt_increase': // ติดเงินก่อน (เพิ่มหนี้)
                $debtBalance += $amount;
                $balanceAfter = $debtBalance;
                break;

            case 'debt_payment': // ชำระหนี้ (ลดหนี้)
                $debtBalance = max(0.00, $debtBalance - $amount);
                $balanceAfter = $debtBalance;
                break;

            default:
                return false;
        }

        $this->update($walletId, [
            'credit_balance' => $creditBalance,
            'debt_balance'   => $debtBalance,
        ]);

        $this->db->table('wallet_transactions')->insert([
            'wallet_id'     => $walletId,
            'tenant_id'     => $wallet['tenant_id'],
            'type'          => $type,
            'amount'        => $amount,
            'balance_after' => $balanceAfter,
            'ref_type'      => $refType,
            'ref_id'        => $refId,
            'notes'         => $notes,
            'created_by'    => $userId,
            'created_at'    => date('Y-m-d H:i:s'),
        ]);

        return true;
    }
}
