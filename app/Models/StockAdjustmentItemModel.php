<?php

namespace App\Models;

use CodeIgniter\Model;

class StockAdjustmentItemModel extends Model
{
    protected $table            = 'stock_adjustment_items';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'stock_adjustment_id',
        'product_id',
        'serial_id',
        'system_qty',
        'counted_qty',
        'diff_qty',
        'cost_price',
        'diff_amount',
        'reason',
        'created_at',
    ];

    protected $useTimestamps = false;
}
