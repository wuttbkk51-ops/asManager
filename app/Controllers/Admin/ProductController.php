<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ProductModel;
use App\Services\StockLedgerService;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Database;

class ProductController extends BaseController
{
    protected ProductModel $productModel;
    protected StockLedgerService $stockService;

    public function __construct()
    {
        $this->productModel = new ProductModel();
        $this->stockService = new StockLedgerService();
    }

    /**
     * หน้าหลักแสดงรายการสินค้า (Tabulator Table + Reusable UI Drawer)
     */
    public function index()
    {
        $tenantId = function_exists('tenant') && tenant('id') ? (int)tenant('id') : 1;
        $db = Database::connect();

        $categories = $db->table('categories')
                         ->where('tenant_id', $tenantId)
                         ->orderBy('name', 'ASC')
                         ->get()
                         ->getResultArray();

        $data = [
            'title'      => 'จัดการสินค้าและอะไหล่ (Products & Inventory)',
            'categories' => $categories,
        ];

        return view('admin/products/index', $data);
    }

    /**
     * API คืนค่า JSON รายการสินค้าสำหรับ Tabulator Table
     */
    public function listData(): ResponseInterface
    {
        $tenantId = function_exists('tenant') && tenant('id') ? (int)tenant('id') : 1;
        $db = Database::connect();

        $rows = $db->table('products p')
                   ->select('p.*, c.name as category_name')
                   ->join('categories c', 'c.id = p.category_id', 'left')
                   ->where('p.tenant_id', $tenantId)
                   ->orderBy('p.id', 'DESC')
                   ->get()
                   ->getResultArray();

        $data = [];
        foreach ($rows as $row) {
            $prodId = (int)$row['id'];
            $stock = $row['track_stock'] ? $this->productModel->getStockBalance($tenantId, $prodId) : 0;

            $data[] = [
                'id'             => $prodId,
                'sku'            => $row['sku'],
                'barcode'        => $row['barcode'] ?? '',
                'name'           => $row['name'],
                'category_name'  => $row['category_name'] ?? 'ทั่วไป',
                'category_id'    => $row['category_id'],
                'unit'           => $row['unit'] ?? 'ชิ้น',
                'cost_price'     => (float)$row['cost_price'],
                'sell_price'     => (float)$row['sell_price'],
                'stock_qty'      => (float)$stock,
                'has_serial'     => (int)$row['has_serial'],
                'track_stock'    => (int)$row['track_stock'],
                'costing_method' => $row['costing_method'] ?? '',
                'is_active'      => (int)$row['is_active'],
            ];
        }

        return $this->response->setJSON($data);
    }

    /**
     * ดึงข้อมูลสินค้า 1 รายการเพื่อโหลดเข้า Reusable Form (AJAX)
     */
    public function getItem(int $id): ResponseInterface
    {
        $tenantId = function_exists('tenant') && tenant('id') ? (int)tenant('id') : 1;
        $item = $this->productModel->where('tenant_id', $tenantId)->find($id);

        if (!$item) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'ไม่พบข้อมูลสินค้า',
            ])->setStatusCode(404);
        }

        $stock = $item['track_stock'] ? $this->productModel->getStockBalance($tenantId, $id) : 0;
        $item['current_stock'] = $stock;

        return $this->response->setJSON([
            'success' => true,
            'data'    => $item,
        ]);
    }

    /**
     * ดึงค่าจาก Request POST หรือ Fallback จาก $_POST (เพื่อรองรับทั้ง HTTP Web Request และ CLI/Unit Tests)
     */
    protected function getField(string $key, $default = null)
    {
        $val = null;
        if ($this->request && method_exists($this->request, 'getPost')) {
            $val = $this->request->getPost($key);
        }
        if ($val === null && isset($_POST[$key])) {
            $val = $_POST[$key];
        }
        return $val !== null ? $val : $default;
    }

    /**
     * บันทึกสินค้าใหม่ (Store)
     */
    public function store(): ResponseInterface
    {
        $tenantId = function_exists('tenant') && tenant('id') ? (int)tenant('id') : 1;
        $userId = function_exists('auth_user') && auth_user('id') ? (int)auth_user('id') : 2;

        $name = trim((string)$this->getField('name', ''));
        if (empty($name)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'กรุณาระบุชื่อสินค้าหรืออะไหล่',
            ])->setStatusCode(422);
        }

        // Auto-generate SKU หากผู้ใช้ไม่ได้ระบุ (UX Best Practice: Zero friction)
        $sku = trim((string)$this->getField('sku', ''));
        if (empty($sku)) {
            $sku = 'PRD-' . date('ym') . '-' . str_pad((string)mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        }

        $sellPrice     = (float)$this->getField('sell_price', 0);
        $costPrice     = (float)$this->getField('cost_price', 0);
        $categoryId    = $this->getField('category_id') ? (int)$this->getField('category_id') : null;
        $unit          = trim((string)$this->getField('unit', '')) ?: 'ชิ้น';
        $barcode       = trim((string)$this->getField('barcode', '')) ?: null;
        $hasSerial     = $this->getField('has_serial') ? 1 : 0;
        $trackStock    = $this->getField('track_stock') !== null ? (int)$this->getField('track_stock') : 1;
        $costingMethod = trim((string)$this->getField('costing_method', '')) ?: null;
        $initialStock  = (float)$this->getField('initial_stock', 0);

        // บันทึกลงตาราง products
        $prodId = $this->productModel->insert([
            'tenant_id'      => $tenantId,
            'category_id'    => $categoryId,
            'sku'            => $sku,
            'barcode'        => $barcode,
            'name'           => $name,
            'unit'           => $unit,
            'cost_price'     => $costPrice,
            'sell_price'     => $sellPrice,
            'has_serial'     => $hasSerial,
            'track_stock'    => $trackStock,
            'costing_method' => $costingMethod,
            'is_active'      => 1,
        ]);

        if (!$prodId) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'ไม่สามารถบันทึกสินค้าได้ กรุณาตรวจสอบว่ารหัส SKU ซ้ำหรือไม่',
            ])->setStatusCode(500);
        }

        // หากมีสต็อกเริ่มต้น และเป็นสินค้าตัดสต็อก ให้บันทึกรับเข้าคลังแรกของร้านทันที
        if ($trackStock && $initialStock > 0) {
            $db = Database::connect();
            $wh = $db->table('warehouses')
                     ->where('tenant_id', $tenantId)
                     ->orderBy('id', 'ASC')
                     ->get()
                     ->getRowArray();

            if ($wh) {
                $this->stockService->directInbound(
                    $tenantId,
                    (int)$wh['branch_id'],
                    (int)$wh['id'],
                    (int)$prodId,
                    $initialStock,
                    $costPrice,
                    null,
                    'ยอดยกมา / สต็อกเริ่มต้นตอนสร้างสินค้า',
                    $userId
                );
            }
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => 'เพิ่มสินค้าใหม่เรียบร้อยแล้ว (รหัส SKU: ' . $sku . ')',
            'id'      => $prodId,
        ]);
    }

    /**
     * บันทึกแก้ไขสินค้า (Update)
     */
    public function update(int $id): ResponseInterface
    {
        $tenantId = function_exists('tenant') && tenant('id') ? (int)tenant('id') : 1;
        $existing = $this->productModel->where('tenant_id', $tenantId)->find($id);

        if (!$existing) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'ไม่พบข้อมูลสินค้าที่ต้องการแก้ไข',
            ])->setStatusCode(404);
        }

        $name = trim((string)$this->getField('name', ''));
        if (empty($name)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'กรุณาระบุชื่อสินค้าหรืออะไหล่',
            ])->setStatusCode(422);
        }

        $sku           = trim((string)$this->getField('sku', '')) ?: $existing['sku'];
        $sellPrice     = (float)$this->getField('sell_price', 0);
        $costPrice     = (float)$this->getField('cost_price', 0);
        $categoryId    = $this->getField('category_id') ? (int)$this->getField('category_id') : null;
        $unit          = trim((string)$this->getField('unit', '')) ?: 'ชิ้น';
        $barcode       = trim((string)$this->getField('barcode', '')) ?: null;
        $hasSerial     = $this->getField('has_serial') ? 1 : 0;
        $trackStock    = $this->getField('track_stock') !== null ? (int)$this->getField('track_stock') : 1;
        $costingMethod = trim((string)$this->getField('costing_method', '')) ?: null;
        $isActive      = $this->getField('is_active') !== null ? (int)$this->getField('is_active') : 1;

        $updated = $this->productModel->update($id, [
            'category_id'    => $categoryId,
            'sku'            => $sku,
            'barcode'        => $barcode,
            'name'           => $name,
            'unit'           => $unit,
            'cost_price'     => $costPrice,
            'sell_price'     => $sellPrice,
            'has_serial'     => $hasSerial,
            'track_stock'    => $trackStock,
            'costing_method' => $costingMethod,
            'is_active'      => $isActive,
        ]);

        return $this->response->setJSON([
            'success' => (bool)$updated,
            'message' => $updated ? 'บันทึกการแก้ไขสินค้าเรียบร้อยแล้ว' : 'ไม่สามารถอัปเดตสินค้าได้',
        ]);
    }

    /**
     * ลบสินค้า (Delete / Deactivate)
     */
    public function delete(int $id): ResponseInterface
    {
        $tenantId = function_exists('tenant') && tenant('id') ? (int)tenant('id') : 1;
        $existing = $this->productModel->where('tenant_id', $tenantId)->find($id);

        if (!$existing) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'ไม่พบข้อมูลสินค้า',
            ])->setStatusCode(404);
        }

        // Soft Delete ด้วยการตั้งค่า is_active = 0
        $this->productModel->update($id, ['is_active' => 0]);

        return $this->response->setJSON([
            'success' => true,
            'message' => 'ปิดการใช้งานและลบสินค้าออกจากหน้ารายการเรียบร้อยแล้ว',
        ]);
    }
}
