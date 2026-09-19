<?php

namespace App\Models;

use CodeIgniter\Model;

class PosOrderModel extends Model
{
    protected $table            = 'pos_orders';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'tenant_id',
        'branch_id',
        'order_no',
        'customer_id',
        'cash_shift_id',
        'total_amount',
        'discount_amount',
        'net_amount',
        'payment_method',
        'payment_status',
        'notes',
        'created_by',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * ออกเลขที่บิลขายหน้าร้านอัตโนมัติ (เช่น POS2609-0001)
     */
    public function generateOrderNo(int $tenantId): string
    {
        $prefix = 'POS' . date('ym') . '-';
        $lastOrder = $this->where('tenant_id', $tenantId)
                          ->like('order_no', $prefix, 'after')
                          ->orderBy('id', 'DESC')
                          ->first();

        if ($lastOrder && preg_match('/-(\d+)$/', $lastOrder['order_no'], $matches)) {
            $nextNumber = intval($matches[1]) + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix . str_pad((string)$nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
