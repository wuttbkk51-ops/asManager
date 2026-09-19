<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductCostLogModel extends Model
{
    protected $table            = 'product_cost_logs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'tenant_id',
        'product_id',
        'old_cost',
        'new_cost',
        'received_qty',
        'received_cost',
        'costing_method',
        'ref_type',
        'ref_id',
        'notes',
        'created_by',
        'created_at',
    ];

    protected $useTimestamps = false;

    /**
     * ดึงประวัติการปรับปรุงต้นทุนของสินค้า
     */
    public function getProductHistory(int $tenantId, int $productId, int $limit = 20): array
    {
        return $this->where('tenant_id', $tenantId)
                    ->where('product_id', $productId)
                    ->orderBy('id', 'DESC')
                    ->findAll($limit);
    }
}
