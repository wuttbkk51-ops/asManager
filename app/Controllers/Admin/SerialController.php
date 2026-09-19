<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ProductModel;
use App\Services\ProductSerialService;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Database;

class SerialController extends BaseController
{
    protected ProductSerialService $serialService;
    protected ProductModel $productModel;

    public function __construct()
    {
        $this->serialService = new ProductSerialService();
        $this->productModel  = new ProductModel();
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
     * หน้าหลักศูนย์จัดการ Serial Number / IMEI
     */
    public function index()
    {
        $tenantId = function_exists('tenant') && tenant('id') ? (int)tenant('id') : 1;
        $db = Database::connect();

        // ดึงรายการสินค้าที่มีการเปิดสวิตช์คุม Serial (has_serial = 1)
        $products = $db->table('products')
            ->where('tenant_id', $tenantId)
            ->where('has_serial', 1)
            ->where('is_active', 1)
            ->orderBy('name', 'ASC')
            ->get()
            ->getResultArray();

        // ดึงสาขาและคลังสินค้า
        $branches = $db->table('branches')
            ->where('tenant_id', $tenantId)
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        $warehouses = $db->table('warehouses')
            ->where('tenant_id', $tenantId)
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        $data = [
            'title'      => 'ศูนย์จัดการ Serial Number / IMEI (Traceability Hub)',
            'products'   => $products,
            'branches'   => $branches,
            'warehouses' => $warehouses,
        ];

        return view('admin/serials/index', $data);
    }

    /**
     * API คืนค่า JSON รายการ Serial Numbers สำหรับ Tabulator Table
     */
    public function listData(): ResponseInterface
    {
        $tenantId = function_exists('tenant') && tenant('id') ? (int)tenant('id') : 1;

        $filters = [
            'product_id' => $this->request->getGet('product_id') ?: null,
            'status'     => $this->request->getGet('status') ?: null,
            'branch_id'  => $this->request->getGet('branch_id') ?: null,
        ];

        $data = $this->serialService->getSerialsData($tenantId, $filters);

        return $this->response->setJSON($data);
    }

    /**
     * สร้าง Serial Numbers อัตโนมัติเป็นชุด (Batch Auto-Gen)
     */
    public function generate(): ResponseInterface
    {
        $tenantId = function_exists('tenant') && tenant('id') ? (int)tenant('id') : 1;
        $userId   = function_exists('auth_user') && auth_user('id') ? (int)auth_user('id') : 2;

        $productId   = (int)$this->getField('product_id');
        $branchId    = (int)$this->getField('branch_id');
        $warehouseId = (int)$this->getField('warehouse_id');
        $qty         = (int)$this->getField('qty');
        $prefix      = trim((string)$this->getField('prefix'));
        $notes       = trim((string)$this->getField('notes'));
        $unitCost    = (float)$this->getField('cost_price', 0);

        if (!$productId) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'กรุณาเลือกสินค้าที่ต้องการสร้าง Serial Number',
            ])->setStatusCode(422);
        }

        // หากไม่ระบุ branch หรือ warehouse ให้ใช้ตัวแรกของร้าน
        $db = Database::connect();
        if (!$warehouseId) {
            $wh = $db->table('warehouses')->where('tenant_id', $tenantId)->orderBy('id', 'ASC')->get()->getRowArray();
            if ($wh) {
                $warehouseId = (int)$wh['id'];
                $branchId = (int)$wh['branch_id'];
            }
        }

        $result = $this->serialService->generateBatchSerials(
            $tenantId,
            $productId,
            $branchId,
            $warehouseId,
            $qty,
            $prefix,
            $unitCost,
            $notes,
            $userId
        );

        $status = $result['success'] ? 200 : 400;
        return $this->response->setJSON($result)->setStatusCode($status);
    }

    /**
     * รับเข้า Serial Numbers จากการสแกนด้วยปืนบาร์โค้ด (Scanner Inbound)
     */
    public function inbound(): ResponseInterface
    {
        $tenantId = function_exists('tenant') && tenant('id') ? (int)tenant('id') : 1;
        $userId   = function_exists('auth_user') && auth_user('id') ? (int)auth_user('id') : 2;

        $productId   = (int)$this->getField('product_id');
        $branchId    = (int)$this->getField('branch_id');
        $warehouseId = (int)$this->getField('warehouse_id');
        $rawSerials  = $this->getField('serials');
        $notes       = trim((string)$this->getField('notes'));
        $unitCost    = (float)$this->getField('cost_price', 0);

        if (!$productId) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'กรุณาเลือกสินค้าที่ต้องการรับเข้า',
            ])->setStatusCode(422);
        }

        // แปลง serials จาก Array หรือ Textarea (ขึ้นบรรทัดใหม่)
        $serialList = [];
        if (is_array($rawSerials)) {
            $serialList = $rawSerials;
        } elseif (is_string($rawSerials)) {
            $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $rawSerials));
            foreach ($lines as $line) {
                $trimmed = trim($line);
                if (!empty($trimmed)) {
                    $serialList[] = $trimmed;
                }
            }
        }

        if (empty($serialList)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'กรุณาระบุหรือสแกน Serial Number อย่างน้อย 1 รายการ',
            ])->setStatusCode(422);
        }

        $db = Database::connect();
        if (!$warehouseId) {
            $wh = $db->table('warehouses')->where('tenant_id', $tenantId)->orderBy('id', 'ASC')->get()->getRowArray();
            if ($wh) {
                $warehouseId = (int)$wh['id'];
                $branchId = (int)$wh['branch_id'];
            }
        }

        $result = $this->serialService->inboundScannedSerials(
            $tenantId,
            $productId,
            $branchId,
            $warehouseId,
            $serialList,
            $unitCost,
            $notes,
            $userId
        );

        $status = $result['success'] ? 200 : 400;
        return $this->response->setJSON($result)->setStatusCode($status);
    }

    /**
     * ดึง Audit Trail ไทม์ไลน์ประวัติการเคลื่อนไหวของ Serial Number
     */
    public function history(int $id): ResponseInterface
    {
        $tenantId = function_exists('tenant') && tenant('id') ? (int)tenant('id') : 1;
        $result = $this->serialService->getSerialTimeline($tenantId, $id);

        $status = $result['success'] ? 200 : 404;
        return $this->response->setJSON($result)->setStatusCode($status);
    }

    /**
     * ปรับสถานะ Serial Number (เช่น เสียหาย / สูญหาย / กลับมาพร้อมใช้)
     */
    public function changeStatus(int $id): ResponseInterface
    {
        $tenantId = function_exists('tenant') && tenant('id') ? (int)tenant('id') : 1;
        $userId   = function_exists('auth_user') && auth_user('id') ? (int)auth_user('id') : 2;

        $status = trim((string)$this->getField('status'));
        $notes  = trim((string)$this->getField('notes'));

        if (empty($status)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'กรุณาระบุสถานะที่ต้องการเปลี่ยน',
            ])->setStatusCode(422);
        }

        $result = $this->serialService->updateSerialStatus($tenantId, $id, $status, $notes, $userId);

        $code = $result['success'] ? 200 : 400;
        return $this->response->setJSON($result)->setStatusCode($code);
    }
}
