<?php

namespace App\Models;

use CodeIgniter\Model;

class StockTransferItemModel extends Model
{
    protected $table            = 'stock_transfer_items';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'stock_transfer_id',
        'product_id',
        'serial_id',
        'qty',
        'created_at',
    ];

    protected $useTimestamps = false;
}
