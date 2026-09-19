<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;

class ErpSeeder extends Seeder
{
    public function run()
    {
        $now = Time::now()->toDateTimeString();
        $tenantId = 1; // Demo Shop

        // 1. ล้างข้อมูล ERP เดิมให้สะอาด (Truncate / Delete)
        $tables = [
            'repair_deposits',
            'product_tier_prices',
            'price_tiers',
            'print_templates',
            'tenant_settings',
            'repair_status_logs',
            'repair_items',
            'repair_jobs',
            'cash_shifts',
            'financial_transactions',
            'wallet_transactions',
            'wallets',
            'goods_receipt_items',
            'goods_receipts',
            'purchase_order_items',
            'purchase_orders',
            'stock_transactions',
            'product_serials',
            'products',
            'categories',
            'warehouses',
            'branches',
        ];

        foreach ($tables as $tbl) {
            $this->db->table($tbl)->emptyTable();
        }

        // 2. สาขา (Branches)
        $branches = [
            [
                'id'         => 1,
                'tenant_id'  => $tenantId,
                'code'       => 'HQ',
                'name'       => 'สาขาใหญ่ (Main Store)',
                'phone'      => '02-123-4567',
                'address'    => '99/1 ถ.สุขุมวิท แขวงคลองเตย เขตคลองเตย กรุงเทพฯ 10110',
                'is_main'    => 1,
                'is_active'  => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id'         => 2,
                'tenant_id'  => $tenantId,
                'code'       => 'BR1',
                'name'       => 'สาขาเซ็นทรัล (Mall Branch)',
                'phone'      => '02-987-6543',
                'address'    => 'ห้างสรรพสินค้าชั้น 3 ห้อง 301',
                'is_main'    => 0,
                'is_active'  => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];
        $this->db->table('branches')->insertBatch($branches);

        // 3. คลังสินค้าและชั้นวาง (Warehouses Tree: parent_id)
        $warehouses = [
            // สาขา 1: คลังหลัก
            [
                'id'         => 1,
                'tenant_id'  => $tenantId,
                'branch_id'  => 1,
                'parent_id'  => null,
                'name'       => 'คลังหน้าร้าน (Main Storefront)',
                'code'       => 'WH-FRONT',
                'type'       => 'warehouse',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            // ชั้นวางย่อยภายใต้คลังหน้าร้าน (parent_id = 1)
            [
                'id'         => 2,
                'tenant_id'  => $tenantId,
                'branch_id'  => 1,
                'parent_id'  => 1,
                'name'       => 'ชั้นวางอะไหล่ A (Shelf A)',
                'code'       => 'SHELF-A',
                'type'       => 'shelf',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            // ช่องเก็บย่อยภายใต้ชั้นวาง A (parent_id = 2)
            [
                'id'         => 3,
                'tenant_id'  => $tenantId,
                'branch_id'  => 1,
                'parent_id'  => 2,
                'name'       => 'ช่องเก็บ 01 (Bin 01 - จอ iPhone)',
                'code'       => 'BIN-01',
                'type'       => 'bin',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            // คลังหลังร้าน (parent_id = null)
            [
                'id'         => 4,
                'tenant_id'  => $tenantId,
                'branch_id'  => 1,
                'parent_id'  => null,
                'name'       => 'คลังสต็อกหลังร้าน (Backstock Warehouse)',
                'code'       => 'WH-BACK',
                'type'       => 'warehouse',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            // คลังสาขา 2 (สาขาเซ็นทรัล)
            [
                'id'         => 5,
                'tenant_id'  => $tenantId,
                'branch_id'  => 2,
                'parent_id'  => null,
                'name'       => 'คลังสาขาเซ็นทรัล (Mall Branch Warehouse)',
                'code'       => 'WH-MALL',
                'type'       => 'warehouse',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];
        $this->db->table('warehouses')->insertBatch($warehouses);

        // 4. หมวดหมู่สินค้า/อะไหล่ (Categories)
        $categories = [
            ['id' => 1, 'tenant_id' => $tenantId, 'name' => 'อะไหล่มือถือ (Mobile Parts)', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'tenant_id' => $tenantId, 'name' => 'อุปกรณ์เสริม (Accessories)', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'tenant_id' => $tenantId, 'name' => 'บริการและค่าแรง (Services)', 'created_at' => $now, 'updated_at' => $now],
        ];
        $this->db->table('categories')->insertBatch($categories);

        // 5. สินค้าและอะไหล่ (Products - Hybrid Serial)
        $products = [
            [
                'id'          => 1,
                'tenant_id'   => $tenantId,
                'category_id' => 2,
                'sku'         => 'ACC-FILM-01',
                'barcode'     => '8850011223344',
                'name'        => 'ฟิล์มกระจกนิรภัย 9D (Tempered Glass)',
                'unit'        => 'ชิ้น',
                'cost_price'  => 30.00,
                'sell_price'  => 120.00,
                'has_serial'  => 0, // นับ Qty ทั่วไป
                'track_stock' => 1,
                'is_active'   => 1,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'id'          => 2,
                'tenant_id'   => $tenantId,
                'category_id' => 1,
                'sku'         => 'PART-IP13-SCR',
                'barcode'     => '8850011225566',
                'name'        => 'ชุดหน้าจอแท้ iPhone 13 (OLED Screen)',
                'unit'        => 'ชิ้น',
                'cost_price'  => 1800.00,
                'sell_price'  => 3200.00,
                'has_serial'  => 1, // ต้องบันทึก Serial/IMEI รายชิ้น
                'track_stock' => 1,
                'is_active'   => 1,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'id'          => 3,
                'tenant_id'   => $tenantId,
                'category_id' => 1,
                'sku'         => 'PART-SS22-BAT',
                'barcode'     => '8850011227788',
                'name'        => 'แบตเตอรี่แท้ Samsung S22 3700mAh',
                'unit'        => 'ชิ้น',
                'cost_price'  => 650.00,
                'sell_price'  => 1400.00,
                'has_serial'  => 1, // ต้องบันทึก Serial
                'track_stock' => 1,
                'is_active'   => 1,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'id'          => 4,
                'tenant_id'   => $tenantId,
                'category_id' => 2,
                'sku'         => 'ACC-CHG-65W',
                'barcode'     => '8850011229900',
                'name'        => 'หัวชาร์จเร็ว GaN 65W Fast Charger',
                'unit'        => 'ชิ้น',
                'cost_price'  => 180.00,
                'sell_price'  => 450.00,
                'has_serial'  => 0, // นับ Qty ปกติ
                'track_stock' => 1,
                'is_active'   => 1,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'id'          => 5,
                'tenant_id'   => $tenantId,
                'category_id' => 3, // บริการและค่าแรง
                'sku'         => 'SRV-LABOR-01',
                'barcode'     => '8850011229955',
                'name'        => 'ค่าแรงบริการช่างตรวจเช็คและทำความสะอาด',
                'unit'        => 'ครั้ง',
                'cost_price'  => 0.00,
                'sell_price'  => 300.00,
                'has_serial'  => 0,
                'track_stock' => 0, // ไม่ตัดสต็อก (บริการ/ค่าแรง)
                'is_active'   => 1,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'id'          => 6,
                'tenant_id'   => $tenantId,
                'category_id' => 3,
                'sku'         => 'SRV-FILM-INST',
                'barcode'     => '8850011229966',
                'name'        => 'ค่าบริการติดฟิล์มและลอกคราบกาว UV',
                'unit'        => 'ครั้ง',
                'cost_price'  => 0.00,
                'sell_price'  => 80.00,
                'has_serial'  => 0,
                'track_stock' => 0, // ไม่ตัดสต็อก (บริการ)
                'is_active'   => 1,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
        ];
        $this->db->table('products')->insertBatch($products);

        // 6. Serial Numbers สำหรับสินค้า Hybrid (Product Serials)
        $serials = [
            [
                'id'           => 1,
                'tenant_id'    => $tenantId,
                'product_id'   => 2, // หน้าจอ iPhone 13
                'branch_id'    => 1,
                'warehouse_id' => 3, // เก็บที่ช่องเก็บ 01
                'serial_no'    => 'SCR-IP13-TH001',
                'status'       => 'in_stock',
                'notes'        => 'หน้าจอแท้ศูนย์ ประกัน 6 เดือน',
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'id'           => 2,
                'tenant_id'    => $tenantId,
                'product_id'   => 2,
                'branch_id'    => 1,
                'warehouse_id' => 3,
                'serial_no'    => 'SCR-IP13-TH002',
                'status'       => 'in_stock',
                'notes'        => 'หน้าจอแท้ศูนย์ ประกัน 6 เดือน',
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'id'           => 3,
                'tenant_id'    => $tenantId,
                'product_id'   => 3, // แบตเตอรี่ Samsung
                'branch_id'    => 1,
                'warehouse_id' => 3,
                'serial_no'    => 'BAT-SS22-KR881',
                'status'       => 'in_stock',
                'notes'        => 'แบตเตอรี่แท้ มอก.',
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
        ];
        $this->db->table('product_serials')->insertBatch($serials);

        // 7. ประวัติความเคลื่อนไหวสต็อก (Stock Transactions Ledger)
        $stockTxns = [
            [
                'tenant_id'     => $tenantId,
                'branch_id'     => 1,
                'warehouse_id'  => 1,
                'product_id'    => 1, // ฟิล์ม
                'serial_id'     => null,
                'movement_type' => 'direct_in',
                'qty'           => 100.00,
                'cost_price'    => 30.00,
                'ref_type'      => 'manual',
                'ref_id'        => null,
                'notes'         => 'ยกยอดสต็อกตั้งต้นหน้าร้าน',
                'created_by'    => 2,
                'created_at'    => $now,
            ],
            [
                'tenant_id'     => $tenantId,
                'branch_id'     => 1,
                'warehouse_id'  => 3,
                'product_id'    => 2, // หน้าจอ iPhone
                'serial_id'     => 1,
                'movement_type' => 'direct_in',
                'qty'           => 1.00,
                'cost_price'    => 1800.00,
                'ref_type'      => 'manual',
                'ref_id'        => null,
                'notes'         => 'รับเข้าหน้าจอ SN: SCR-IP13-TH001',
                'created_by'    => 2,
                'created_at'    => $now,
            ],
            [
                'tenant_id'     => $tenantId,
                'branch_id'     => 1,
                'warehouse_id'  => 3,
                'product_id'    => 2,
                'serial_id'     => 2,
                'movement_type' => 'direct_in',
                'qty'           => 1.00,
                'cost_price'    => 1800.00,
                'ref_type'      => 'manual',
                'ref_id'        => null,
                'notes'         => 'รับเข้าหน้าจอ SN: SCR-IP13-TH002',
                'created_by'    => 2,
                'created_at'    => $now,
            ],
            [
                'tenant_id'     => $tenantId,
                'branch_id'     => 1,
                'warehouse_id'  => 3,
                'product_id'    => 3, // แบตเตอรี่ Samsung
                'serial_id'     => 3,
                'movement_type' => 'direct_in',
                'qty'           => 1.00,
                'cost_price'    => 650.00,
                'ref_type'      => 'manual',
                'ref_id'        => null,
                'notes'         => 'รับเข้าแบตเตอรี่ SN: BAT-SS22-KR881',
                'created_by'    => 2,
                'created_at'    => $now,
            ],
            [
                'tenant_id'     => $tenantId,
                'branch_id'     => 1,
                'warehouse_id'  => 1,
                'product_id'    => 4, // หัวชาร์จ
                'serial_id'     => null,
                'movement_type' => 'direct_in',
                'qty'           => 50.00,
                'cost_price'    => 180.00,
                'ref_type'      => 'manual',
                'ref_id'        => null,
                'notes'         => 'ยกยอดสต็อกตั้งต้นหน้าร้าน',
                'created_by'    => 2,
                'created_at'    => $now,
            ],
        ];
        $this->db->table('stock_transactions')->insertBatch($stockTxns);

        // 8. กระเป๋าเงินลูกค้า / คู่ค้า (Wallets: Store Credit & Debt)
        $wallets = [
            [
                'id'             => 1,
                'tenant_id'      => $tenantId,
                'party_type'     => 'customer',
                'party_id'       => 2, // คุณสมชาย (Customer)
                'credit_balance' => 500.00, // มีวงเงินฝากสะสม 500 บาท ไว้ซื้อรอบหน้า
                'debt_balance'   => 0.00,
                'credit_limit'   => 2000.00, // วงเงินเชื่อที่ยอมให้ติดเงินได้
                'created_at'     => $now,
                'updated_at'     => $now,
            ],
            [
                'id'             => 2,
                'tenant_id'      => $tenantId,
                'party_type'     => 'supplier',
                'party_id'       => 99, // บริษัท สยามอะไหล่ จำกัด
                'credit_balance' => 0.00,
                'debt_balance'   => 3600.00, // ร้านติดหนี้ค่าอะไหล่ Supply อยู่ 3,600 บาท
                'credit_limit'   => 20000.00,
                'created_at'     => $now,
                'updated_at'     => $now,
            ],
        ];
        $this->db->table('wallets')->insertBatch($wallets);

        // ประวัติเดินบัญชีกระเป๋าเงิน (Wallet Transactions)
        $this->db->table('wallet_transactions')->insert([
            'wallet_id'     => 1,
            'tenant_id'     => $tenantId,
            'type'          => 'credit_deposit',
            'amount'        => 500.00,
            'balance_after' => 500.00,
            'ref_type'      => 'return_refund',
            'ref_id'        => null,
            'notes'         => 'คืนสินค้าแต่ไม่รับเงินสด โอนเข้าเป็นวงเงินฝากสะสม (Store Credit)',
            'created_by'    => 2,
            'created_at'    => $now,
        ]);

        // 9. ธุรกรรมการเงินส่วนกลาง (Financial Transactions)
        $finTxns = [
            [
                'tenant_id'      => $tenantId,
                'branch_id'      => 1,
                'type'           => 'income',
                'category'       => 'pos_sale',
                'payment_method' => 'cash',
                'amount'         => 350.00,
                'ref_type'       => 'pos_sale',
                'ref_id'         => 101,
                'notes'          => 'ขายฟิล์มกระจก 2 ชิ้น และสายชาร์จ',
                'created_by'     => 2,
                'created_at'     => $now,
            ],
            [
                'tenant_id'      => $tenantId,
                'branch_id'      => 1,
                'type'           => 'income',
                'category'       => 'repair_deposit',
                'payment_method' => 'bank_transfer',
                'amount'         => 1000.00,
                'ref_type'       => 'repair_job',
                'ref_id'         => 1,
                'notes'          => 'รับเงินมัดจำใบแจ้งซ่อม RP2609-0001 (โอนเข้าบัญชีกสิกรไทย)',
                'created_by'     => 2,
                'created_at'     => $now,
            ],
            [
                'tenant_id'      => $tenantId,
                'branch_id'      => 1,
                'type'           => 'expense',
                'category'       => 'shop_expense',
                'payment_method' => 'cash',
                'amount'         => 120.00,
                'ref_type'       => 'expense_voucher',
                'ref_id'         => null,
                'notes'          => 'ซื้อน้ำยาเช็ดกระจกและกระดาษทิชชู่หน้าร้าน',
                'created_by'     => 2,
                'created_at'     => $now,
            ],
        ];
        $this->db->table('financial_transactions')->insertBatch($finTxns);

        // 10. กะหน้าร้าน (Cash Shift)
        $this->db->table('cash_shifts')->insert([
            'tenant_id'            => $tenantId,
            'branch_id'            => 1,
            'user_id'              => 2,
            'opened_at'            => $now,
            'closed_at'            => null,
            'opening_cash'         => 2000.00, // เงินทอนตั้งต้น
            'cash_sales'           => 350.00,
            'cash_expenses'        => 120.00,
            'cash_drops'           => 0.00,
            'expected_cash'        => 2230.00, // 2000 + 350 - 120
            'closing_cash_counted' => null,
            'difference'           => null,
            'status'               => 'open',
            'notes'                => 'กะเช้าเปิดบริการตามปกติ',
        ]);

        // 11. ระบบบริหารงานซ่อม (Repair Jobs, Items, Logs)
        $jobToken = 'trk_demo_' . bin2hex(random_bytes(8));
        $this->db->table('repair_jobs')->insert([
            'id'                => 1,
            'tenant_id'         => $tenantId,
            'branch_id'         => 1,
            'job_no'            => 'RP2609-0001',
            'tracking_token'    => $jobToken,
            'customer_id'       => 2,
            'customer_name'     => 'คุณสมชาย ใจดี',
            'customer_phone'    => '089-999-8888',
            'device_type'       => 'Smartphone',
            'brand'             => 'Apple',
            'model'             => 'iPhone 13 128GB',
            'serial_imei'       => '356789101112131',
            'color'             => 'Midnight Blue',
            'passcode'          => '123456',
            'accessories'       => 'ตัวเครื่อง + เคสใส (ไม่มีสายชาร์จ)',
            'problem_reported'  => 'หน้าจอแตก มีเส้นเขียว ทัชสกรีนรวนบางจุด',
            'technician_notes'  => 'แกะเช็ค บอร์ดปกติ แบตเตอรี่สุขภาพ 88% ต้องเปลี่ยนชุดหน้าจอแท้',
            'status'            => 'in_progress',
            'assigned_to'       => 3, // ช่างเทคนิค (User 3)
            'estimated_price'   => 3700.00,
            'deposit_amount'    => 1000.00, // วางเงินมัดจำแล้ว
            'deposit_no'        => 'DP2609-0001',
            'total_parts_price' => 3200.00,
            'total_labor_price' => 500.00,
            'discount_amount'   => 0.00,
            'net_total'         => 3700.00,
            'net_payable'       => 2700.00, // ยอดคงเหลือที่ต้องชำระจริงหลังหักมัดจำ
            'paid_amount'       => 1000.00,
            'payment_status'    => 'partial',
            'warranty_days'     => 90,
            'delivered_at'      => null,
            'created_at'        => $now,
            'updated_at'        => $now,
        ]);

        // รายการอะไหล่ & ค่าแรง
        $repairItems = [
            [
                'repair_job_id' => 1,
                'item_type'     => 'part',
                'product_id'    => 2, // จอ iPhone
                'serial_id'     => 1, // SN: SCR-IP13-TH001
                'item_name'     => 'ชุดหน้าจอแท้ iPhone 13 (OLED)',
                'qty'           => 1.00,
                'cost_price'    => 1800.00,
                'unit_price'    => 3200.00,
                'total_price'   => 3200.00,
                'status'        => 'approved', // อนุมัติเบิกแล้ว
                'approved_by'   => 2,
                'approved_at'   => $now,
                'returned_at'   => null,
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'repair_job_id' => 1,
                'item_type'     => 'service',
                'product_id'    => null,
                'serial_id'     => null,
                'item_name'     => 'ค่าแรงและตรวจเช็คระบบ',
                'qty'           => 1.00,
                'cost_price'    => 0.00,
                'unit_price'    => 500.00,
                'total_price'   => 500.00,
                'status'        => 'approved',
                'approved_by'   => 2,
                'approved_at'   => $now,
                'returned_at'   => null,
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
        ];
        $this->db->table('repair_items')->insertBatch($repairItems);

        // ประวัติการเปลี่ยน State ของงานซ่อม (Audit & Reject Trail)
        $repairLogs = [
            ['repair_job_id' => 1, 'from_status' => null,          'to_status' => 'received',    'notes' => 'รับเครื่องและออกใบรับเครื่องซ่อม', 'created_by' => 2, 'created_at' => $now],
            ['repair_job_id' => 1, 'from_status' => 'received',    'to_status' => 'checking',    'notes' => 'ช่างรับเครื่องไปตรวจเช็คอย่างละเอียด', 'created_by' => 3, 'created_at' => $now],
            ['repair_job_id' => 1, 'from_status' => 'checking',    'to_status' => 'quoted',      'notes' => 'ประเมินราคา 3,700 บาท แจ้งลูกค้าผ่านโทรศัพท์', 'created_by' => 2, 'created_at' => $now],
            ['repair_job_id' => 1, 'from_status' => 'quoted',      'to_status' => 'approved',    'notes' => 'ลูกค้าอนุมัติซ่อม และโอนเงินมัดจำ 1,000 บาท', 'created_by' => 2, 'created_at' => $now],
            ['repair_job_id' => 1, 'from_status' => 'approved',    'to_status' => 'in_progress', 'notes' => 'เบิกอะไหล่หน้าจอ SN: SCR-IP13-TH001 และลงมือเปลี่ยน', 'created_by' => 3, 'created_at' => $now],
        ];
        $this->db->table('repair_status_logs')->insertBatch($repairLogs);

        // 12. การตั้งค่าความยืดหยุ่น Onboarding Wizard ประจำร้าน (Tenant Settings)
        $settings = [
            ['tenant_id' => $tenantId, 'setting_key' => 'workflow_mode',          'setting_value' => 'simple',         'created_at' => $now, 'updated_at' => $now],
            ['tenant_id' => $tenantId, 'setting_key' => 'costing_method',         'setting_value' => 'moving_average', 'created_at' => $now, 'updated_at' => $now],
            ['tenant_id' => $tenantId, 'setting_key' => 'inventory_costing_method', 'setting_value' => 'moving_average', 'created_at' => $now, 'updated_at' => $now],
            ['tenant_id' => $tenantId, 'setting_key' => 'landed_cost_method',       'setting_value' => 'by_value',       'created_at' => $now, 'updated_at' => $now],
            ['tenant_id' => $tenantId, 'setting_key' => 'enable_landed_cost',     'setting_value' => '1',              'created_at' => $now, 'updated_at' => $now],
            ['tenant_id' => $tenantId, 'setting_key' => 'require_cash_shift',     'setting_value' => '1',              'created_at' => $now, 'updated_at' => $now],
            ['tenant_id' => $tenantId, 'setting_key' => 'parts_approval_required', 'setting_value' => '1',              'created_at' => $now, 'updated_at' => $now],
            ['tenant_id' => $tenantId, 'setting_key' => 'enable_serial_tracking',  'setting_value' => '1',              'created_at' => $now, 'updated_at' => $now],
            ['tenant_id' => $tenantId, 'setting_key' => 'enable_wallet_credit',    'setting_value' => '1',              'created_at' => $now, 'updated_at' => $now],
            ['tenant_id' => $tenantId, 'setting_key' => 'enable_direct_inbound',   'setting_value' => '1',              'created_at' => $now, 'updated_at' => $now],
            ['tenant_id' => $tenantId, 'setting_key' => 'is_onboarded',           'setting_value' => '1',              'created_at' => $now, 'updated_at' => $now],
        ];
        $this->db->table('tenant_settings')->insertBatch($settings);

        // 13. รูปแบบการพิมพ์ (Print Templates: A4, A5, Slip 80mm, Barcode)
        $templates = [
            [
                'tenant_id'       => $tenantId,
                'doc_type'        => 'repair_ticket',
                'paper_size'      => 'a5_landscape',
                'header_title'    => 'ใบรับเครื่องซ่อม (Repair Service Ticket)',
                'header_logo_url' => '/assets/img/logo.png',
                'header_info'     => json_encode([
                    'shop_name' => 'Demo Shop - บริการซ่อมมือถือและคอมพิวเตอร์ครบวงจร',
                    'tax_id'    => '0105559998881',
                    'phone'     => '02-123-4567, 089-999-8888',
                    'address'   => '99/1 ถ.สุขุมวิท แขวงคลองเตย เขตคลองเตย กทม.',
                ]),
                'footer_terms'    => '1. กรุณานำใบรับเครื่องนี้มาแสดงทุกครั้งที่มารับเครื่องคืน\n2. กรณีไม่มารับเครื่องคืนภายใน 60 วัน ทางร้านขอสงวนสิทธิ์ในการจัดการตามระเบียบ\n3. การรับประกัน 90 วัน ไม่ครอบคลุมเครื่องตกน้ำ จอแตก หรือถูกแกะโดยบุคคลภายนอก',
                'footer_notes'    => 'ขอบพระคุณที่ไว้วางใจใช้บริการ | สแกน QR Code เพื่อตรวจสอบสถานะงานซ่อมแบบเรียลไทม์ได้ตลอด 24 ชม.',
                'show_options'    => json_encode([
                    'show_imei'       => true,
                    'show_passcode'   => false, // ปิดการพิมพ์ passcode ลงบนใบเสร็จเพื่อความเป็นส่วนตัว
                    'show_deposit'    => true,
                    'show_qr_tracking'=> true,
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'tenant_id'       => $tenantId,
                'doc_type'        => 'pos_receipt',
                'paper_size'      => 'slip_80mm',
                'header_title'    => 'ใบเสร็จรับเงิน / ใบกำกับภาษีอย่างย่อ',
                'header_logo_url' => null,
                'header_info'     => json_encode([
                    'shop_name' => 'Demo Shop หน้าร้าน',
                    'tax_id'    => '0105559998881',
                    'branch'    => 'สาขาใหญ่ (00000)',
                ]),
                'footer_terms'    => 'สินค้าซื้อแล้วสามารถเปลี่ยนได้ภายใน 7 วัน พร้อมใบเสร็จนี้',
                'footer_notes'    => 'Thank You for Shopping With Us!',
                'show_options'    => json_encode(['show_cashier' => true, 'show_barcode' => true]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'tenant_id'       => $tenantId,
                'doc_type'        => 'invoice',
                'paper_size'      => 'a4_portrait',
                'header_title'    => 'ใบแจ้งหนี้ / ใบวางบิล (Invoice / Billing Note)',
                'header_logo_url' => '/assets/img/logo.png',
                'header_info'     => json_encode([
                    'company_name' => 'บริษัท เดโม ช็อป จำกัด (สำนักงานใหญ่)',
                    'tax_id'       => '0105559998881',
                ]),
                'footer_terms'    => 'กรุณาโอนเงินเข้าบัญชี ธนาคารกสิกรไทย เลขที่ 123-4-56789-0 ชื่อบัญชี บจก.เดโม ช็อป',
                'footer_notes'    => 'เอกสารนี้จะสมบูรณ์เมื่อบริษัทฯ ได้รับการชำระเงินเรียบร้อยแล้ว',
                'show_options'    => json_encode(['show_bank_info' => true, 'show_signatures' => true]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
        ];
        $this->db->table('print_templates')->insertBatch($templates);

        // 14. กลุ่มระดับราคา (Price Tiers)
        $tiers = [
            [
                'id'           => 1,
                'tenant_id'    => $tenantId,
                'code'         => 'retail',
                'name'         => 'ราคาปลีกทั่วไป (Retail)',
                'calc_type'    => 'fixed',
                'default_rate' => 0.00,
                'is_default'   => 1,
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'id'           => 2,
                'tenant_id'    => $tenantId,
                'code'         => 'technician',
                'name'         => 'ราคาช่างซ่อม (Technician)',
                'calc_type'    => 'discount_percent', // ลด 15% จากราคาขายหลัก
                'default_rate' => 15.00,
                'is_default'   => 0,
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'id'           => 3,
                'tenant_id'    => $tenantId,
                'code'         => 'wholesale',
                'name'         => 'ราคาส่ง (Wholesale)',
                'calc_type'    => 'cost_plus_percent', // ต้นทุน + 15%
                'default_rate' => 15.00,
                'is_default'   => 0,
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'id'           => 4,
                'tenant_id'    => $tenantId,
                'code'         => 'vip',
                'name'         => 'ลูกค้า VIP',
                'calc_type'    => 'discount_amount', // ลด 100 บาทจากราคาขายหลัก
                'default_rate' => 100.00,
                'is_default'   => 0,
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
        ];
        $this->db->table('price_tiers')->insertBatch($tiers);

        // 15. การ Override ราคาพิเศษเฉพาะสินค้า (Product Tier Prices)
        // หน้าจอ iPhone 13 (Product ID: 2, ราคาขายปกติ 3,200 บาท)
        // ให้ราคาพิเศษเฉพาะช่างซ่อม (Tier 2): กำหนดตายตัว (fixed) 2,800 บาท
        $this->db->table('product_tier_prices')->insert([
            'tenant_id'  => $tenantId,
            'product_id' => 2,
            'tier_id'    => 2, // technician
            'calc_type'  => 'fixed',
            'value'      => 2800.00,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // 16. ใบรับเงินมัดจำงานซ่อม (Repair Deposits)
        $this->db->table('repair_deposits')->insert([
            'id'             => 1,
            'tenant_id'      => $tenantId,
            'repair_job_id'  => 1,
            'deposit_no'     => 'DP2609-0001',
            'amount'         => 1000.00,
            'payment_method' => 'bank_transfer',
            'receipt_no'     => 'DP-REC-260901',
            'notes'          => 'รับเงินมัดจำค่าจอ iPhone 13 โอนเข้ากสิกรไทย',
            'received_by'    => 2,
            'received_at'    => $now,
            'created_at'     => $now,
            'updated_at'     => $now,
        ]);
    }
}
