<?php

namespace App\Models;

use CodeIgniter\Model;

class WarehouseModel extends Model
{
    protected $table            = 'warehouses';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'tenant_id',
        'branch_id',
        'parent_id',
        'name',
        'code',
        'type', // warehouse, zone, shelf, bin
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * ดึงโครงสร้างคลัง/ชั้นวางแบบ Tree สำหรับสาขาที่กำหนด
     */
    public function getTree(int $tenantId, int $branchId): array
    {
        $all = $this->where('tenant_id', $tenantId)
                    ->where('branch_id', $branchId)
                    ->findAll();

        return $this->buildTree($all, null);
    }

    private function buildTree(array $elements, ?int $parentId = null): array
    {
        $branch = [];
        foreach ($elements as $element) {
            if ($element['parent_id'] == $parentId) {
                $children = $this->buildTree($elements, (int)$element['id']);
                if ($children) {
                    $element['children'] = $children;
                } else {
                    $element['children'] = [];
                }
                $branch[] = $element;
            }
        }
        return $branch;
    }

    /**
     * ดึงเส้นทางแบบเต็มของคลัง/ชั้นวาง เช่น "คลังหน้าร้าน > ชั้นวางอะไหล่ A > ช่องเก็บ 01"
     */
    public function getFullPath(int $warehouseId): string
    {
        $path = [];
        $currentId = $warehouseId;

        while ($currentId) {
            $item = $this->find($currentId);
            if (!$item) {
                break;
            }
            array_unshift($path, $item['name']);
            $currentId = $item['parent_id'];
        }

        return implode(' > ', $path);
    }
}
