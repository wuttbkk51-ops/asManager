<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductSerialModel extends Model
{
    protected $table            = 'product_serials';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'tenant_id',
        'product_id',
        'branch_id',
        'warehouse_id',
        'serial_no',
        'status',
        'notes',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * ดึง Serial Number ที่พร้อมใช้งาน (in_stock) ของสินค้าที่ระบุ
     */
    public function getAvailableSerials(int $tenantId, int $productId, ?int $branchId = null): array
    {
        $builder = $this->where('tenant_id', $tenantId)
                        ->where('product_id', $productId)
                        ->where('status', 'in_stock');

        if ($branchId !== null) {
            $builder->where('branch_id', $branchId);
        }

        return $builder->findAll();
    }

    /**
     * อัปเดตสถานะ Serial (เช่น เปลี่ยนเป็น reserved, used_in_repair, in_stock)
     */
    public function setStatus(int $serialId, string $status, ?string $notes = null): bool
    {
        $data = ['status' => $status];
        if ($notes !== null) {
            $data['notes'] = $notes;
        }
        return $this->update($serialId, $data);
    }

    /**
     * ตรวจสอบว่ามี Serial Number นี้อยู่ในร้านแล้วหรือไม่ (ป้องกันการบันทึกซ้ำ)
     */
    public function existsSerial(int $tenantId, string $serialNo, ?int $excludeId = null): bool
    {
        $builder = $this->where('tenant_id', $tenantId)
                        ->where('serial_no', trim($serialNo));
        if ($excludeId !== null) {
            $builder->where('id !=', $excludeId);
        }
        return $builder->countAllResults() > 0;
    }

    /**
     * ค้นหา Serial ตามหมายเลข
     */
    public function findBySerial(int $tenantId, string $serialNo): ?array
    {
        return $this->where('tenant_id', $tenantId)
                    ->where('serial_no', trim($serialNo))
                    ->first();
    }

    /**
     * ดึงข้อมูล Serial พร้อมข้อมูลสินค้า สาขา และคลัง
     */
    public function getSerialWithDetails(int $tenantId, int $serialId): ?array
    {
        return $this->select('product_serials.*, p.name as product_name, p.sku as product_sku, p.sell_price, p.cost_price, b.name as branch_name, w.name as warehouse_name')
                    ->join('products p', 'p.id = product_serials.product_id', 'left')
                    ->join('branches b', 'b.id = product_serials.branch_id', 'left')
                    ->join('warehouses w', 'w.id = product_serials.warehouse_id', 'left')
                    ->where('product_serials.tenant_id', $tenantId)
                    ->where('product_serials.id', $serialId)
                    ->first();
    }
}
