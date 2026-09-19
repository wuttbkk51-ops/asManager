<?php

namespace App\Models;

use CodeIgniter\Model;

class StockTransferModel extends Model
{
    protected $table            = 'stock_transfers';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'tenant_id',
        'transfer_no',
        'from_branch_id',
        'from_warehouse_id',
        'to_branch_id',
        'to_warehouse_id',
        'status', // requested, in_transit, completed, cancelled
        'notes',
        'dispatched_by',
        'dispatched_at',
        'received_by',
        'received_at',
        'created_by',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * ออกเลขที่ใบโอนย้ายสินค้าอัตโนมัติ (เช่น TR2609-0001)
     */
    public function generateTransferNo(int $tenantId): string
    {
        $prefix = 'TR' . date('ym') . '-';
        $lastTransfer = $this->where('tenant_id', $tenantId)
                             ->like('transfer_no', $prefix, 'after')
                             ->orderBy('id', 'DESC')
                             ->first();

        if ($lastTransfer && preg_match('/-(\d+)$/', $lastTransfer['transfer_no'], $matches)) {
            $nextNumber = intval($matches[1]) + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix . str_pad((string)$nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
