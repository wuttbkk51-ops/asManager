<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\StockAdjustmentModel;

class InventoryController extends BaseController
{
    protected $adjModel;

    public function __construct()
    {
        $this->adjModel = new StockAdjustmentModel();
    }

    public function adjustments()
    {
        $tenantId = function_exists('tenant') && tenant('id') ? (int)tenant('id') : 1;
        $adjustments = $this->adjModel->where('tenant_id', $tenantId)
                                      ->orderBy('id', 'DESC')
                                      ->findAll();

        $data = [
            'title'       => 'ตรวจนับและปรับปรุงสต็อก (Stock Adjustments)',
            'adjustments' => $adjustments,
        ];

        return view('admin/inventory/adjustments', $data);
    }
}
