<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductModel extends Model
{
    protected $table            = 'products';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'tenant_id',
        'category_id',
        'sku',
        'barcode',
        'name',
        'unit',
        'cost_price',
        'sell_price',
        'has_serial',
        'track_stock',
        'costing_method',
        'is_active',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * ดึงยอดคงเหลือจริงในสต็อกคำนวณจาก Immutable Stock Ledger
     */
    public function getStockBalance(int $tenantId, int $productId, ?int $branchId = null, ?int $warehouseId = null): float
    {
        $builder = $this->db->table('stock_transactions')
                            ->selectSum('qty')
                            ->where('tenant_id', $tenantId)
                            ->where('product_id', $productId);

        if ($branchId !== null) {
            $builder->where('branch_id', $branchId);
        }
        if ($warehouseId !== null) {
            $builder->where('warehouse_id', $warehouseId);
        }

        $row = $builder->get()->getRowArray();
        return (float)($row['qty'] ?? 0.00);
    }
}
