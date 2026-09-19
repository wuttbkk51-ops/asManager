<?php

namespace App\Services;

use App\Models\TenantModel;

class PlanLimitService
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    /**
     * ดึงข้อมูล Plan ของร้านค้า (Tenant)
     */
    public function getTenantPlan(int $tenantId): ?array
    {
        $tenant = $this->db->table('tenants')->where('id', $tenantId)->get()->getRowArray();
        if (!$tenant) {
            return null;
        }

        $planCode = $tenant['plan'] ?? 'freePlan';
        return $this->db->table('plans')->where('code', $planCode)->get()->getRowArray();
    }

    /**
     * ตรวจสอบโควตาจำนวนสินค้า (Products Limit)
     * limit = 0 หรือ null หมายถึง ไม่จำกัด (Unlimited)
     */
    public function checkProductLimit(int $tenantId): array
    {
        $plan = $this->getTenantPlan($tenantId);
        $max = (int)($plan['max_products'] ?? 50);

        $currentCount = $this->db->table('products')
                                 ->where('tenant_id', $tenantId)
                                 ->where('is_active', 1)
                                 ->countAllResults();

        $allowed = ($max === 0) || ($currentCount < $max);

        return [
            'allowed' => $allowed,
            'current' => $currentCount,
            'limit'   => $max,
            'plan'    => $plan['name'] ?? 'Unknown',
            'message' => $allowed
                ? "สามารถเพิ่มสินค้าได้ (ใช้งานแล้ว {$currentCount}/" . ($max === 0 ? 'ไม่จำกัด' : $max) . ")"
                : "จำนวนสินค้าเกินโควตาของแพ็กเกจ {$plan['name']} ({$currentCount}/{$max}) กรุณาอัปเกรดแพ็กเกจ",
        ];
    }

    /**
     * ตรวจสอบโควตาจำนวนผู้ใช้/ลูกน้องในร้าน (Users Limit)
     * limit = 0 หมายถึง ไม่จำกัด (Unlimited)
     */
    public function checkUserLimit(int $tenantId): array
    {
        $plan = $this->getTenantPlan($tenantId);
        $max = (int)($plan['max_users'] ?? 2);

        $currentCount = $this->db->table('users')
                                 ->where('tenant_id', $tenantId)
                                 ->where('status', 'active')
                                 ->countAllResults();

        $allowed = ($max === 0) || ($currentCount < $max);

        return [
            'allowed' => $allowed,
            'current' => $currentCount,
            'limit'   => $max,
            'plan'    => $plan['name'] ?? 'Unknown',
            'message' => $allowed
                ? "สามารถเพิ่มผู้ใช้ได้ (ใช้งานแล้ว {$currentCount}/" . ($max === 0 ? 'ไม่จำกัด' : $max) . ")"
                : "จำนวนผู้ใช้เกินโควตาของแพ็กเกจ {$plan['name']} ({$currentCount}/{$max}) กรุณาอัปเกรดแพ็กเกจ",
        ];
    }

    /**
     * ตรวจสอบโควตาจำนวนสาขา (Branches Limit)
     * limit = 0 หมายถึง ไม่จำกัด (Unlimited)
     */
    public function checkBranchLimit(int $tenantId): array
    {
        $plan = $this->getTenantPlan($tenantId);
        $max = (int)($plan['max_branches'] ?? 1);

        $currentCount = $this->db->table('branches')
                                 ->where('tenant_id', $tenantId)
                                 ->where('is_active', 1)
                                 ->countAllResults();

        $allowed = ($max === 0) || ($currentCount < $max);

        return [
            'allowed' => $allowed,
            'current' => $currentCount,
            'limit'   => $max,
            'plan'    => $plan['name'] ?? 'Unknown',
            'message' => $allowed
                ? "สามารถเพิ่มสาขาได้ (ใช้งานแล้ว {$currentCount}/" . ($max === 0 ? 'ไม่จำกัด' : $max) . ")"
                : "จำนวนสาขาเกินโควตาของแพ็กเกจ {$plan['name']} ({$currentCount}/{$max}) กรุณาอัปเกรดแพ็กเกจ",
        ];
    }
}
