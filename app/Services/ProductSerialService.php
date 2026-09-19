<?php

namespace App\Services;

use App\Models\ProductModel;
use App\Models\ProductSerialModel;
use Config\Database;

class ProductSerialService
{
    protected $db;
    protected ProductSerialModel $serialModel;
    protected ProductModel $productModel;

    public function __construct()
    {
        $this->db           = Database::connect();
        $this->serialModel  = new ProductSerialModel();
        $this->productModel = new ProductModel();
    }

    /**
     * สร้าง Serial Number อัตโนมัติเป็นชุด (Batch Auto-Generator)
     * พร้อมบันทึกรับเข้าคลังใน Stock Ledger ทันที
     */
    public function generateBatchSerials(
        int $tenantId,
        int $productId,
        int $branchId,
        int $warehouseId,
        int $qty,
        ?string $prefix = null,
        float $unitCost = 0.00,
        ?string $notes = null,
        ?int $userId = null
    ): array {
        if ($qty <= 0) {
            return ['success' => false, 'message' => 'กรุณาระบุจำนวนที่ต้องการสร้างให้มากกว่า 0'];
        }

        // ตรวจสอบสินค้า
        $product = $this->productModel->where('tenant_id', $tenantId)->find($productId);
        if (!$product) {
            return ['success' => false, 'message' => 'ไม่พบข้อมูลสินค้าที่ระบุ'];
        }

        // กำหนด Prefix
        $prefix = trim((string)$prefix);
        if (empty($prefix)) {
            $prefix = !empty($product['sku']) ? preg_replace('/[^A-Za-z0-9]/', '', $product['sku']) : 'SN';
            $prefix = strtoupper(substr($prefix, 0, 8));
        } else {
            $prefix = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $prefix));
        }

        $now = date('Y-m-d H:i:s');
        $yymm = date('ym');
        $patternBase = "{$prefix}-{$yymm}-";

        // หาเลข Sequence ล่าสุดในรอบเดือนนี้ของสินค้านี้
        $lastSnRow = $this->db->table('product_serials')
            ->select('serial_no')
            ->where('tenant_id', $tenantId)
            ->like('serial_no', $patternBase, 'after')
            ->orderBy('id', 'DESC')
            ->get()
            ->getRowArray();

        $seq = 1;
        if ($lastSnRow) {
            $parts = explode('-', $lastSnRow['serial_no']);
            $lastNum = (int)end($parts);
            if ($lastNum > 0) {
                $seq = $lastNum + 1;
            }
        }

        $generatedSerials = [];
        $createdSerialIds = [];

        // ใช้ Transaction เพื่อความถูกต้องของข้อมูล (All-or-Nothing)
        $this->db->transStart();

        for ($i = 0; $i < $qty; $i++) {
            // สร้าง SN และตรวจสอบไม่ให้ซ้ำ
            do {
                $sn = sprintf('%s%04d', $patternBase, $seq++);
            } while ($this->serialModel->existsSerial($tenantId, $sn));

            $serialId = $this->serialModel->insert([
                'tenant_id'    => $tenantId,
                'product_id'   => $productId,
                'branch_id'    => $branchId,
                'warehouse_id' => $warehouseId,
                'serial_no'    => $sn,
                'status'       => 'in_stock',
                'notes'        => $notes ?: 'สร้างอัตโนมัติ (Batch Auto-Gen)',
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);

            // ลง Ledger ใน stock_transactions รายชิ้น
            $this->db->table('stock_transactions')->insert([
                'tenant_id'     => $tenantId,
                'branch_id'     => $branchId,
                'warehouse_id'  => $warehouseId,
                'product_id'    => $productId,
                'serial_id'     => $serialId,
                'movement_type' => 'direct_in',
                'qty'           => 1.00,
                'cost_price'    => $unitCost > 0 ? $unitCost : (float)$product['cost_price'],
                'ref_type'      => 'auto_gen',
                'ref_id'        => null,
                'notes'         => "รับเข้าอัตโนมัติจากการสร้าง SN: {$sn}",
                'created_by'    => $userId,
                'created_at'    => $now,
            ]);

            $generatedSerials[] = [
                'id'        => $serialId,
                'serial_no' => $sn,
            ];
            $createdSerialIds[] = $serialId;
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return ['success' => false, 'message' => 'เกิดข้อผิดพลาดในการบันทึกข้อมูล Serial Number'];
        }

        return [
            'success'   => true,
            'message'   => "สร้าง Serial Number อัตโนมัติสำเร็จจำนวน {$qty} รายการ",
            'count'     => $qty,
            'serials'   => $generatedSerials,
            'ids'       => $createdSerialIds,
        ];
    }

    /**
     * รับเข้า Serial Number ด้วยการสแกนผ่านปืนบาร์โค้ด (Continuous Scanner Inbound)
     */
    public function inboundScannedSerials(
        int $tenantId,
        int $productId,
        int $branchId,
        int $warehouseId,
        array $serialList,
        float $unitCost = 0.00,
        ?string $notes = null,
        ?int $userId = null
    ): array {
        if (empty($serialList)) {
            return ['success' => false, 'message' => 'ไม่พบรายการ Serial Number ที่ต้องการนำเข้า'];
        }

        $product = $this->productModel->where('tenant_id', $tenantId)->find($productId);
        if (!$product) {
            return ['success' => false, 'message' => 'ไม่พบข้อมูลสินค้าที่ระบุ'];
        }

        $now = date('Y-m-d H:i:s');
        $effectiveCost = $unitCost > 0 ? $unitCost : (float)$product['cost_price'];

        $imported = [];
        $duplicates = [];

        $this->db->transStart();

        foreach ($serialList as $rawSn) {
            $sn = trim((string)$rawSn);
            if (empty($sn)) continue;

            // ตรวจสอบความซ้ำซ้อนภายในร้าน
            if ($this->serialModel->existsSerial($tenantId, $sn)) {
                $duplicates[] = $sn;
                continue;
            }

            $serialId = $this->serialModel->insert([
                'tenant_id'    => $tenantId,
                'product_id'   => $productId,
                'branch_id'    => $branchId,
                'warehouse_id' => $warehouseId,
                'serial_no'    => $sn,
                'status'       => 'in_stock',
                'notes'        => $notes ?: 'รับเข้าผ่านการสแกนบาร์โค้ด',
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);

            // ลง Ledger
            $this->db->table('stock_transactions')->insert([
                'tenant_id'     => $tenantId,
                'branch_id'     => $branchId,
                'warehouse_id'  => $warehouseId,
                'product_id'    => $productId,
                'serial_id'     => $serialId,
                'movement_type' => 'direct_in',
                'qty'           => 1.00,
                'cost_price'    => $effectiveCost,
                'ref_type'      => 'scanner_inbound',
                'ref_id'        => null,
                'notes'         => "รับเข้าผ่านปืนสแกนบาร์โค้ด: {$sn}",
                'created_by'    => $userId,
                'created_at'    => $now,
            ]);

            $imported[] = [
                'id'        => $serialId,
                'serial_no' => $sn,
            ];
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return ['success' => false, 'message' => 'เกิดข้อผิดพลาดในการบันทึกข้อมูล'];
        }

        $importedCount = count($imported);
        $dupCount = count($duplicates);

        $msg = "รับเข้าสำเร็จ {$importedCount} รายการ";
        if ($dupCount > 0) {
            $msg .= " (ข้ามรหัสซ้ำ {$dupCount} รายการ: " . implode(', ', array_slice($duplicates, 0, 3)) . ($dupCount > 3 ? '...' : '') . ")";
        }

        return [
            'success'        => true,
            'message'        => $msg,
            'imported_count' => $importedCount,
            'imported'       => $imported,
            'duplicates'     => $duplicates,
        ];
    }

    /**
     * ดึงข้อมูลรายการ Serial Numbers สำหรับ Tabulator Table
     */
    public function getSerialsData(int $tenantId, array $filters = []): array
    {
        $builder = $this->db->table('product_serials ps')
            ->select('ps.*, p.name as product_name, p.sku as product_sku, p.sell_price, p.unit, b.name as branch_name, w.name as warehouse_name')
            ->join('products p', 'p.id = ps.product_id', 'left')
            ->join('branches b', 'b.id = ps.branch_id', 'left')
            ->join('warehouses w', 'w.id = ps.warehouse_id', 'left')
            ->where('ps.tenant_id', $tenantId)
            ->orderBy('ps.id', 'DESC');

        if (!empty($filters['product_id'])) {
            $builder->where('ps.product_id', (int)$filters['product_id']);
        }
        if (!empty($filters['status'])) {
            $builder->where('ps.status', $filters['status']);
        }
        if (!empty($filters['branch_id'])) {
            $builder->where('ps.branch_id', (int)$filters['branch_id']);
        }

        $rows = $builder->get()->getResultArray();

        $result = [];
        foreach ($rows as $r) {
            $result[] = [
                'id'             => (int)$r['id'],
                'serial_no'      => $r['serial_no'],
                'product_id'     => (int)$r['product_id'],
                'product_name'   => $r['product_name'] ?? 'ไม่ระบุ',
                'product_sku'    => $r['product_sku'] ?? '-',
                'sell_price'     => (float)$r['sell_price'],
                'branch_name'    => $r['branch_name'] ?? 'สำนักงานใหญ่',
                'warehouse_name' => $r['warehouse_name'] ?? 'คลังหลัก',
                'status'         => $r['status'],
                'notes'          => $r['notes'] ?? '',
                'created_at'     => $r['created_at'],
            ];
        }

        return $result;
    }

    /**
     * ดึง Audit Trail ไทม์ไลน์ประวัติการเคลื่อนไหวของ Serial Number รายชิ้น
     */
    public function getSerialTimeline(int $tenantId, int $serialId): array
    {
        $serial = $this->serialModel->getSerialWithDetails($tenantId, $serialId);
        if (!$serial) {
            return ['success' => false, 'message' => 'ไม่พบข้อมูล Serial Number'];
        }

        $txns = $this->db->table('stock_transactions st')
            ->select('st.*, b.name as branch_name, w.name as warehouse_name, u.username as user_name')
            ->join('branches b', 'b.id = st.branch_id', 'left')
            ->join('warehouses w', 'w.id = st.warehouse_id', 'left')
            ->join('users u', 'u.id = st.created_by', 'left')
            ->where('st.tenant_id', $tenantId)
            ->where('st.serial_id', $serialId)
            ->orderBy('st.id', 'ASC')
            ->get()
            ->getResultArray();

        $timeline = [];
        foreach ($txns as $t) {
            $typeMeta = $this->getMovementMeta($t['movement_type'], $t['qty']);
            $timeline[] = [
                'id'            => (int)$t['id'],
                'date'          => $t['created_at'],
                'movement_type' => $t['movement_type'],
                'title'         => $typeMeta['title'],
                'icon'          => $typeMeta['icon'],
                'badge_class'   => $typeMeta['badge'],
                'qty'           => (float)$t['qty'],
                'branch_name'   => $t['branch_name'] ?? 'สำนักงานใหญ่',
                'warehouse_name'=> $t['warehouse_name'] ?? 'คลังหลัก',
                'user_name'     => $t['user_name'] ?? 'ระบบ',
                'notes'         => $t['notes'] ?? '',
                'ref_type'      => $t['ref_type'],
                'ref_id'        => $t['ref_id'],
            ];
        }

        return [
            'success'  => true,
            'serial'   => $serial,
            'timeline' => $timeline,
        ];
    }

    /**
     * อัปเดตสถานะ Serial (เช่น เสียหาย / สูญหาย / กลับมาพร้อมใช้)
     */
    public function updateSerialStatus(int $tenantId, int $serialId, string $status, ?string $notes = null, ?int $userId = null): array
    {
        $serial = $this->serialModel->where('tenant_id', $tenantId)->find($serialId);
        if (!$serial) {
            return ['success' => false, 'message' => 'ไม่พบข้อมูล Serial Number'];
        }

        $oldStatus = $serial['status'];
        if ($oldStatus === $status) {
            return ['success' => true, 'message' => 'สถานะปัจจุบันตรงกันอยู่แล้ว'];
        }

        $now = date('Y-m-d H:i:s');
        $this->db->transStart();

        $this->serialModel->setStatus($serialId, $status, $notes);

        // หากปรับเป็น damaged หรือ lost ให้บันทึกลดสต็อกออกจากคลัง
        if (in_array($status, ['damaged', 'lost']) && $oldStatus === 'in_stock') {
            $this->db->table('stock_transactions')->insert([
                'tenant_id'     => $tenantId,
                'branch_id'     => $serial['branch_id'],
                'warehouse_id'  => $serial['warehouse_id'],
                'product_id'    => $serial['product_id'],
                'serial_id'     => $serialId,
                'movement_type' => 'adjust_out',
                'qty'           => -1.00,
                'cost_price'    => 0.00,
                'ref_type'      => 'status_change',
                'ref_id'        => null,
                'notes'         => "ปรับสถานะเป็น {$status}: " . ($notes ?: '-'),
                'created_by'    => $userId,
                'created_at'    => $now,
            ]);
        } 
        // หากเปลี่ยนจาก damaged/lost กลับมาเป็น in_stock ให้บันทึกเพิ่มสต็อกกลับคืน
        elseif ($status === 'in_stock' && in_array($oldStatus, ['damaged', 'lost'])) {
            $this->db->table('stock_transactions')->insert([
                'tenant_id'     => $tenantId,
                'branch_id'     => $serial['branch_id'],
                'warehouse_id'  => $serial['warehouse_id'],
                'product_id'    => $serial['product_id'],
                'serial_id'     => $serialId,
                'movement_type' => 'adjust_in',
                'qty'           => 1.00,
                'cost_price'    => 0.00,
                'ref_type'      => 'status_change',
                'ref_id'        => null,
                'notes'         => "ปรับสถานะกลับเป็นพร้อมใช้งาน (in_stock): " . ($notes ?: '-'),
                'created_by'    => $userId,
                'created_at'    => $now,
            ]);
        }

        $this->db->transComplete();

        return [
            'success' => $this->db->transStatus() !== false,
            'message' => 'ปรับปรุงสถานะ Serial Number เรียบร้อยแล้ว',
        ];
    }

    /**
     * คำอธิบายและ Icon ประจำประเภทการเคลื่อนไหว
     */
    protected function getMovementMeta(string $type, float $qty): array
    {
        switch ($type) {
            case 'direct_in':
            case 'po_receive':
                return ['title' => 'รับเข้าคลังสินค้า', 'icon' => 'bi-box-seam', 'badge' => 'text-bg-success'];
            case 'transfer_out':
                return ['title' => 'โอนย้ายออก (ส่งไปสาขาอื่น)', 'icon' => 'bi-truck', 'badge' => 'text-bg-warning'];
            case 'transfer_in':
                return ['title' => 'รับเข้าจากการโอนย้ายสาขา', 'icon' => 'bi-check2-circle', 'badge' => 'text-bg-info'];
            case 'pos_sale':
                return ['title' => 'ขายหน้าร้าน (POS)', 'icon' => 'bi-cart-check', 'badge' => 'text-bg-primary'];
            case 'repair_use':
                return ['title' => 'เบิกใช้อะไหล่ในงานซ่อม', 'icon' => 'bi-tools', 'badge' => 'text-bg-secondary'];
            case 'adjust_out':
                return ['title' => 'ตัดยอดออก (ชำรุด/สูญหาย)', 'icon' => 'bi-dash-circle', 'badge' => 'text-bg-danger'];
            case 'adjust_in':
                return ['title' => 'ปรับยอดรับคืนเข้าสต็อก', 'icon' => 'bi-plus-circle', 'badge' => 'text-bg-success'];
            default:
                return ['title' => 'ปรับปรุงสต็อก', 'icon' => 'bi-arrow-left-right', 'badge' => 'text-bg-light'];
        }
    }
}
