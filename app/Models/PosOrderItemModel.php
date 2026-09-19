<?php

namespace App\Models;

use CodeIgniter\Model;

class PosOrderItemModel extends Model
{
    protected $table            = 'pos_order_items';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'pos_order_id',
        'product_id',
        'serial_id',
        'item_name',
        'qty',
        'cost_price',
        'unit_price',
        'total_price',
        'track_stock',
        'created_at',
    ];

    protected $useTimestamps = false;
}
