<?php

namespace App\Models;

use CodeIgniter\Model;

class StockAdjustmentModel extends Model
{
    protected $table            = 'stock_adjustments';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'tenant_id',
        'branch_id',
        'warehouse_id',
        'adjustment_no',
        'type',
        'status',
        'total_loss_value',
        'total_gain_value',
        'notes',
        'created_by',
        'approved_by',
        'approved_at',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * ออกเลขที่เอกสารปรับปรุงสต็อก เช่น ADJ2609-0001
     */
    public function generateAdjustmentNo(int $tenantId): string
    {
        $prefix = 'ADJ' . date('ym') . '-';
        $count = $this->where('tenant_id', $tenantId)
                      ->like('adjustment_no', $prefix, 'after')
                      ->countAllResults();

        return $prefix . str_pad((string)($count + 1), 4, '0', STR_PAD_LEFT);
    }
}
