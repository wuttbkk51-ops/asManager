<?php

namespace App\Models;

use CodeIgniter\Model;

class BranchModel extends Model
{
    protected $table            = 'branches';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'tenant_id',
        'code',
        'name',
        'phone',
        'address',
        'is_main',
        'is_active',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * ดึงสาขาที่เปิดใช้งานทั้งหมดของร้าน
     */
    public function getActiveBranches(int $tenantId): array
    {
        return $this->where('tenant_id', $tenantId)
                    ->where('is_active', 1)
                    ->orderBy('is_main', 'DESC')
                    ->findAll();
    }
}
