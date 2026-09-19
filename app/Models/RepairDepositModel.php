<?php

namespace App\Models;

use CodeIgniter\Model;

class RepairDepositModel extends Model
{
    protected $table            = 'repair_deposits';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'tenant_id',
        'repair_job_id',
        'deposit_no',
        'amount',
        'payment_method',
        'receipt_no',
        'notes',
        'received_by',
        'received_at',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * รันเลขที่ใบรับเงินมัดจำอัตโนมัติ (เช่น DP2609-0001)
     */
    public function generateDepositNo(int $tenantId): string
    {
        $prefix = 'DP' . date('ym') . '-';
        $last = $this->where('tenant_id', $tenantId)
                     ->like('deposit_no', $prefix, 'after')
                     ->orderBy('id', 'DESC')
                     ->first();

        $seq = 1;
        if ($last) {
            $lastSeq = (int)substr($last['deposit_no'], -4);
            $seq = $lastSeq + 1;
        }

        return $prefix . str_pad((string)$seq, 4, '0', STR_PAD_LEFT);
    }
}
