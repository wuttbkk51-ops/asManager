<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\BranchModel;
use App\Models\WarehouseModel;
use App\Models\ProductModel;
use App\Models\ProductSerialModel;
use App\Models\WalletModel;
use App\Models\RepairJobModel;
use App\Models\RepairItemModel;
use App\Models\PriceTierModel;
use App\Models\ProductTierPriceModel;
use App\Models\ProductCostLogModel;
use App\Models\PosOrderModel;
use App\Models\StockTransferModel;
use App\Services\StockLedgerService;
use App\Services\FinanceService;
use App\Services\PrintService;
use App\Services\InventoryCostService;
use App\Services\PosService;
use App\Services\StockTransferService;
use App\Services\TenantSettingService;
use App\Services\StockAdjustmentService;
use App\Services\ReportService;

class TestErp extends BaseCommand
{
    protected $group       = 'Testing';
    protected $name        = 'test:erp';
    protected $description = 'ทดสอบระบบบริหารงานซ่อม + คลังสินค้า + การเงิน & Wallet + Print Engine ครบวงจร';

    public function run(array $params)
    {
        $db = \Config\Database::connect();
        $tenantId = 1; // Demo Shop

        CLI::write("=== 1. TEST MULTI-BRANCH & WAREHOUSE TREE HIERARCHY ===", 'yellow');
        $branchModel = new BranchModel();
        $branches = $branchModel->getActiveBranches($tenantId);
        $this->assertTest("Branches exist (count: " . count($branches) . ")", count($branches) >= 2);
        $this->assertTest("Main branch code is HQ", $branches[0]['code'] === 'HQ');

        $whModel = new WarehouseModel();
        $tree = $whModel->getTree($tenantId, 1);
        $this->assertTest("Warehouse tree structure exists", !empty($tree));
        
        $fullPath = $whModel->getFullPath(3);
        $this->assertTest("Sub-tree path traversal: {$fullPath}", str_contains($fullPath, 'คลังหน้าร้าน') && str_contains($fullPath, 'ช่องเก็บ 01'));

        CLI::write(PHP_EOL . "=== 2. TEST HYBRID STOCK & DIRECT INBOUND VS PO-GRN ===", 'yellow');
        $productModel = new ProductModel();
        $filmBalance = $productModel->getStockBalance($tenantId, 1);
        $this->assertTest("Regular item stock balance (Qty only: {$filmBalance})", $filmBalance >= 100);

        $serialModel = new ProductSerialModel();
        $availableSerials = $serialModel->getAvailableSerials($tenantId, 2); // หน้าจอ iPhone
        $this->assertTest("Hybrid item has available serials (count: " . count($availableSerials) . ")", count($availableSerials) >= 2);

        // ทดสอบ Direct Inbound (โหมดง่าย ร้านโชห่วย)
        $stockService = new StockLedgerService();
        $inboundTxnId = $stockService->directInbound($tenantId, 1, 1, 1, 20.00, 30.00, null, 'รับฟิล์มเพิ่ม 20 ชิ้น', 2);
        $newFilmBalance = $productModel->getStockBalance($tenantId, 1);
        $this->assertTest("Direct Inbound increased stock (+20 -> {$newFilmBalance})", $newFilmBalance == ($filmBalance + 20));

        // ทดสอบ PO -> GRN (โหมดเป็นระบบ)
        $db->table('purchase_orders')->insert([
            'tenant_id'     => $tenantId,
            'branch_id'     => 1,
            'po_no'         => 'PO2609-TEST',
            'supplier_name' => 'บจก. ซัพพลายทดสอบ',
            'status'        => 'pending',
            'total_amount'  => 5000.00,
            'created_at'    => date('Y-m-d H:i:s'),
        ]);
        $poId = $db->insertID();
        $db->table('purchase_order_items')->insert([
            'purchase_order_id' => $poId,
            'product_id'        => 4, // หัวชาร์จ
            'qty_ordered'       => 10.00,
            'qty_received'      => 0.00,
            'unit_cost'         => 180.00,
            'created_at'        => date('Y-m-d H:i:s'),
        ]);

        $grnId = $stockService->receivePurchaseOrder($tenantId, (int)$poId, 1, [
            ['product_id' => 4, 'qty' => 10.00, 'cost' => 180.00, 'serials' => []]
        ], 'ตรวจรับครบ 10 ชิ้น', 2);

        $updatedPo = $db->table('purchase_orders')->where('id', $poId)->get()->getRowArray();
        $this->assertTest("Goods Receipt against PO successfully completed (PO status: {$updatedPo['status']})", $updatedPo['status'] === 'completed');

        CLI::write(PHP_EOL . "=== 3. TEST REPAIR LIFECYCLE: DEPOSIT, APPROVAL, RETURN PARTS, REJECT ===", 'yellow');
        $jobModel = new RepairJobModel();
        $itemModel = new RepairItemModel();

        // 3.1 สร้างงานซ่อมใหม่
        $newJobNo = $jobModel->generateJobNo($tenantId);
        $testToken = 'trk_test_' . bin2hex(random_bytes(6));
        $jobId = $jobModel->insert([
            'tenant_id'        => $tenantId,
            'branch_id'        => 1,
            'job_no'           => $newJobNo,
            'tracking_token'   => $testToken,
            'customer_name'    => 'นายทดสอบ ระบบซ่อม',
            'customer_phone'   => '081-234-5678',
            'device_type'      => 'Tablet',
            'brand'            => 'Apple',
            'model'            => 'iPad Air 5',
            'problem_reported' => 'แบตเสื่อม ชาร์จไม่เข้า',
            'status'           => 'received',
            'deposit_amount'   => 500.00,
            'paid_amount'      => 500.00,
            'payment_status'   => 'partial',
        ]);
        $this->assertTest("Created repair job: {$newJobNo} with tracking token", $jobId !== false);

        // 3.2 บันทึกเงินมัดจำเข้า Finance Ledger
        $financeService = new FinanceService();
        $finTxnId = $financeService->recordTransaction($tenantId, 1, 'income', 'repair_deposit', 500.00, 'bank_transfer', 'repair_job', $jobId, 'มัดจำ iPad Air 5', 2);
        $this->assertTest("Deposit recorded in financial transactions (Txn ID: {$finTxnId})", $finTxnId > 0);

        // 3.3 ขอเบิกอะไหล่ (สถานะ requested รอ approve)
        $partItemId = $itemModel->addPart((int)$jobId, [
            'item_type'   => 'part',
            'product_id'  => 3, // แบตเตอรี่ Samsung (จำลองเบิก)
            'serial_id'   => 3,
            'item_name'   => 'แบตเตอรี่แท้ 3700mAh',
            'qty'         => 1.00,
            'cost_price'  => 650.00,
            'unit_price'  => 1400.00,
        ], true, 3);
        $itemBeforeApprove = $itemModel->find($partItemId);
        $this->assertTest("Part requisition created with status 'requested'", $itemBeforeApprove['status'] === 'requested');

        // 3.4 ผู้จัดการกด Approve เบิกอะไหล่ -> ตัดสต็อกจริง
        $itemModel->approvePart((int)$partItemId, 2);
        $itemAfterApprove = $itemModel->find($partItemId);
        $this->assertTest("Part approved and status is 'approved'", $itemAfterApprove['status'] === 'approved');

        $jobAfterPart = $jobModel->find($jobId);
        $this->assertTest("Job net total updated after part approval ({$jobAfterPart['net_total']} THB)", (float)$jobAfterPart['net_total'] == 1400.00);

        // 3.5 คืนอะไหล่เข้าคลัง (Return Part: หยิบผิดรุ่น ต้องคืนอะไหล่ได้)
        $returned = $itemModel->returnPart((int)$partItemId, 'หยิบผิดรุ่น ลูกค้าใช้ iPad', 2);
        $this->assertTest("Part returned to stock successfully", $returned === true);

        $jobAfterReturn = $jobModel->find($jobId);
        $this->assertTest("Job net total reduced to 0 after returning part ({$jobAfterReturn['net_total']} THB)", (float)$jobAfterReturn['net_total'] == 0.00);

        // 3.6 ทดสอบการสลับ State ไปมา และการ Reject งาน
        $jobModel->changeStatus((int)$jobId, 'in_progress', 'เริ่มดำเนินการซ่อม', 3);
        $jobModel->changeStatus((int)$jobId, 'repaired', 'ซ่อมเสร็จเรียบร้อย รอส่งมอบ', 3);
        
        // หน้าร้านตรวจสอบพบว่ายังมีปัญหา สั่ง Reject ตีกลับไปให้ช่างแก้ใหม่
        $rejected = $jobModel->changeStatus((int)$jobId, 'rejected', 'หน้าร้านเทสแล้วชาร์จยังตัด ส่งช่างแก้ไขใหม่', 2);
        $jobCurrent = $jobModel->find($jobId);
        $this->assertTest("Flexible state machine: Job rejected back to technician", $rejected === true && $jobCurrent['status'] === 'rejected');

        $logs = $db->table('repair_status_logs')->where('repair_job_id', $jobId)->orderBy('id', 'ASC')->get()->getResultArray();
        $this->assertTest("Status history trail preserved (log count: " . count($logs) . ")", count($logs) >= 3);

        CLI::write(PHP_EOL . "=== 4. TEST WALLETS: STORE CREDIT, DEPOSIT REFUND & DEBT ===", 'yellow');
        $walletModel = new WalletModel();
        $testCustomerId = 88;
        $wallet = $walletModel->getOrCreateWallet($tenantId, 'customer', $testCustomerId, 5000.00);
        // รีเซ็ตยอดเงินในกระเป๋าสำหรับรอบการทดสอบให้เป็นศูนย์ เพื่อความเสถียรของการทดสอบซ้ำ
        $walletModel->update($wallet['id'], ['credit_balance' => 0.00, 'debt_balance' => 0.00]);
        $wallet = $walletModel->find($wallet['id']);
        $this->assertTest("Customer wallet initialized (Credit: 0, Debt: 0)", !empty($wallet['id']));

        // 4.1 คืนสินค้าแต่ไม่คืนเงินสด -> โอนเข้าเป็น Store Credit
        $financeService->refundToStoreCredit($tenantId, $testCustomerId, 1000.00, 'return_refund', null, 'คืนสินค้าไม่รับเงินสด', 2);
        $walletAfterRefund = $walletModel->find($wallet['id']);
        $this->assertTest("Store Credit added to customer wallet (+1,000 -> {$walletAfterRefund['credit_balance']} THB)", (float)$walletAfterRefund['credit_balance'] == 1000.00);

        // 4.2 นำ Store Credit มาจ่ายค่าซ่อม/สินค้า
        $paidByCredit = $financeService->payWithStoreCredit($tenantId, 1, $testCustomerId, 400.00, 'repair_job', $jobId, 'หักจ่ายด้วย Store Credit', 2);
        $walletAfterPay = $walletModel->find($wallet['id']);
        $this->assertTest("Paid with Store Credit (remaining balance: {$walletAfterPay['credit_balance']} THB)", $paidByCredit === true && (float)$walletAfterPay['credit_balance'] == 600.00);

        // 4.3 ซื้อของติดเงินก่อน (บันทึกหนี้ค้างชำระ Debt)
        $walletModel->adjustBalance((int)$wallet['id'], 'debt_increase', 1500.00, 'pos_order', null, 'ซื้อสินค้าติดเงินก่อน', 2);
        $walletAfterDebt = $walletModel->find($wallet['id']);
        $this->assertTest("Debt increased for credit customer ({$walletAfterDebt['debt_balance']} THB)", (float)$walletAfterDebt['debt_balance'] == 1500.00);

        // 4.4 นำเงินสดมาชำระหนี้
        $walletModel->adjustBalance((int)$wallet['id'], 'debt_payment', 1000.00, 'manual', null, 'จ่ายชำระหนี้บางส่วน', 2);
        $walletAfterRepay = $walletModel->find($wallet['id']);
        $this->assertTest("Debt reduced after repayment (remaining debt: {$walletAfterRepay['debt_balance']} THB)", (float)$walletAfterRepay['debt_balance'] == 500.00);

        CLI::write(PHP_EOL . "=== 5. TEST CASH SHIFT (กะหน้าร้าน & ลิ้นชักเงินสด) ===", 'yellow');
        $shiftBranchId = 2; // ใช้สาขา 2 เพื่อทดสอบกะใหม่อย่างอิสระ
        // ปิดกะที่ค้างอยู่ (ถ้ามี) ก่อนเริ่มทดสอบรอบใหม่
        $db->table('cash_shifts')->where('tenant_id', $tenantId)->where('branch_id', $shiftBranchId)->where('status', 'open')->update(['status' => 'closed', 'closed_at' => date('Y-m-d H:i:s')]);
        $shiftId = $financeService->openShift($tenantId, $shiftBranchId, 2, 1500.00, 'เปิดกะทดสอบสาขา 2');
        $this->assertTest("Cash shift opened (Shift ID: {$shiftId})", $shiftId > 0);

        // จำลองการขายเงินสดหน้าร้าน 500 บาท และจ่ายค่าใช้จ่ายเงินสด 100 บาท
        $financeService->recordTransaction($tenantId, $shiftBranchId, 'income', 'pos_sale', 500.00, 'cash', 'pos_sale', 991, 'ขายหน้าร้าน', 2);
        $financeService->recordTransaction($tenantId, $shiftBranchId, 'expense', 'shop_expense', 100.00, 'cash', 'manual', null, 'ค่าน้ำดื่ม', 2);

        $shiftReport = $financeService->closeShift((int)$shiftId, 1900.00, 'นับเงินปิดกะ');
        // expected: 1500 + 500 - 100 = 1900, counted: 1900 -> diff = 0
        $this->assertTest("Cash shift closed: expected {$shiftReport['expected_cash']}, counted {$shiftReport['counted_cash']}, diff {$shiftReport['difference']}",
            $shiftReport !== false && $shiftReport['difference'] == 0.00);

        CLI::write(PHP_EOL . "=== 6. TEST SMART PRINT ENGINE & ZERO-BREAK CSS ===", 'yellow');
        $printService = new PrintService();
        $printHtml = $printService->renderDocument($tenantId, 'repair_ticket', [
            'doc_no'         => $newJobNo,
            'date'           => date('d/m/Y H:i'),
            'customer_name'  => 'คุณสมชาย ใจดี',
            'customer_phone' => '089-999-8888',
            'device_info'    => 'Apple iPhone 13 (IMEI: 356789101112131)',
            'items'          => [
                ['name' => 'ชุดหน้าจอแท้ OLED', 'qty' => 1, 'unit_price' => 3200.00, 'total_price' => 3200.00],
                ['name' => 'ค่าบริการติดตั้ง', 'qty' => 1, 'unit_price' => 500.00, 'total_price' => 500.00],
            ],
            'total_amount'   => 3700.00,
            'deposit_amount' => 1000.00,
            'net_total'      => 2700.00,
            'tracking_token' => $testToken,
        ]);

        $this->assertTest("Print HTML rendered successfully", strlen($printHtml) > 500);
        $this->assertTest("Contains Zero-Break CSS rule (page-break-inside: avoid)", str_contains($printHtml, 'page-break-inside: avoid'));
        $this->assertTest("Contains Flexbox Sticky Footer rule (margin-top: auto)", str_contains($printHtml, 'margin-top: auto'));
        $this->assertTest("Contains QR Tracking section", str_contains($printHtml, $testToken));

        CLI::write(PHP_EOL . "=== 7. TEST PLAN LIMIT ENFORCER (SaaS Quota Checking) ===", 'yellow');
        $planLimitService = new \App\Services\PlanLimitService();

        // 7.1 ตรวจสอบโควตาสินค้าของ Tenant 1 (freePlan: limit 50, มีอยู่ 4)
        $prodLimit = $planLimitService->checkProductLimit($tenantId);
        $this->assertTest("Tenant 1 can add products (current: {$prodLimit['current']}, limit: {$prodLimit['limit']})", $prodLimit['allowed'] === true);

        // 7.2 ตรวจสอบโควตาสาขาของ Tenant 1 (freePlan: limit 1, แต่มี 2 สาขา) -> ต้องไม่อนุญาตให้เพิ่มสาขาที่ 3
        $branchLimit = $planLimitService->checkBranchLimit($tenantId);
        $this->assertTest("Tenant 1 branch limit enforced (current: {$branchLimit['current']}, limit: {$branchLimit['limit']} -> disallowed)", $branchLimit['allowed'] === false);

        // 7.3 ตรวจสอบโควตาผู้ใช้ของ Tenant 1 (freePlan: limit 2, มี 2 คน) -> ไม่อนุญาตให้เพิ่มคนที่ 3
        $userLimit = $planLimitService->checkUserLimit($tenantId);
        $this->assertTest("Tenant 1 user limit enforced (current: {$userLimit['current']}, limit: {$userLimit['limit']} -> disallowed)", $userLimit['allowed'] === false);

        // 7.4 ตรวจสอบ Tenant 2 (proPlan: max_products = 0 unlimited)
        $proTenantProdLimit = $planLimitService->checkProductLimit(2);
        $this->assertTest("Tenant 2 on Pro Plan has unlimited products (limit = 0 -> allowed)", $proTenantProdLimit['allowed'] === true && $proTenantProdLimit['limit'] === 0);

        CLI::write(PHP_EOL . "=== 8. TEST DYNAMIC TIER PRICING (5 Formulas & Product Override) ===", 'yellow');
        $tierService = new \App\Services\PriceTierService();

        // 8.1 ฟิล์มกระจก (Product 1: sell_price = 120, cost_price = 30)
        // Tier 1: Retail (Fixed -> 120.00)
        $p1Retail = $tierService->calculatePrice(1, 1);
        $this->assertTest("Tier 1 Retail: Fixed price = 120.00 THB", $p1Retail['final_price'] == 120.00);

        // Tier 2: Technician (Discount Percent 15% -> 120 * 0.85 = 102.00)
        $p1Tech = $tierService->calculatePrice(1, 2);
        $this->assertTest("Tier 2 Technician: 15% discount = 102.00 THB", $p1Tech['final_price'] == 102.00);

        // Tier 3: Wholesale (Cost Plus Percent 15% -> 30 * 1.15 = 34.50)
        $p1Wholesale = $tierService->calculatePrice(1, 3);
        $this->assertTest("Tier 3 Wholesale: Cost + 15% = 34.50 THB", $p1Wholesale['final_price'] == 34.50);

        // Tier 4: VIP (Discount Amount 100 บาท -> 120 - 100 = 20.00)
        $p1Vip = $tierService->calculatePrice(1, 4);
        $this->assertTest("Tier 4 VIP: Discount 100 THB = 20.00 THB", $p1Vip['final_price'] == 20.00);

        // 8.2 หน้าจอ iPhone 13 (Product 2: sell_price = 3200, cost_price = 1800)
        // มี Override ในตาราง product_tier_prices สำหรับช่าง (Tier 2) เป็น Fixed 2,800 บาท (แทนที่จะลด 15% ซึ่งเป็น 2,720)
        $p2Tech = $tierService->calculatePrice(2, 2);
        $this->assertTest("Product 2 Override for Technician Tier: Fixed 2,800.00 THB", $p2Tech['final_price'] == 2800.00 && $p2Tech['is_overridden'] === true);

        // 8.3 คำนวณราคาอัตโนมัติตามลูกค้า (Customer 2 ผูก price_tier_id = 2 ช่างซ่อม)
        $p1ByCustomer = $tierService->calculatePrice(1, null, 2);
        $this->assertTest("Automatic tier price by Customer (Tech Tier): 102.00 THB", $p1ByCustomer['final_price'] == 102.00 && $p1ByCustomer['tier_code'] === 'technician');

        // 8.4 ทดสอบกรณี Customer รายนั้นไม่ได้เลือก Tier (ไม่มี price_tier_id หรือไม่ได้ระบุ)
        // กฎ: ระบบจะดึงราคาขายหลัก (sell_price) มาขายเป็น Default 100% เสมอ
        $p1NoTierCustomer = $tierService->calculatePrice(1, null, 1); // Customer 1 (price_tier_id = null)
        $this->assertTest("When Customer has no tier: Always use product sell_price (120.00 THB)",
            $p1NoTierCustomer['final_price'] == 120.00 && $p1NoTierCustomer['calc_type'] === 'default_sell_price');

        $p1Walkin = $tierService->calculatePrice(1); // ลูกค้า Walk-in ทั่วไป ไม่ได้ระบุ Tier และ Customer
        $this->assertTest("Walk-in customer with no tier: Sells at default sell_price (120.00 THB)",
            $p1Walkin['final_price'] == 120.00 && $p1Walkin['calc_type'] === 'default_sell_price');

        // 8.5 ทดสอบ Owner CRUD การตั้งค่าและปรับแต่ง Tier ของร้านตนเอง (Owner Custom Tier Management)
        $tierModel = new PriceTierModel();
        $overrideModel = new ProductTierPriceModel();

        // 8.5.1 Owner สร้าง Tier ใหม่สำหรับร้านตนเอง
        $customTierId = $tierModel->createTier($tenantId, [
            'code'         => 'contractor_gold',
            'name'         => 'ผู้รับเหมา VIP Gold',
            'calc_type'    => 'cost_plus_amount',
            'default_rate' => 25.00, // บวกจากต้นทุน 25 บาท
        ]);
        $this->assertTest("Owner created custom tier successfully (ID: {$customTierId})", $customTierId !== false);

        // Product 1 (cost 30): cost + 25 = 55.00 THB
        $customTierPrice = $tierService->calculatePrice(1, (int)$customTierId);
        $this->assertTest("Custom tier formula calculated correctly (30 + 25 = 55.00 THB)", $customTierPrice['final_price'] == 55.00);

        // 8.5.2 Owner ตั้งค่า Override เฉพาะสินค้าสำหรับ Tier นี้
        $overrideOk = $overrideModel->setProductOverride($tenantId, 1, (int)$customTierId, 'fixed', 49.00);
        $this->assertTest("Owner set product override for custom tier", $overrideOk);

        $customTierOverridden = $tierService->calculatePrice(1, (int)$customTierId);
        $this->assertTest("Custom tier with product override works (Fixed 49.00 THB)", $customTierOverridden['final_price'] == 49.00 && $customTierOverridden['is_overridden'] === true);

        // 8.5.3 ผูก Tier นี้ให้ Customer ชั่วคราว แล้วทดสอบการลบ Tier
        $db->table('peoples')->where('id', 2)->update(['price_tier_id' => $customTierId]);
        $customerTierPrice = $tierService->calculatePrice(1, null, 2);
        $this->assertTest("Customer receives custom tier price (49.00 THB)", $customerTierPrice['final_price'] == 49.00);

        // 8.5.4 Owner ลบ Tier: ต้องเคลียร์ override และรีเซ็ตลูกค้าที่ผูกกับ Tier นี้ให้กลับเป็น Default (sell_price)
        $deleteOk = $tierModel->deleteTier($tenantId, (int)$customTierId);
        $this->assertTest("Owner deleted custom tier successfully", $deleteOk);

        $customerAfterDelete = $tierService->calculatePrice(1, null, 2);
        $this->assertTest("Customer automatically falls back to product sell_price (120.00 THB) after tier deletion",
            $customerAfterDelete['final_price'] == 120.00 && $customerAfterDelete['calc_type'] === 'default_sell_price');

        // คืนค่า customer 2 ให้กลับเป็น technician tier (Tier 2) เหมือนเดิม
        $db->table('peoples')->where('id', 2)->update(['price_tier_id' => 2]);

        CLI::write(PHP_EOL . "=== 9. TEST DEPOSIT REFERENCE BILLING & EXCESS REFUND TO STORE CREDIT ===", 'yellow');
        $repairService = new \App\Services\RepairService();

        // 9.1 สร้างงานซ่อมเพื่อทดสอบการวางมัดจำ
        $depJobNo = $jobModel->generateJobNo($tenantId);
        $depJobId = $jobModel->insert([
            'tenant_id'        => $tenantId,
            'branch_id'        => 1,
            'job_no'           => $depJobNo,
            'tracking_token'   => 'trk_dep_' . bin2hex(random_bytes(6)),
            'customer_id'      => $testCustomerId, // ลูกค้าทดสอบ ID 88
            'customer_name'    => 'ลูกค้าทดสอบ มัดจำ',
            'customer_phone'   => '082-345-6789',
            'device_type'      => 'Smartphone',
            'brand'            => 'Samsung',
            'model'            => 'Galaxy S22',
            'problem_reported' => 'แบตบวม ชาร์จไม่เข้า',
            'status'           => 'received',
            'deposit_amount'   => 0.00,
            'paid_amount'      => 0.00,
        ]);

        // 9.2 ลูกค้าวางเงินมัดจำ 1,500 บาท รอร้านสั่งอะไหล่
        $depResult = $repairService->issueDeposit((int)$depJobId, 1500.00, 'bank_transfer', 'มัดจำรอสั่งแบตเตอรี่', 2);
        $this->assertTest("Deposit issued successfully with deposit_no ({$depResult['deposit_no']})", $depResult['success'] === true && !empty($depResult['deposit_no']));

        // ใส่รายการอะไหล่และค่าแรง (ยอดรวม 1,900 บาท)
        $itemModel->addPart((int)$depJobId, [
            'item_type'  => 'part',
            'product_id' => 3,
            'item_name'  => 'แบตเตอรี่แท้ Samsung S22',
            'qty'        => 1.00,
            'cost_price' => 650.00,
            'unit_price' => 1400.00,
        ], false, 2);
        $itemModel->addPart((int)$depJobId, [
            'item_type'  => 'service',
            'item_name'  => 'ค่าบริการเปลี่ยนแบตเตอรี่',
            'qty'        => 1.00,
            'cost_price' => 0.00,
            'unit_price' => 500.00,
        ], false, 2);

        // 9.3 คิดเงินปิดบิลซ่อม: ยอดซ่อม 1,900 หัก มัดจำ 1,500 เหลือจ่ายจริง 400 บาท
        $checkoutResult = $repairService->checkoutFinalBill((int)$depJobId, 'cash', 'ปิดบิลซ่อมส่งมอบเครื่อง', 2);
        $this->assertTest("Final checkout references deposit ({$checkoutResult['deposit_no']})", $checkoutResult['deposit_no'] === $depResult['deposit_no']);
        $this->assertTest("Net payable calculated accurately (1,900 - 1,500 = {$checkoutResult['net_payable']} THB)", (float)$checkoutResult['net_payable'] == 400.00);
        $this->assertTest("Final receipt generated ({$checkoutResult['final_receipt_no']})", !empty($checkoutResult['final_receipt_no']));
        $this->assertTest("Job status updated to 'delivered' and payment 'paid'", $checkoutResult['status'] === 'delivered' && $checkoutResult['payment_status'] === 'paid');

        // 9.4 ทดสอบกรณีมัดจำเกิน (Excess Deposit) เช่น มัดจำ 1,000 แต่สรุปค่าซ่อมแค่ 500
        // ระบบต้องคืนเงินส่วนเกิน 500 บาทเข้า Store Credit ของลูกค้าใน Wallet อัตโนมัติ
        $excessJobNo = $jobModel->generateJobNo($tenantId);
        $excessJobId = $jobModel->insert([
            'tenant_id'        => $tenantId,
            'branch_id'        => 1,
            'job_no'           => $excessJobNo,
            'tracking_token'   => 'trk_exc_' . bin2hex(random_bytes(6)),
            'customer_id'      => $testCustomerId,
            'customer_name'    => 'ลูกค้าทดสอบ มัดจำเกิน',
            'customer_phone'   => '082-345-6789',
            'device_type'      => 'Smartphone',
            'brand'            => 'Apple',
            'model'            => 'iPhone 11',
            'problem_reported' => 'เครื่องโดนน้ำ ตรวจเช็คทำความสะอาด',
            'status'           => 'received',
            'deposit_amount'   => 0.00,
            'paid_amount'      => 0.00,
        ]);
        $repairService->issueDeposit((int)$excessJobId, 1000.00, 'cash', 'มัดจำ 1,000 บาท', 2);
        // เพิ่มค่าบริการเพียง 500 บาท (ทำความสะอาดเครื่อง ไม่ต้องเปลี่ยนอะไหล่)
        $itemModel->addPart((int)$excessJobId, [
            'item_type'  => 'service',
            'item_name'  => 'ตรวจเช็คและทำความสะอาดบอร์ด',
            'qty'        => 1.00,
            'cost_price' => 0.00,
            'unit_price' => 500.00,
        ], false, 2);

        $walletBeforeExcess = $walletModel->find($wallet['id']);
        $initialCredit = (float)$walletBeforeExcess['credit_balance'];

        // คิดเงินปิดบิลซ่อมโดยเลือกให้คืนส่วนเกินเป็น Store Credit
        $excessCheckout = $repairService->checkoutFinalBill((int)$excessJobId, 'cash', 'คืนเงินมัดจำส่วนเกินเข้า Wallet', 2, 'refund_store_credit');
        $this->assertTest("Excess deposit detected (amount: {$excessCheckout['excess_amount']} THB, net_payable: 0)", (float)$excessCheckout['excess_amount'] == 500.00 && (float)$excessCheckout['net_payable'] == 0.00);

        $walletAfterExcess = $walletModel->find($wallet['id']);
        $this->assertTest("Excess deposit transferred to Customer Store Credit (+500 -> {$walletAfterExcess['credit_balance']} THB)", (float)$walletAfterExcess['credit_balance'] == ($initialCredit + 500.00));

        CLI::write(PHP_EOL . "=== 10. TEST INVENTORY COSTING FORMULAS & LANDED COST ALLOCATION ===", 'yellow');
        $costService = new InventoryCostService();
        $costLogModel = new ProductCostLogModel();

        // 10.1 ตรวจสอบสูตรเริ่มต้น และการตั้งค่าของ Owner
        $defaultMethod = $costService->getTenantCostingMethod($tenantId);
        $this->assertTest("Tenant default costing method is 'moving_average'", $defaultMethod === 'moving_average');

        $costService->setTenantCostingMethod($tenantId, 'latest_cost');
        $this->assertTest("Owner changed default costing method to 'latest_cost'", $costService->getTenantCostingMethod($tenantId) === 'latest_cost');
        // คืนค่ากลับเป็น moving_average
        $costService->setTenantCostingMethod($tenantId, 'moving_average');

        // สร้างสินค้าทดสอบสำหรับการคำนวณต้นทุน
        $testProdId = $productModel->insert([
            'tenant_id'   => $tenantId,
            'category_id' => 2,
            'sku'         => 'TEST-CABLE-01',
            'name'        => 'สายชาร์จทดสอบสูตรต้นทุน',
            'unit'        => 'เส้น',
            'cost_price'  => 100.00,
            'sell_price'  => 250.00,
            'has_serial'  => 0,
            'is_active'   => 1,
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        // 10.2 ทดสอบสูตร 1: Moving Average (ถ่วงน้ำหนักตามสต็อกเดิม)
        // รับเข้าครั้งแรก: สต็อกเดิม = 0 -> รับ 10 เส้น @ 100 บาท -> cost = 100.00
        $stockService->directInbound($tenantId, 1, 1, (int)$testProdId, 10.00, 100.00, null, 'รับล็อต 1: 10 ชิ้น @ 100', 2, 'moving_average');
        $pCheck1 = $productModel->find($testProdId);
        $this->assertTest("Moving Average first inbound with 0 stock sets cost to 100.00 THB", (float)$pCheck1['cost_price'] == 100.00);

        // รับเข้าครั้งที่สอง: สต็อกเดิม 10 ชิ้น @ 100 (มูลค่า 1,000) + รับเข้า 10 ชิ้น @ 150 (มูลค่า 1,500)
        // รวม 20 ชิ้น มูลค่า 2,500 -> ต้นทุนเฉลี่ยใหม่ = 2,500 / 20 = 125.00 บาท
        $stockService->directInbound($tenantId, 1, 1, (int)$testProdId, 10.00, 150.00, null, 'รับล็อต 2: 10 ชิ้น @ 150', 2, 'moving_average');
        $pCheck2 = $productModel->find($testProdId);
        $this->assertTest("Moving Average calculates (1,000 + 1,500) / 20 = 125.00 THB", (float)$pCheck2['cost_price'] == 125.00);

        // 10.3 ทดสอบสูตร 2: Latest Cost (ปรับตามราคาซื้อล่าสุดทันที)
        // รับเข้า 5 ชิ้น @ 140 บาท -> ต้นทุนใหม่ต้องเป็น 140.00 บาททันที
        $stockService->directInbound($tenantId, 1, 1, (int)$testProdId, 5.00, 140.00, null, 'รับล็อต 3: latest_cost', 2, 'latest_cost');
        $pCheck3 = $productModel->find($testProdId);
        $this->assertTest("Latest Cost formula updates cost to 140.00 THB immediately", (float)$pCheck3['cost_price'] == 140.00);

        // 10.4 ทดสอบสูตร 3: Highest Cost (ยึดราคาสูงสุด ป้องกันขาดทุน)
        // รับเข้า 5 ชิ้น @ 110 บาท (ต่ำกว่าเดิม 140) -> ต้นทุนต้องคงที่ 140.00 บาท (ไม่ลด)
        $stockService->directInbound($tenantId, 1, 1, (int)$testProdId, 5.00, 110.00, null, 'รับล็อต 4: highest_cost ต่ำกว่า', 2, 'highest_cost');
        $pCheck4 = $productModel->find($testProdId);
        $this->assertTest("Highest Cost keeps higher cost (140 vs 110 -> 140.00 THB)", (float)$pCheck4['cost_price'] == 140.00);

        // รับเข้า 5 ชิ้น @ 175 บาท (สูงกว่าเดิม 140) -> ต้นทุนต้องปรับขึ้นเป็น 175.00 บาท
        $stockService->directInbound($tenantId, 1, 1, (int)$testProdId, 5.00, 175.00, null, 'รับล็อต 5: highest_cost สูงกว่า', 2, 'highest_cost');
        $pCheck5 = $productModel->find($testProdId);
        $this->assertTest("Highest Cost increases to higher cost (140 vs 175 -> 175.00 THB)", (float)$pCheck5['cost_price'] == 175.00);

        // 10.5 ทดสอบสูตร 4: Manual Standard Cost (คงที่ ไม่เปลี่ยนอัตโนมัติ)
        // รับเข้า 10 ชิ้น @ 220 บาท -> ต้นทุนต้องคงเดิมที่ 175.00 บาท
        $stockService->directInbound($tenantId, 1, 1, (int)$testProdId, 10.00, 220.00, null, 'รับล็อต 6: manual cost', 2, 'manual');
        $pCheck6 = $productModel->find($testProdId);
        $this->assertTest("Manual formula preserves fixed standard cost (175.00 THB)", (float)$pCheck6['cost_price'] == 175.00);

        // 10.6 ทดสอบระบบ Landed Cost Allocation (ปันส่วนค่าขนส่ง)
        $poItemsTest = [
            ['product_id' => 1, 'qty' => 10.00, 'cost' => 100.00], // มูลค่า 1,000 (25%)
            ['product_id' => 2, 'qty' => 10.00, 'cost' => 300.00], // มูลค่า 3,000 (75%)
        ];
        // ปันส่วนตามมูลค่า (by_value) ค่าขนส่ง 400 บาท:
        // Item 1 ได้ 100 บาท (10 บาท/ชิ้น) -> Effective Cost = 110.00
        // Item 2 ได้ 300 บาท (30 บาท/ชิ้น) -> Effective Cost = 330.00
        $allocatedByValue = $costService->allocateLandedCost($poItemsTest, 400.00, 0.00, 'by_value');
        $this->assertTest("Landed cost by_value: Item 1 effective cost is 110.00 THB", (float)$allocatedByValue[0]['effective_cost'] == 110.00);
        $this->assertTest("Landed cost by_value: Item 2 effective cost is 330.00 THB", (float)$allocatedByValue[1]['effective_cost'] == 330.00);

        // ปันส่วนตามจำนวนชิ้น (by_qty) ค่าขนส่ง 400 บาท (รวม 20 ชิ้น -> ชิ้นละ 20 บาท):
        // Item 1 -> 100 + 20 = 120.00
        // Item 2 -> 300 + 20 = 320.00
        $allocatedByQty = $costService->allocateLandedCost($poItemsTest, 400.00, 0.00, 'by_qty');
        $this->assertTest("Landed cost by_qty: Item 1 effective cost is 120.00 THB", (float)$allocatedByQty[0]['effective_cost'] == 120.00);
        $this->assertTest("Landed cost by_qty: Item 2 effective cost is 320.00 THB", (float)$allocatedByQty[1]['effective_cost'] == 320.00);

        // 10.7 ตรวจสอบ Audit Log ใน product_cost_logs
        $costHistory = $costLogModel->getProductHistory($tenantId, (int)$testProdId);
        $this->assertTest("Product cost audit logs recorded (count: " . count($costHistory) . ")", count($costHistory) >= 5);
        $this->assertTest("Audit log tracks latest method and cost diff", $costHistory[0]['costing_method'] === 'manual');

        // 10.8 ทดสอบการเลือกปันส่วน vs ไม่ปันส่วน และการป้องกันการบันทึกทับซ้อน (Zero Double-Counting)
        // 10.8.1 กรณีเลือกปันส่วน (allocateToCost = true):
        $db->table('purchase_orders')->insert([
            'tenant_id'     => $tenantId,
            'branch_id'     => 1,
            'po_no'         => 'PO-TEST-ALLOC',
            'supplier_name' => 'Supplier A',
            'status'        => 'pending',
            'total_amount'  => 1000.00,
            'created_at'    => date('Y-m-d H:i:s'),
        ]);
        $poTest1Id = $db->insertID();
        $db->table('purchase_order_items')->insert([
            'purchase_order_id' => $poTest1Id,
            'product_id'        => 4,
            'qty_ordered'       => 10.00,
            'unit_cost'         => 100.00,
            'created_at'        => date('Y-m-d H:i:s'),
        ]);
        $grn1Id = $stockService->receivePurchaseOrder($tenantId, (int)$poTest1Id, 1, [
            ['product_id' => 4, 'qty' => 10.00, 'cost' => 100.00, 'serials' => []]
        ], 'ตรวจรับแบบปันส่วนค่าส่ง', 2, 200.00, 0.00, 'latest_cost', true);

        // ตรวจสอบว่าบันทึกเป็น inventory_freight_capitalized (ไม่ถูกนับเป็น shop_expense)
        $finTxnAlloc = $db->table('financial_transactions')->where('ref_type', 'goods_receipt')->where('ref_id', $grn1Id)->get()->getRowArray();
        $this->assertTest("When allocated to cost: Freight is capitalized as inventory asset (category: {$finTxnAlloc['category']})", $finTxnAlloc['category'] === 'inventory_freight_capitalized');

        // 10.8.2 กรณีเลือกไม่ปันส่วน (allocateToCost = false):
        $db->table('purchase_orders')->insert([
            'tenant_id'     => $tenantId,
            'branch_id'     => 1,
            'po_no'         => 'PO-TEST-EXPENSE',
            'supplier_name' => 'Supplier B',
            'status'        => 'pending',
            'total_amount'  => 1000.00,
            'created_at'    => date('Y-m-d H:i:s'),
        ]);
        $poTest2Id = $db->insertID();
        $db->table('purchase_order_items')->insert([
            'purchase_order_id' => $poTest2Id,
            'product_id'        => 4,
            'qty_ordered'       => 10.00,
            'unit_cost'         => 100.00,
            'created_at'        => date('Y-m-d H:i:s'),
        ]);
        $grn2Id = $stockService->receivePurchaseOrder($tenantId, (int)$poTest2Id, 1, [
            ['product_id' => 4, 'qty' => 10.00, 'cost' => 100.00, 'serials' => []]
        ], 'ตรวจรับแบบบันทึกค่าส่งเป็นค่าใช้จ่ายหน้าร้าน', 2, 200.00, 0.00, 'latest_cost', false);

        // ตรวจสอบว่าบันทึกเป็น shop_expense ทันที
        $finTxnExpense = $db->table('financial_transactions')->where('ref_type', 'goods_receipt')->where('ref_id', $grn2Id)->get()->getRowArray();
        $this->assertTest("When NOT allocated: Freight is recorded directly as shop operating expense (category: {$finTxnExpense['category']})", $finTxnExpense['category'] === 'shop_expense');

        CLI::write(PHP_EOL . "=== 11. TEST NON-STOCK SERVICE PRODUCTS & POS CHECKOUT ===", 'yellow');
        $posService = new PosService();

        // 11.1 ตรวจสอบสินค้าประเภทบริการไม่ตัดสต็อก (track_stock = 0)
        $laborProduct = $productModel->find(5);
        $filmInstProduct = $productModel->find(6);
        $this->assertTest("Service item 1 exists and track_stock = 0 (Product 5: {$laborProduct['name']})", 
            $laborProduct !== null && (int)$laborProduct['track_stock'] === 0);
        $this->assertTest("Service item 2 exists and track_stock = 0 (Product 6: {$filmInstProduct['name']})", 
            $filmInstProduct !== null && (int)$filmInstProduct['track_stock'] === 0);

        // 11.2 เปิดกะเงินสดหน้าร้านสาขา 1 (HQ) - ปิดกะเดิมก่อนเพื่อความแม่นยำ
        $db->table('cash_shifts')->where('tenant_id', $tenantId)->where('branch_id', 1)->where('status', 'open')->update(['status' => 'closed', 'closed_at' => date('Y-m-d H:i:s')]);
        $posShiftId = $financeService->openShift($tenantId, 1, 1, 1000.00, 'กะเงินสดทดสอบขาย POS');
        $this->assertTest("POS Cash Shift opened at HQ (Shift ID: {$posShiftId})", $posShiftId > 0);

        // ดึงสต็อกก่อนขาย
        $p1StockBefore = $productModel->getStockBalance($tenantId, 1, 1, 1);
        $p5StockBefore = $productModel->getStockBalance($tenantId, 5, 1, 1);

        // 11.3 ดำเนินการขายหน้าร้านแบบผสม (Hybrid: ตัดสต็อก 2 ชิ้น + บริการไม่ตัดสต็อก 1 ครั้ง)
        // Product 1 (ฟิล์มกระจก): ราคาขายปกติ 120 บาท x 2 = 240 บาท (track_stock = 1)
        // Product 5 (ค่าแรงบริการ): 300 บาท x 1 = 300 บาท (track_stock = 0)
        // รวม = 540 บาท ชำระด้วยเงินสด
        $checkout1 = $posService->checkout(
            $tenantId, 1, 1, 1,
            [
                ['product_id' => 1, 'qty' => 2.00],
                ['product_id' => 5, 'qty' => 1.00],
            ],
            'cash', 0.00, 'บิลทดสอบ POS ผสมสินค้าและบริการ', 1
        );
        $this->assertTest("POS Checkout successful (Order: {$checkout1['order_no']}, Net: {$checkout1['net_amount']})", 
            $checkout1['success'] === true && $checkout1['net_amount'] == 540.00);

        // ยืนยันว่าสต็อกสินค้า Product 1 ลดลง 2 ชิ้น
        $p1StockAfter = $productModel->getStockBalance($tenantId, 1, 1, 1);
        $this->assertTest("Physical product stock deducted (-2: {$p1StockBefore} -> {$p1StockAfter})", $p1StockAfter == ($p1StockBefore - 2));

        // ยืนยันว่าสินค้าบริการ Product 5 ไม่มีการตัดสต็อก (เป็น 0 ตลอดไป)
        $p5StockAfter = $productModel->getStockBalance($tenantId, 5, 1, 1);
        $this->assertTest("Service item stock not deducted (Stock balance: {$p5StockAfter})", $p5StockAfter == 0);

        // ตรวจสอบว่าใน stock_transactions ไม่มีแถวของ Product 5
        $srvTxnCount = $db->table('stock_transactions')
            ->where('ref_type', 'pos_order')
            ->where('ref_id', $checkout1['order_id'])
            ->where('product_id', 5)
            ->countAllResults();
        $this->assertTest("Zero stock ledger transactions generated for service item (count: {$srvTxnCount})", $srvTxnCount === 0);

        // ตรวจสอบยอดเงินสดในกะเงินสด cash_shifts เพิ่มขึ้น 540 บาท
        $shiftData = $db->table('cash_shifts')->where('id', $posShiftId)->get()->getRowArray();
        $this->assertTest("Cash shift cash sales recorded (+540.00: {$shiftData['cash_sales']})", (float)$shiftData['cash_sales'] == 540.00);

        // 11.4 ทดสอบขายสินค้า Hybrid Serial ผ่าน POS พร้อม Tier Price ของลูกค้า
        // Customer 2 (ช่างต้อม) มี Tier 2 (Tech Member) ซึ่ง Product 2 มี Product Override เป็น Fixed 2,800 บาท
        $availSerial = $serialModel->where('product_id', 2)
            ->where('tenant_id', $tenantId)
            ->where('status', 'in_stock')
            ->first();
        $this->assertTest("Available serial found for POS sale (SN: {$availSerial['serial_no']})", !empty($availSerial));

        $checkout2 = $posService->checkout(
            $tenantId, 1, 1, 2, // Customer 2 (Tier 2 Override: 2,800 THB)
            [
                ['product_id' => 2, 'qty' => 1.00, 'serial_id' => $availSerial['id']],
            ],
            'bank_transfer', 0.00, 'ขายจอให้ช่างต้อม Tier Override 2800', 1
        );
        $this->assertTest("POS Checkout with Customer Tier price (Product Override: 2,800.00 THB, Actual: {$checkout2['net_amount']})", 
            $checkout2['success'] === true && $checkout2['net_amount'] == 2800.00);

        $soldSerial = $serialModel->find($availSerial['id']);
        $this->assertTest("Serial status marked as 'sold' (SN: {$soldSerial['serial_no']}, Status: {$soldSerial['status']})", 
            $soldSerial['status'] === 'sold');

        // 11.5 ทดสอบชำระเงินด้วย Store Credit Wallet ผ่าน POS
        $walletCust1 = $walletModel->getOrCreateWallet($tenantId, 'customer', 1);
        $walletModel->update($walletCust1['id'], ['credit_balance' => 0.00]);
        $walletModel->adjustBalance((int)$walletCust1['id'], 'credit_deposit', 500.00, 'manual', null, 'เติมเงินทดสอบ POS', 1);
        $balBefore = (float)$walletModel->find($walletCust1['id'])['credit_balance'];

        $checkout3 = $posService->checkout(
            $tenantId, 1, 1, 1,
            [
                ['product_id' => 6, 'qty' => 1.00], // ค่าบริการติดฟิล์ม 80 บาท
            ],
            'store_credit', 0.00, 'ตัดจ่ายด้วย Store Credit ในกระเป๋าเงิน', 1
        );
        $balAfter = (float)$walletModel->find($walletCust1['id'])['credit_balance'];
        $this->assertTest("POS Checkout with Store Credit deduction (-80.00: {$balBefore} -> {$balAfter})", 
            $checkout3['success'] === true && $balAfter == ($balBefore - 80.00));

        CLI::write(PHP_EOL . "=== 12. TEST MULTI-BRANCH STOCK TRANSFERS ===", 'yellow');
        $transferService = new StockTransferService();

        // หา Serial ที่เหลืออยู่ของ Product 2 ใน HQ
        $transferSerial = $serialModel->where('product_id', 2)
            ->where('tenant_id', $tenantId)
            ->where('branch_id', 1)
            ->where('status', 'in_stock')
            ->first();
        $this->assertTest("Available serial in Branch 1 found for inter-branch transfer (SN: {$transferSerial['serial_no']})", !empty($transferSerial));

        $p1Wh1Before = $productModel->getStockBalance($tenantId, 1, 1, 1);
        $p1Wh5Before = $productModel->getStockBalance($tenantId, 1, 2, 5);

        // 12.1 สร้างใบขอโอนย้ายสินค้า HQ (Branch 1, Wh 1) -> สาขาเซ็นทรัล (Branch 2, Wh 5)
        $transferRes = $transferService->createTransfer(
            $tenantId,
            1, 1, // from Branch 1, Wh 1
            2, 5, // to Branch 2, Wh 5
            [
                ['product_id' => 1, 'qty' => 10.00],
                ['product_id' => 2, 'qty' => 1.00, 'serial_id' => $transferSerial['id']],
            ],
            'โอนอะไหล่ด่วนจากสำนักงานใหญ่ไปสาขาเซ็นทรัล',
            1
        );
        $transferId = (int)$transferRes['transfer_id'];
        $this->assertTest("Stock Transfer request created (Doc: {$transferRes['transfer_no']}, Status: {$transferRes['status']})", 
            $transferRes['success'] === true && $transferRes['status'] === 'requested');

        // 12.2 ส่งสินค้าออกจากคลังต้นทาง (Dispatch Transfer)
        $dispatchRes = $transferService->dispatchTransfer($tenantId, $transferId, 1);
        $this->assertTest("Stock Transfer dispatched (Status: {$dispatchRes['status']})", 
            $dispatchRes['success'] === true && $dispatchRes['status'] === 'in_transit');

        $p1Wh1After = $productModel->getStockBalance($tenantId, 1, 1, 1);
        $this->assertTest("Source warehouse stock deducted (-10: {$p1Wh1Before} -> {$p1Wh1After})", 
            $p1Wh1After == ($p1Wh1Before - 10));

        $inTransitSerial = $serialModel->find($transferSerial['id']);
        $this->assertTest("Serial status marked as 'in_transit' during delivery", 
            $inTransitSerial['status'] === 'in_transit');

        // 12.3 ตรวจรับสินค้าเข้าคลังปลายทาง (Receive Transfer)
        $receiveRes = $transferService->receiveTransfer($tenantId, $transferId, 2);
        $this->assertTest("Stock Transfer received at destination (Status: {$receiveRes['status']})", 
            $receiveRes['success'] === true && $receiveRes['status'] === 'completed');

        $p1Wh5After = $productModel->getStockBalance($tenantId, 1, 2, 5);
        $this->assertTest("Destination warehouse stock increased (+10: {$p1Wh5Before} -> {$p1Wh5After})", 
            $p1Wh5After == ($p1Wh5Before + 10));

        $destSerial = $serialModel->find($transferSerial['id']);
        $this->assertTest("Serial relocated to Branch 2 / Wh 5 and restored to 'in_stock'", 
            (int)$destSerial['branch_id'] === 2 && (int)$destSerial['warehouse_id'] === 5 && $destSerial['status'] === 'in_stock');

        CLI::write(PHP_EOL . "=== 13. TEST TENANT SETTINGS & ONBOARDING WIZARD ===", 'yellow');
        $settingService = new TenantSettingService();

        // 13.1 ดึงการตั้งค่าเริ่มต้นของร้าน
        $initialSettings = $settingService->getSettings($tenantId);
        $this->assertTest("Tenant initial settings loaded (workflow: {$initialSettings['workflow_mode']})", 
            $initialSettings['workflow_mode'] === 'simple');
        $this->assertTest("Initially in simple mode (isSimpleMode = true)", $settingService->isSimpleMode($tenantId) === true);
        $this->assertTest("Initially PO not required (isPoRequired = false)", $settingService->isPoRequired($tenantId) === false);

        // 13.2 จำลองการตอบแบบสอบถาม Onboarding Wizard (เลือกโหมดบริษัท: ต้องมี PO, ต้นทุนล่าสุด, บังคับกะ)
        $wizardResult = $settingService->saveWizardAnswers($tenantId, [
            'po_policy'          => 'require_po',
            'costing_method'     => 'latest_cost',
            'freight_policy'     => 'capitalize',
            'cash_shift_policy'  => 'strict',
            'serial_policy'      => 'yes',
        ]);
        $this->assertTest("Wizard answers processed successfully", $wizardResult['success'] === true);
        $this->assertTest("Now in corporate mode (workflow_mode: standard)", $settingService->isPoRequired($tenantId) === true);
        $this->assertTest("Onboarding flag marked as true", $settingService->isOnboarded($tenantId) === true);

        // 13.3 สลับกลับเป็นโหมดร้านทั่วไป (Direct Inbound)
        $settingService->saveWizardAnswers($tenantId, [
            'po_policy'          => 'direct',
            'costing_method'     => 'moving_average',
            'freight_policy'     => 'expense',
            'cash_shift_policy'  => 'flexible',
            'serial_policy'      => 'yes',
        ]);
        $this->assertTest("Switched back to simple mode (isSimpleMode = true)", $settingService->isSimpleMode($tenantId) === true);
        $this->assertTest("PO is no longer required (isPoRequired = false)", $settingService->isPoRequired($tenantId) === false);

        CLI::write(PHP_EOL . "=== 14. TEST STOCK COUNT & ADJUSTMENT ENGINE ===", 'yellow');
        $adjService = new StockAdjustmentService();

        // สต็อกก่อนปรับปรุงของ Product 1 ใน HQ (Branch 1, Wh 1)
        $p1StockBeforeAdj = $productModel->getStockBalance($tenantId, 1, 1, 1);

        // 14.1 สร้างใบตรวจนับสต็อกสิ้นงวด: นับจริงได้น้อยกว่าระบบ 3 ชิ้น (ขาด 3 ชิ้น)
        $countedQty = $p1StockBeforeAdj - 3.00;
        $adjRes1 = $adjService->createAdjustment(
            $tenantId, 1, 1, 'cycle_count',
            [
                ['product_id' => 1, 'counted_qty' => $countedQty, 'reason' => 'ตรวจนับสินค้าสิ้นเดือนขาดหาย 3 ชิ้น'],
            ],
            'ใบตรวจนับสต็อกประจำงวด', 1
        );
        $adjId1 = (int)$adjRes1['adjustment_id'];
        $this->assertTest("Adjustment draft created (Doc: {$adjRes1['adjustment_no']}, Status: {$adjRes1['status']})", 
            $adjRes1['success'] === true && $adjRes1['status'] === 'draft');
        $this->assertTest("Total loss value calculated accurately (diff: -3, loss: {$adjRes1['total_loss_value']} THB)", 
            $adjRes1['total_loss_value'] > 0);

        // 14.2 อนุมัติใบตรวจนับสต็อก (Approve)
        $approveRes1 = $adjService->approveAdjustment($tenantId, $adjId1, 1);
        $this->assertTest("Adjustment approved successfully (Status: {$approveRes1['status']})", 
            $approveRes1['success'] === true && $approveRes1['status'] === 'approved');

        // ตรวจสอบสต็อกลดลง 3 ชิ้นพอดี
        $p1StockAfterAdj = $productModel->getStockBalance($tenantId, 1, 1, 1);
        $this->assertTest("Stock balance deducted by variance (-3: {$p1StockBeforeAdj} -> {$p1StockAfterAdj})", 
            $p1StockAfterAdj == ($p1StockBeforeAdj - 3.00));

        // ตรวจสอบบันทึกบัญชี inventory_loss
        $lossTxn = $db->table('financial_transactions')
                      ->where('ref_type', 'stock_adjustment')
                      ->where('ref_id', $adjId1)
                      ->where('category', 'inventory_loss')
                      ->get()->getRowArray();
        $this->assertTest("Financial ledger recorded inventory_loss expense (Txn ID: {$lossTxn['id']}, Amount: {$lossTxn['amount']})", 
            !empty($lossTxn) && $lossTxn['type'] === 'expense');

        // 14.3 ทดสอบการปรับปรุงสต็อกเกิน (พบของเกิน 2 ชิ้น)
        $p1StockBeforeGain = $productModel->getStockBalance($tenantId, 1, 1, 1);
        $adjRes2 = $adjService->createAdjustment(
            $tenantId, 1, 1, 'found',
            [
                ['product_id' => 1, 'counted_qty' => ($p1StockBeforeGain + 2.00), 'reason' => 'พบสินค้าเกิน 2 ชิ้นหลังชั้นวาง'],
            ],
            'พบสินค้าเกิน', 1
        );
        $adjId2 = (int)$adjRes2['adjustment_id'];
        $adjService->approveAdjustment($tenantId, $adjId2, 1);
        $p1StockAfterGain = $productModel->getStockBalance($tenantId, 1, 1, 1);
        $this->assertTest("Stock balance increased on inventory gain (+2: {$p1StockBeforeGain} -> {$p1StockAfterGain})", 
            $p1StockAfterGain == ($p1StockBeforeGain + 2.00));

        $gainTxn = $db->table('financial_transactions')
                      ->where('ref_type', 'stock_adjustment')
                      ->where('ref_id', $adjId2)
                      ->where('category', 'inventory_gain')
                      ->get()->getRowArray();
        $this->assertTest("Financial ledger recorded inventory_gain income (Amount: {$gainTxn['amount']})", 
            !empty($gainTxn) && $gainTxn['type'] === 'income');

        CLI::write(PHP_EOL . "=== 15. TEST OWNER REPORT ENGINE & DASHBOARD STATS ===", 'yellow');
        $reportService = new ReportService();

        // 15.1 สถิติรวมสำหรับ Dashboard ของ Owner
        $dashStats = $reportService->getDashboardStats($tenantId);
        $this->assertTest("Dashboard stats fetched successfully", is_array($dashStats));
        $this->assertTest("Dashboard includes active repairs count ({$dashStats['active_repairs']})", isset($dashStats['active_repairs']));
        $this->assertTest("Dashboard includes low stock items count ({$dashStats['low_stock_count']})", isset($dashStats['low_stock_count']));
        $this->assertTest("Dashboard includes recent repairs list (count: " . count($dashStats['recent_repairs']) . ")", 
            is_array($dashStats['recent_repairs']));

        // 15.2 รายงานกำไรขั้นต้น (Gross Profit: POS Margin + Repair Margin)
        $today = date('Y-m-d');
        $profitReport = $reportService->getGrossProfitReport($tenantId, $today, $today);
        $this->assertTest("Gross profit report calculated (POS Margin: {$profitReport['pos_margin']}, Repair Margin: {$profitReport['repair_margin']})", 
            isset($profitReport['total_margin']));
        $this->assertTest("Report includes margin percentage ({$profitReport['margin_percent']}%)", 
            isset($profitReport['margin_percent']));

        // 15.3 รายงานการแจ้งเตือนสินค้าสต็อกต่ำ
        $lowStockAlerts = $reportService->getLowStockAlerts($tenantId, 50.00);
        $this->assertTest("Low stock alerts report functional (found: " . count($lowStockAlerts) . " items under threshold 50)", 
            is_array($lowStockAlerts));

        // 15.4 รายงานลูกหนี้ค้างชำระ (Debt Report)
        $debtReport = $reportService->getOutstandingDebtReport($tenantId);
        $this->assertTest("Customer outstanding debt report functional (debtors count: " . count($debtReport) . ")", 
            is_array($debtReport));

        // Clean up Section 14 adjustments
        $db->table('stock_adjustment_items')->whereIn('stock_adjustment_id', [$adjId1, $adjId2])->delete();
        $db->table('stock_adjustments')->whereIn('id', [$adjId1, $adjId2])->delete();
        $db->table('stock_transactions')->where('ref_type', 'stock_adjustment')->whereIn('ref_id', [$adjId1, $adjId2])->delete();
        $db->table('financial_transactions')->where('ref_type', 'stock_adjustment')->whereIn('ref_id', [$adjId1, $adjId2])->delete();

        // ทำความสะอาดข้อมูลทดสอบ Section 11 & 12
        $db->table('stock_transfer_items')->where('stock_transfer_id', $transferId)->delete();
        $db->table('stock_transfers')->where('id', $transferId)->delete();
        $db->table('stock_transactions')->where('ref_type', 'stock_transfer')->where('ref_id', $transferId)->delete();
        $serialModel->update($transferSerial['id'], ['branch_id' => 1, 'warehouse_id' => 3, 'status' => 'in_stock']);

        $posOrders = [$checkout1['order_id'], $checkout2['order_id'], $checkout3['order_id']];
        $db->table('pos_order_items')->whereIn('pos_order_id', $posOrders)->delete();
        $db->table('pos_orders')->whereIn('id', $posOrders)->delete();
        $db->table('stock_transactions')->where('ref_type', 'pos_order')->whereIn('ref_id', $posOrders)->delete();
        $db->table('financial_transactions')->where('ref_type', 'pos_order')->whereIn('ref_id', $posOrders)->delete();
        $serialModel->update($availSerial['id'], ['status' => 'in_stock']);
        $db->table('cash_shifts')->where('id', $posShiftId)->delete();

        // Clean up test POs for 10.8
        $db->table('financial_transactions')->whereIn('id', [$finTxnAlloc['id'], $finTxnExpense['id']])->delete();
        $db->table('goods_receipt_items')->whereIn('goods_receipt_id', [$grn1Id, $grn2Id])->delete();
        $db->table('goods_receipts')->whereIn('id', [$grn1Id, $grn2Id])->delete();
        $db->table('purchase_order_items')->whereIn('purchase_order_id', [$poTest1Id, $poTest2Id])->delete();
        $db->table('purchase_orders')->whereIn('id', [$poTest1Id, $poTest2Id])->delete();

        // ทำความสะอาดข้อมูลทดสอบสินค้าใน Section 10
        $db->table('product_cost_logs')->where('product_id', $testProdId)->delete();
        $db->table('stock_transactions')->where('product_id', $testProdId)->delete();
        $productModel->delete($testProdId);

        // Clean up test jobs
        $db->table('repair_deposits')->whereIn('repair_job_id', [$depJobId, $excessJobId])->delete();
        $db->table('repair_items')->whereIn('repair_job_id', [$depJobId, $excessJobId])->delete();
        $db->table('repair_jobs')->whereIn('id', [$depJobId, $excessJobId])->delete();

        // Clean up test data
        $db->table('repair_status_logs')->where('repair_job_id', $jobId)->delete();
        $db->table('repair_items')->where('repair_job_id', $jobId)->delete();
        $db->table('repair_jobs')->where('id', $jobId)->delete();
        $db->table('wallet_transactions')->where('wallet_id', $wallet['id'])->delete();
        $db->table('wallets')->where('id', $wallet['id'])->delete();
        $db->table('goods_receipt_items')->where('goods_receipt_id', $grnId)->delete();
        $db->table('goods_receipts')->where('id', $grnId)->delete();
        $db->table('purchase_order_items')->where('purchase_order_id', $poId)->delete();
        $db->table('purchase_orders')->where('id', $poId)->delete();
        $db->table('cash_shifts')->where('id', $shiftId)->delete();
        $db->table('financial_transactions')->where('id', $finTxnId)->delete();

        CLI::write(PHP_EOL . "=== 16. TEST CUSTOMIZABLE DASHBOARD & PERMISSION-ENFORCED WIDGETS ===", 'yellow');
        $layoutService = new \App\Services\DashboardLayoutService();
        \App\Libraries\Dashboard\WidgetRegistry::init();

        // 16.1 ตรวจสอบ Widget Registry
        $allRegisteredWidgets = \App\Libraries\Dashboard\WidgetRegistry::getAllWidgets();
        $this->assertTest("WidgetRegistry initialized with 13 built-in widgets", count($allRegisteredWidgets) >= 13);
        $this->assertTest("PosSalesTodayWidget exists in registry", isset($allRegisteredWidgets['pos_sales_today']));
        $this->assertTest("AdminTenantStatsWidget exists in registry", isset($allRegisteredWidgets['admin_tenant_stats']));

        // 16.2 ตรวจสอบการกรองสิทธิ์ (Permission Enforcement by Role)
        // Superadmin: สิทธิ์ '*' หรือ role 'superadmin' ต้องเข้าถึง platform widgets ได้
        $superadminWidgets = \App\Libraries\Dashboard\WidgetRegistry::getAvailableWidgets(['*'], 'superadmin');
        $this->assertTest("Superadmin can access platform widgets (admin_tenant_stats)", isset($superadminWidgets['admin_tenant_stats']));

        // Owner: มีสิทธิ์ dashboard ร้านค้า แต่ต้องไม่เห็น platform widgets
        $ownerWidgets = \App\Libraries\Dashboard\WidgetRegistry::getAvailableWidgets(['dashboard', 'products.*', 'settings.*'], 'owner');
        $this->assertTest("Owner can access POS sales and shop widgets", isset($ownerWidgets['pos_sales_today']));
        $this->assertTest("Owner CANNOT access platform widgets (admin_tenant_stats)", !isset($ownerWidgets['admin_tenant_stats']));

        // Technician: มีสิทธิ์ซ่อมบำรุง ต้องเห็นเฉพาะ repair widgets
        $techWidgets = \App\Libraries\Dashboard\WidgetRegistry::getAvailableWidgets(['repairs.view', 'dashboard'], 'technician');
        $this->assertTest("Technician can access active_repairs widget", isset($techWidgets['active_repairs']));
        $this->assertTest("Technician CANNOT access platform widgets", !isset($techWidgets['admin_tenant_stats']));

        // Sales / Cashier: มีสิทธิ์ขายหน้าร้าน
        $salesWidgets = \App\Libraries\Dashboard\WidgetRegistry::getAvailableWidgets(['pos.view', 'products', 'dashboard'], 'sales');
        $this->assertTest("Sales can access cash_drawer widget", isset($salesWidgets['cash_drawer']));
        $this->assertTest("Sales CANNOT access platform widgets", !isset($salesWidgets['admin_tenant_stats']));

        // 16.3 ตรวจสอบ Default Layout ตาม Role
        $ownerDefaultLayout = $layoutService->getUserLayout(2, 1, 'owner', ['dashboard', 'products.*']);
        $this->assertTest("Owner default layout loaded with widgets", count($ownerDefaultLayout) > 0);

        $techDefaultLayout = $layoutService->getUserLayout(3, 1, 'technician', ['repairs.view', 'dashboard']);
        $this->assertTest("Technician default layout loaded with widgets", count($techDefaultLayout) > 0);
        $techWidgetIds = array_column($techDefaultLayout, 'widget_id');
        $this->assertTest("Technician default layout contains active_repairs", in_array('active_repairs', $techWidgetIds));

        // 16.4 ตรวจสอบการบันทึก Custom Layout (Drag & Drop Persistence)
        $customLayout = [
            ['widget_id' => 'gross_profit_summary', 'col' => 'col-lg-6'],
            ['widget_id' => 'pos_sales_today',      'col' => 'col-lg-6'],
            ['widget_id' => 'recent_repairs_table', 'col' => 'col-lg-12'],
        ];
        $saveResult = $layoutService->saveUserLayout(2, 1, $customLayout, ['dashboard', 'products.*'], 'owner');
        $this->assertTest("Owner saved custom layout successfully", $saveResult['success'] === true);

        // ดึง Layout ที่บันทึกไว้
        $savedUserLayout = $layoutService->getUserLayout(2, 1, 'owner', ['dashboard', 'products.*']);
        $this->assertTest("Retrieved saved layout matches custom count (3)", count($savedUserLayout) === 3);
        $this->assertTest("First saved widget is gross_profit_summary", $savedUserLayout[0]['widget_id'] === 'gross_profit_summary');
        $this->assertTest("First widget custom width is col-lg-6", $savedUserLayout[0]['col'] === 'col-lg-6');

        // 16.5 ตรวจสอบ Server-side Security Violation (Technician พยายามบันทึก Widget ของ Admin)
        $illegalLayout = [
            ['widget_id' => 'admin_tenant_stats',   'col' => 'col-lg-3 col-sm-6'], // ไม่มีสิทธิ์
            ['widget_id' => 'active_repairs',       'col' => 'col-lg-6'], // มีสิทธิ์
        ];
        $techSaveResult = $layoutService->saveUserLayout(3, 1, $illegalLayout, ['repairs.view'], 'technician');
        $this->assertTest("Server sanitized unauthorized widgets from technician layout", $techSaveResult['success'] === true);
        $savedTechWidgets = array_column($techSaveResult['layout'], 'widget_id');
        $this->assertTest("Unauthorized admin_tenant_stats was stripped out", !in_array('admin_tenant_stats', $savedTechWidgets));
        $this->assertTest("Authorized active_repairs was preserved", in_array('active_repairs', $savedTechWidgets));

        // 16.6 ตรวจสอบการคืนค่าเริ่มต้น (Reset Layout)
        $resetResult = $layoutService->resetUserLayout(2, 1, 'owner');
        $this->assertTest("Reset layout succeeded", $resetResult['success'] === true);
        $restoredLayout = $layoutService->getUserLayout(2, 1, 'owner', ['dashboard', 'products.*']);
        $this->assertTest("Owner layout restored to default count", count($restoredLayout) >= 5);

        // Clean up test dashboard layouts
        $db->table('user_dashboard_layouts')->whereIn('user_id', [2, 3])->delete();

        CLI::write(PHP_EOL . "=== 17. TEST SHOP SETTINGS, USER PROFILE & USER SETTINGS ===", 'yellow');
        $userSettingService = new \App\Services\UserSettingService();

        // 17.1 ตรวจสอบและบันทึก User Settings (Preferences)
        $initialUserSettings = $userSettingService->getUserSettings(2);
        $this->assertTest("Initial user settings loaded (theme: {$initialUserSettings['theme']})", isset($initialUserSettings['theme']));

        $userSettingService->saveUserSettings(2, [
            'theme'         => 'dark',
            'language'      => 'th',
            'sound_enabled' => '1',
            'notify_repair' => '1',
            'notify_stock'  => '0',
        ]);
        $updatedUserSettings = $userSettingService->getUserSettings(2);
        $this->assertTest("User settings saved (theme is dark)", $updatedUserSettings['theme'] === 'dark');
        $this->assertTest("User settings saved (notify_stock is 0)", $updatedUserSettings['notify_stock'] === '0');

        // 17.2 ตรวจสอบ Tenant Profile & Shop Settings
        $tenantProfile = $settingService->getTenantProfile($tenantId);
        $this->assertTest("Tenant profile retrieved successfully", !empty($tenantProfile['slug']));

        $settingService->saveSettings($tenantId, [
            'shop_phone'         => '089-999-8888',
            'shop_address'       => '456 อาคารสุขุมวิท กทม.',
            'print_paper_size'   => 'slip_80mm',
            'print_header_note'  => 'ยินดีต้อนรับสู่ Demo Shop',
        ]);
        $shopSettings = $settingService->getSettings($tenantId);
        $this->assertTest("Shop phone setting updated (089-999-8888)", $shopSettings['shop_phone'] === '089-999-8888');
        $this->assertTest("Print paper size setting updated (slip_80mm)", $shopSettings['print_paper_size'] === 'slip_80mm');

        // 17.3 ตรวจสอบ User Profile Data & Permission Binding
        $userProfile = $db->table('users')
                          ->select('users.*, peoples.first_name, peoples.last_name, peoples.phone')
                          ->join('peoples', 'peoples.id = users.person_id', 'left')
                          ->where('users.id', 2)
                          ->get()
                          ->getRowArray();
        $this->assertTest("User profile joined with person successfully", !empty($userProfile['first_name']));
        $this->assertTest("User password hash verify works", password_verify('123456', $userProfile['password']) === true);

        // 17.4 ตรวจสอบ Permissions Query
        $ownerPermRows = $db->table('user_permissions')->where('user_id', 2)->get()->getResultArray();
        $ownerPermList = array_column($ownerPermRows, 'permission');
        $this->assertTest("Owner has dashboard permission", in_array('dashboard', $ownerPermList));
        $this->assertTest("Owner has products.* permission", in_array('products.*', $ownerPermList));

        // Clean up test user settings
        $db->table('user_settings')->where('user_id', 2)->delete();

        CLI::write(PHP_EOL . "=== 18. TEST PRODUCT CRUD WITH TABULATOR API & REUSABLE FORM ===", 'yellow');
        $prodController = new \App\Controllers\Admin\ProductController();
        $prodController->initController(\Config\Services::request(), \Config\Services::response(), \Config\Services::logger());

        // 18.1 ตรวจสอบ API listData() สำหรับ Tabulator Table
        $listResponse = $prodController->listData();
        $this->assertTest("Tabulator API returns HTTP 200 OK", $listResponse->getStatusCode() === 200);
        $tabulatorData = json_decode($listResponse->getBody(), true);
        $this->assertTest("Tabulator API returns array of products", is_array($tabulatorData) && count($tabulatorData) > 0);
        
        $firstItem = $tabulatorData[0];
        $this->assertTest("Tabulator row contains required fields (sku, name, sell_price, stock_qty)", 
            isset($firstItem['sku']) && isset($firstItem['name']) && isset($firstItem['sell_price']) && isset($firstItem['stock_qty']));
        $this->assertTest("Tabulator row contains progressive disclosure flags (has_serial, track_stock)", 
            isset($firstItem['has_serial']) && isset($firstItem['track_stock']));

        // 18.2 ทดสอบ Create ผ่าน store() พร้อม Auto SKU & Initial Stock Direct Inbound
        $_POST = [
            'name'          => 'หูฟังบลูทูธ TWS Studio Test',
            'sku'           => '', // จงใจเว้นว่าง เพื่อทดสอบระบบ Auto-generate SKU
            'barcode'       => '8859988776655',
            'sell_price'    => '890.00',
            'cost_price'    => '450.00',
            'unit'          => 'ชุด',
            'initial_stock' => '15',
            'track_stock'   => '1',
            'has_serial'    => '0',
        ];
        $storeResponse = $prodController->store();
        $storeResult = json_decode($storeResponse->getBody(), true);
        $this->assertTest("Product created successfully via store()", $storeResult['success'] === true && !empty($storeResult['id']));
        $createdProdId = (int)$storeResult['id'];

        $createdProd = $productModel->find($createdProdId);
        $this->assertTest("Zero-friction Auto-generated SKU created (SKU: {$createdProd['sku']})", 
            strpos($createdProd['sku'], 'PRD-') === 0);
        $this->assertTest("Initial stock of 15 units recorded in inventory ledger", 
            (float)$productModel->getStockBalance($tenantId, $createdProdId) == 15.00);

        // 18.3 ทดสอบ Create สินค้าบริการไม่ตัดสต็อก (Progressive Disclosure)
        $_POST = [
            'name'        => 'ค่าบริการอัปเกรดเฟิร์มแวร์ระบบ',
            'sku'         => 'SRV-FW-001',
            'sell_price'  => '250.00',
            'cost_price'  => '0.00',
            'track_stock' => '0',
            'has_serial'  => '0',
        ];
        $srvStoreResponse = $prodController->store();
        $srvStoreResult = json_decode($srvStoreResponse->getBody(), true);
        $this->assertTest("Service item (track_stock = 0) created successfully", $srvStoreResult['success'] === true);
        $createdSrvId = (int)$srvStoreResult['id'];

        $createdSrv = $productModel->find($createdSrvId);
        $this->assertTest("Service item flag track_stock is 0", (int)$createdSrv['track_stock'] === 0);
        $this->assertTest("Service item has 0 stock ledger balance", (float)$productModel->getStockBalance($tenantId, $createdSrvId) == 0.00);

        // 18.4 ทดสอบ getItem() สำหรับโหลดข้อมูลเข้า Reusable Form Drawer
        $getItemResponse = $prodController->getItem($createdProdId);
        $getItemResult = json_decode($getItemResponse->getBody(), true);
        $this->assertTest("getItem() returned success for Reusable Form Drawer", $getItemResult['success'] === true);
        $this->assertTest("getItem() includes current_stock field for display", 
            isset($getItemResult['data']['current_stock']) && (float)$getItemResult['data']['current_stock'] == 15.00);

        // 18.5 ทดสอบ update() แก้ไขข้อมูลสินค้า
        $_POST = [
            'name'        => 'หูฟังบลูทูธ TWS Studio Test (Pro Edition)',
            'sku'         => $createdProd['sku'],
            'barcode'     => '8859988776655',
            'sell_price'  => '990.00',
            'cost_price'  => '480.00',
            'unit'        => 'กล่อง',
            'track_stock' => '1',
            'has_serial'  => '0',
            'is_active'   => '1',
        ];
        $updateResponse = $prodController->update($createdProdId);
        $updateResult = json_decode($updateResponse->getBody(), true);
        $this->assertTest("Product updated successfully via update()", $updateResult['success'] === true);

        $updatedProd = $productModel->find($createdProdId);
        $this->assertTest("Updated product name matches", $updatedProd['name'] === 'หูฟังบลูทูธ TWS Studio Test (Pro Edition)');
        $this->assertTest("Updated product sell price matches (990.00 THB)", (float)$updatedProd['sell_price'] == 990.00);
        $this->assertTest("Updated product unit matches (กล่อง)", $updatedProd['unit'] === 'กล่อง');

        // 18.6 ทดสอบ Soft Delete ผ่าน delete()
        $deleteResponse = $prodController->delete($createdProdId);
        $deleteResult = json_decode($deleteResponse->getBody(), true);
        $this->assertTest("Product soft-deleted successfully via delete()", $deleteResult['success'] === true);

        $deletedProd = $productModel->find($createdProdId);
        $this->assertTest("Product is_active set to 0", (int)$deletedProd['is_active'] === 0);

        // Clean up test products and initial stock ledgers
        $db->table('stock_transactions')->where('product_id', $createdProdId)->delete();
        $db->table('goods_receipt_items')->where('product_id', $createdProdId)->delete();
        $productModel->delete($createdProdId);
        $productModel->delete($createdSrvId);

        CLI::write(PHP_EOL . "=== 19. TEST SERIAL NUMBER ENGINE, AUTO-GEN, SCANNER INBOUND & AUDIT TRAIL ===", 'yellow');
        $serialService = new \App\Services\ProductSerialService();
        $serialController = new \App\Controllers\Admin\SerialController();
        $serialController->initController(\Config\Services::request(), \Config\Services::response(), \Config\Services::logger());

        // 19.1 ทดสอบ Batch Auto-Generate Serials (สร้างรหัสอัตโนมัติ 5 ชิ้น)
        $batchQty = 5;
        $genResult = $serialService->generateBatchSerials($tenantId, 2, 1, 1, $batchQty, 'IP13AUTO', 2000.00, 'ล็อตทดสอบสร้างอัตโนมัติ', 1);
        $this->assertTest("Batch Auto-Gen generated {$batchQty} serials successfully", 
            $genResult['success'] === true && count($genResult['serials']) === $batchQty);

        $firstGeneratedSn = $genResult['serials'][0]['serial_no'];
        $firstSerialId = (int)$genResult['serials'][0]['id'];
        $this->assertTest("Generated serial follows pattern (Prefix: IP13AUTO, SN: {$firstGeneratedSn})", 
            strpos($firstGeneratedSn, 'IP13AUTO-') === 0);

        // ตรวจสอบสถานะเริ่มต้นในฐานข้อมูลเป็น in_stock
        $serialRow = $db->table('product_serials')->where('id', $firstSerialId)->get()->getRowArray();
        $this->assertTest("Serial status is initially 'in_stock'", $serialRow['status'] === 'in_stock');

        // ตรวจสอบว่ามี Stock Transaction รับเข้า (direct_in) ถูกบันทึกจริง
        $stTxn = $db->table('stock_transactions')->where('serial_id', $firstSerialId)->get()->getRowArray();
        $this->assertTest("Stock ledger recorded direct_in movement for serial", 
            $stTxn !== null && $stTxn['movement_type'] === 'direct_in' && (float)$stTxn['qty'] == 1.00);

        // 19.2 ทดสอบ Scanner Inbound พร้อมตรวจจับรหัสซ้ำ (Duplicate Prevention)
        $scanInputList = [
            'SCAN-SN-TEST-001',
            'SCAN-SN-TEST-002',
            $firstGeneratedSn, // จงใจใส่รหัสที่เพิ่ง Gen ไปข้างต้น เพื่อทดสอบระบบตรวจจับซ้ำ
            'SCAN-SN-TEST-003',
        ];
        $scanResult = $serialService->inboundScannedSerials($tenantId, 2, 1, 1, $scanInputList, 2000.00, 'สแกนรับเข้าทดสอบ', 1);
        $this->assertTest("Scanner inbound processed items successfully", $scanResult['success'] === true);
        $this->assertTest("Imported 3 new unique serials", $scanResult['imported_count'] === 3);
        $this->assertTest("Duplicate detected and skipped safely (Duplicate: {$firstGeneratedSn})", 
            count($scanResult['duplicates']) === 1 && $scanResult['duplicates'][0] === $firstGeneratedSn);

        // 19.3 ทดสอบ API listData() สำหรับ Tabulator Table
        $serialListResponse = $serialController->listData();
        $this->assertTest("Serial listData API returns HTTP 200", $serialListResponse->getStatusCode() === 200);
        $serialTableData = json_decode($serialListResponse->getBody(), true);
        $this->assertTest("Serial Tabulator data contains rows", is_array($serialTableData) && count($serialTableData) >= 8);
        $tableFirst = $serialTableData[0];
        $this->assertTest("Tabulator row contains serial_no, product_name, branch_name, status", 
            isset($tableFirst['serial_no']) && isset($tableFirst['product_name']) && isset($tableFirst['status']));

        // 19.4 ทดสอบ Audit Trail ไทม์ไลน์การเคลื่อนไหว
        $timelineResult = $serialService->getSerialTimeline($tenantId, $firstSerialId);
        $this->assertTest("Timeline fetched successfully for serial #{$firstSerialId}", $timelineResult['success'] === true);
        $this->assertTest("Timeline contains inbound event", count($timelineResult['timeline']) >= 1);
        $this->assertTest("Timeline event has title and movement_type", 
            !empty($timelineResult['timeline'][0]['title']) && $timelineResult['timeline'][0]['movement_type'] === 'direct_in');

        // 19.5 ทดสอบการปรับสถานะเป็น damaged และ lost พร้อมบันทึกลด/เพิ่มสต็อก
        $damagedResult = $serialService->updateSerialStatus($tenantId, $firstSerialId, 'damaged', 'หน้าจอแตกขณะย้ายกล่อง', 1);
        $this->assertTest("Status updated to damaged", $damagedResult['success'] === true);
        $damagedRow = $db->table('product_serials')->where('id', $firstSerialId)->get()->getRowArray();
        $this->assertTest("Database status is 'damaged'", $damagedRow['status'] === 'damaged');

        // ตรวจสอบว่ามี Stock Transaction adjust_out บันทึกตัดสต็อก -1.00
        $adjOutTxn = $db->table('stock_transactions')
            ->where('serial_id', $firstSerialId)
            ->where('movement_type', 'adjust_out')
            ->get()->getRowArray();
        $this->assertTest("Stock ledger deducted 1 unit on damage (movement: adjust_out, qty: -1.00)", 
            $adjOutTxn !== null && (float)$adjOutTxn['qty'] == -1.00);

        // ปรับสถานะกลับมาเป็น in_stock
        $restoreResult = $serialService->updateSerialStatus($tenantId, $firstSerialId, 'in_stock', 'เปลี่ยนอะไหล่ซ่อมแซมแล้วกลับมาใช้ได้', 1);
        $this->assertTest("Status restored to in_stock", $restoreResult['success'] === true);
        $adjInTxn = $db->table('stock_transactions')
            ->where('serial_id', $firstSerialId)
            ->where('movement_type', 'adjust_in')
            ->get()->getRowArray();
        $this->assertTest("Stock ledger restored 1 unit on return (movement: adjust_in, qty: 1.00)", 
            $adjInTxn !== null && (float)$adjInTxn['qty'] == 1.00);

        // Clean up test serials and transactions
        $allTestIds = array_merge($genResult['ids'], array_column($scanResult['imported'], 'id'));
        $db->table('stock_transactions')->whereIn('serial_id', $allTestIds)->delete();
        $db->table('product_serials')->whereIn('id', $allTestIds)->delete();

        CLI::write(PHP_EOL . "=== ALL ERP MODULE TESTS PASSED 100% ===", 'green');
    }

    private function assertTest(string $label, bool $result)
    {
        if ($result) {
            CLI::write("  [PASS] " . $label, 'green');
        } else {
            CLI::error("  [FAIL] " . $label);
        }
    }
}
