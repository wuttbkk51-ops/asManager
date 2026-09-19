<?php

namespace App\Services;

class ReportService
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    /**
     * สรุปสถิติภาพรวมสำหรับ Dashboard ของ Owner (AdminLTE 4 Small Box Widgets)
     */
    public function getDashboardStats(int $tenantId, ?int $branchId = null): array
    {
        $today = date('Y-m-d');

        // 1. ยอดขาย POS รวมวันนี้
        $posBuilder = $this->db->table('pos_orders')
                               ->where('tenant_id', $tenantId)
                               ->where('payment_status', 'paid')
                               ->like('created_at', $today, 'after');
        if ($branchId !== null) {
            $posBuilder->where('branch_id', $branchId);
        }
        $todayPosRow = $posBuilder->selectSum('net_amount')->get()->getRowArray();
        $todayPosSales = (float)($todayPosRow['net_amount'] ?? 0.00);

        // 2. งานซ่อมที่เปิดอยู่และยังไม่ได้ส่งมอบ (Active Repairs)
        $repairBuilder = $this->db->table('repair_jobs')
                                  ->where('tenant_id', $tenantId)
                                  ->whereNotIn('status', ['delivered', 'cancelled']);
        if ($branchId !== null) {
            $repairBuilder->where('branch_id', $branchId);
        }
        $activeRepairs = $repairBuilder->countAllResults();

        // 3. เงินสดในลิ้นชักกะที่เปิดอยู่ (Cash in Open Shifts)
        $shiftBuilder = $this->db->table('cash_shifts')
                                 ->where('tenant_id', $tenantId)
                                 ->where('status', 'open');
        if ($branchId !== null) {
            $shiftBuilder->where('branch_id', $branchId);
        }
        $shiftRow = $shiftBuilder->selectSum('expected_cash')->get()->getRowArray();
        $cashInShifts = (float)($shiftRow['expected_cash'] ?? 0.00);

        // 4. สินค้าสต็อกต่ำกว่าเกณฑ์ (Low Stock Count)
        $lowStockItems = $this->getLowStockAlerts($tenantId, 10.00);
        $lowStockCount = count($lowStockItems);

        // 5. งานซ่อมล่าสุด 5 รายการ
        $recentRepairsBuilder = $this->db->table('repair_jobs')
                                         ->where('tenant_id', $tenantId)
                                         ->orderBy('id', 'DESC')
                                         ->limit(5);
        if ($branchId !== null) {
            $recentRepairsBuilder->where('branch_id', $branchId);
        }
        $recentRepairs = $recentRepairsBuilder->get()->getResultArray();

        return [
            'today_pos_sales' => $todayPosSales,
            'active_repairs'  => $activeRepairs,
            'cash_in_shifts'  => $cashInShifts,
            'low_stock_count' => $lowStockCount,
            'recent_repairs'  => $recentRepairs,
            'low_stock_items' => array_slice($lowStockItems, 0, 5),
        ];
    }

    /**
     * คำนวณรายงานกำไรขั้นต้น (Gross Profit Report)
     * รวมทั้งกำไรจากขายหน้าร้าน (POS) และกำไรจากงานซ่อม (Repair Services)
     */
    public function getGrossProfitReport(int $tenantId, string $fromDate, string $toDate): array
    {
        $from = $fromDate . ' 00:00:00';
        $to   = $toDate . ' 23:59:59';

        // 1. กำไรจาก POS
        $posItems = $this->db->table('pos_order_items')
                             ->select('pos_order_items.qty, pos_order_items.cost_price, pos_order_items.unit_price, pos_order_items.total_price')
                             ->join('pos_orders', 'pos_orders.id = pos_order_items.pos_order_id')
                             ->where('pos_orders.tenant_id', $tenantId)
                             ->where('pos_orders.payment_status', 'paid')
                             ->where('pos_orders.created_at >=', $from)
                             ->where('pos_orders.created_at <=', $to)
                             ->get()
                             ->getResultArray();

        $posRevenue = 0.00;
        $posCost    = 0.00;
        foreach ($posItems as $item) {
            $posRevenue += (float)$item['total_price'];
            $posCost    += ((float)$item['cost_price'] * (float)$item['qty']);
        }
        $posMargin = round($posRevenue - $posCost, 2);

        // 2. กำไรจากงานซ่อม (Repair Jobs)
        $repairJobs = $this->db->table('repair_jobs')
                               ->where('tenant_id', $tenantId)
                               ->where('payment_status', 'paid')
                               ->where('created_at >=', $from)
                               ->where('created_at <=', $to)
                               ->get()
                               ->getResultArray();

        $repairRevenue   = 0.00;
        $repairPartsCost = 0.00;

        foreach ($repairJobs as $job) {
            $repairRevenue += (float)$job['net_total'];

            $parts = $this->db->table('repair_items')
                              ->where('repair_job_id', $job['id'])
                              ->where('item_type', 'part')
                              ->where('status', 'approved')
                              ->get()
                              ->getResultArray();

            foreach ($parts as $p) {
                $repairPartsCost += ((float)$p['cost_price'] * (float)$p['qty']);
            }
        }
        $repairMargin = round($repairRevenue - $repairPartsCost, 2);

        $totalRevenue = round($posRevenue + $repairRevenue, 2);
        $totalCost    = round($posCost + $repairPartsCost, 2);
        $totalMargin  = round($posMargin + $repairMargin, 2);
        $marginPct    = $totalRevenue > 0 ? round(($totalMargin / $totalRevenue) * 100, 2) : 0.00;

        return [
            'from_date'      => $fromDate,
            'to_date'        => $toDate,
            'pos_revenue'    => $posRevenue,
            'pos_cost'       => $posCost,
            'pos_margin'     => $posMargin,
            'repair_revenue' => $repairRevenue,
            'repair_cost'    => $repairPartsCost,
            'repair_margin'  => $repairMargin,
            'total_revenue'  => $totalRevenue,
            'total_cost'     => $totalCost,
            'total_margin'   => $totalMargin,
            'margin_percent' => $marginPct,
        ];
    }

    /**
     * ดึงรายการสินค้าที่มีสต็อกเหลือน้อยกว่าจุดสั่งซื้อ (Low Stock Alerts)
     */
    public function getLowStockAlerts(int $tenantId, float $threshold = 10.00): array
    {
        $products = $this->db->table('products')
                             ->where('tenant_id', $tenantId)
                             ->where('track_stock', 1) // เฉพาะสินค้าที่นับสต็อก
                             ->where('is_active', 1)
                             ->get()
                             ->getResultArray();

        $alerts = [];
        foreach ($products as $prod) {
            $row = $this->db->table('stock_transactions')
                            ->selectSum('qty')
                            ->where('tenant_id', $tenantId)
                            ->where('product_id', $prod['id'])
                            ->get()
                            ->getRowArray();

            $currentStock = (float)($row['qty'] ?? 0.00);
            if ($currentStock <= $threshold) {
                $alerts[] = [
                    'product_id'    => (int)$prod['id'],
                    'sku'           => $prod['sku'],
                    'name'          => $prod['name'],
                    'cost_price'    => (float)$prod['cost_price'],
                    'sell_price'    => (float)$prod['sell_price'],
                    'current_stock' => $currentStock,
                    'threshold'     => $threshold,
                ];
            }
        }

        return $alerts;
    }

    /**
     * รายงานลูกหนี้ค้างชำระ (Outstanding Customer Debt Report)
     */
    public function getOutstandingDebtReport(int $tenantId): array
    {
        $wallets = $this->db->table('wallets')
                            ->where('tenant_id', $tenantId)
                            ->where('party_type', 'customer')
                            ->where('debt_balance >', 0)
                            ->get()
                            ->getResultArray();

        $report = [];
        foreach ($wallets as $w) {
            $customer = $this->db->table('peoples')->where('id', $w['party_id'])->get()->getRowArray();
            $report[] = [
                'wallet_id'      => (int)$w['id'],
                'customer_id'    => (int)$w['party_id'],
                'customer_name'  => $customer ? ($customer['name'] ?? 'ลูกค้า ID: ' . $w['party_id']) : 'ลูกค้า ID: ' . $w['party_id'],
                'customer_phone' => $customer['phone'] ?? '-',
                'debt_balance'   => (float)$w['debt_balance'],
                'credit_limit'   => (float)$w['credit_limit'],
                'updated_at'     => $w['updated_at'],
            ];
        }

        return $report;
    }
}
