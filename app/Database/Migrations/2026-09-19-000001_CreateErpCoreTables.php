<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateErpCoreTables extends Migration
{
    public function up()
    {
        // 1. ตาราง branches (สาขา)
        $this->forge->addField([
            'id'         => ['type' => 'INTEGER', 'constraint' => 11, 'auto_increment' => true],
            'tenant_id'  => ['type' => 'INTEGER', 'constraint' => 11],
            'code'       => ['type' => 'VARCHAR', 'constraint' => 20],
            'name'       => ['type' => 'VARCHAR', 'constraint' => 100],
            'phone'      => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'address'    => ['type' => 'TEXT', 'null' => true],
            'is_main'    => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'is_active'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['tenant_id', 'code']);
        $this->forge->createTable('branches', true);

        // 2. ตาราง warehouses (คลัง/โซน/ชั้นวาง โครงสร้างต้นไม้ parent_id)
        $this->forge->addField([
            'id'         => ['type' => 'INTEGER', 'constraint' => 11, 'auto_increment' => true],
            'tenant_id'  => ['type' => 'INTEGER', 'constraint' => 11],
            'branch_id'  => ['type' => 'INTEGER', 'constraint' => 11],
            'parent_id'  => ['type' => 'INTEGER', 'constraint' => 11, 'null' => true],
            'name'       => ['type' => 'VARCHAR', 'constraint' => 100],
            'code'       => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'type'       => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'warehouse'], // warehouse, zone, shelf, bin
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['tenant_id', 'branch_id']);
        $this->forge->addKey('parent_id');
        $this->forge->createTable('warehouses', true);

        // 3. ตาราง categories (หมวดหมู่สินค้า/อะไหล่)
        $this->forge->addField([
            'id'         => ['type' => 'INTEGER', 'constraint' => 11, 'auto_increment' => true],
            'tenant_id'  => ['type' => 'INTEGER', 'constraint' => 11],
            'name'       => ['type' => 'VARCHAR', 'constraint' => 100],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('tenant_id');
        $this->forge->createTable('categories', true);

        // 4. ตาราง products (สินค้า/อะไหล่ รองรับ Hybrid Serial)
        $this->forge->addField([
            'id'          => ['type' => 'INTEGER', 'constraint' => 11, 'auto_increment' => true],
            'tenant_id'   => ['type' => 'INTEGER', 'constraint' => 11],
            'category_id' => ['type' => 'INTEGER', 'constraint' => 11, 'null' => true],
            'sku'         => ['type' => 'VARCHAR', 'constraint' => 50],
            'barcode'     => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'name'        => ['type' => 'VARCHAR', 'constraint' => 150],
            'unit'        => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'ชิ้น'],
            'cost_price'     => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0.00],
            'sell_price'     => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0.00],
            'has_serial'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0], // 0 = Qty, 1 = Serial Tracking
            'track_stock'    => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1], // 1 = ตัดสต็อก (สินค้ามีสต็อก), 0 = ไม่ตัดสต็อก (บริการ/ค่าแรง/ซอฟต์แวร์)
            'costing_method' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true], // null=use tenant default, moving_average, latest_cost, highest_cost, manual
            'is_active'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['tenant_id', 'sku']);
        $this->forge->createTable('products', true);

        // 5. ตาราง product_serials (Serial Number / IMEI รายชิ้น)
        $this->forge->addField([
            'id'           => ['type' => 'INTEGER', 'constraint' => 11, 'auto_increment' => true],
            'tenant_id'    => ['type' => 'INTEGER', 'constraint' => 11],
            'product_id'   => ['type' => 'INTEGER', 'constraint' => 11],
            'branch_id'    => ['type' => 'INTEGER', 'constraint' => 11],
            'warehouse_id' => ['type' => 'INTEGER', 'constraint' => 11, 'null' => true],
            'serial_no'    => ['type' => 'VARCHAR', 'constraint' => 100],
            'status'       => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'in_stock'], // in_stock, reserved, used_in_repair, sold, defective, claimed
            'notes'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['tenant_id', 'serial_no']);
        $this->forge->addKey(['product_id', 'status']);
        $this->forge->createTable('product_serials', true);

        // 6. ตาราง stock_transactions (บันทึกประวัติการเคลื่อนไหวสต็อก Immutable Ledger)
        $this->forge->addField([
            'id'            => ['type' => 'INTEGER', 'constraint' => 11, 'auto_increment' => true],
            'tenant_id'     => ['type' => 'INTEGER', 'constraint' => 11],
            'branch_id'     => ['type' => 'INTEGER', 'constraint' => 11],
            'warehouse_id'  => ['type' => 'INTEGER', 'constraint' => 11],
            'product_id'    => ['type' => 'INTEGER', 'constraint' => 11],
            'serial_id'     => ['type' => 'INTEGER', 'constraint' => 11, 'null' => true],
            'movement_type' => ['type' => 'VARCHAR', 'constraint' => 50], // direct_in, po_receive, pos_sale, repair_used, repair_returned, transfer_out, transfer_in, adjust_in, adjust_out
            'qty'           => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'cost_price'    => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0.00],
            'ref_type'      => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true], // repair_job, pos_order, purchase_order, goods_receipt, manual
            'ref_id'        => ['type' => 'INTEGER', 'constraint' => 11, 'null' => true],
            'notes'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_by'    => ['type' => 'INTEGER', 'constraint' => 11, 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['tenant_id', 'product_id']);
        $this->forge->addKey(['tenant_id', 'warehouse_id']);
        $this->forge->createTable('stock_transactions', true);

        // 7. ตาราง purchase_orders (ใบสั่งซื้อ สำหรับร้านที่เป็นระบบ)
        $this->forge->addField([
            'id'            => ['type' => 'INTEGER', 'constraint' => 11, 'auto_increment' => true],
            'tenant_id'     => ['type' => 'INTEGER', 'constraint' => 11],
            'branch_id'     => ['type' => 'INTEGER', 'constraint' => 11],
            'po_no'         => ['type' => 'VARCHAR', 'constraint' => 50],
            'supplier_name' => ['type' => 'VARCHAR', 'constraint' => 150],
            'supplier_id'   => ['type' => 'INTEGER', 'constraint' => 11, 'null' => true],
            'status'         => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'pending'], // pending, partial, completed, cancelled
            'total_amount'   => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0.00],
            'shipping_cost'  => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0.00], // ค่าขนส่ง
            'other_expenses' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0.00], // ค่าใช้จ่ายอื่นๆ (เช่น ภาษี/ค่าดำเนินการ)
            'notes'          => ['type' => 'TEXT', 'null' => true],
            'created_by'    => ['type' => 'INTEGER', 'constraint' => 11, 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['tenant_id', 'po_no']);
        $this->forge->createTable('purchase_orders', true);

        // 8. ตาราง purchase_order_items (รายการในใบสั่งซื้อ)
        $this->forge->addField([
            'id'                => ['type' => 'INTEGER', 'constraint' => 11, 'auto_increment' => true],
            'purchase_order_id' => ['type' => 'INTEGER', 'constraint' => 11],
            'product_id'        => ['type' => 'INTEGER', 'constraint' => 11],
            'qty_ordered'       => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'qty_received'      => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0.00],
            'unit_cost'         => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0.00],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
            'updated_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('purchase_order_id');
        $this->forge->createTable('purchase_order_items', true);

        // 9. ตาราง goods_receipts (ใบตรวจรับสินค้า GRN เช็คของขาด/เกิน)
        $this->forge->addField([
            'id'                => ['type' => 'INTEGER', 'constraint' => 11, 'auto_increment' => true],
            'tenant_id'         => ['type' => 'INTEGER', 'constraint' => 11],
            'branch_id'         => ['type' => 'INTEGER', 'constraint' => 11],
            'warehouse_id'      => ['type' => 'INTEGER', 'constraint' => 11],
            'grn_no'            => ['type' => 'VARCHAR', 'constraint' => 50],
            'purchase_order_id' => ['type' => 'INTEGER', 'constraint' => 11, 'null' => true], // null ถ้าเป็น Direct Inbound
            'shipping_cost'     => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0.00], // ค่าขนส่ง
            'other_expenses'    => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0.00], // ค่าใช้จ่ายอื่นๆ
            'received_date'     => ['type' => 'DATETIME'],
            'received_by'       => ['type' => 'INTEGER', 'constraint' => 11, 'null' => true],
            'notes'             => ['type' => 'TEXT', 'null' => true],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
            'updated_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['tenant_id', 'grn_no']);
        $this->forge->createTable('goods_receipts', true);

        // 10. ตาราง goods_receipt_items
        $this->forge->addField([
            'id'               => ['type' => 'INTEGER', 'constraint' => 11, 'auto_increment' => true],
            'goods_receipt_id' => ['type' => 'INTEGER', 'constraint' => 11],
            'product_id'       => ['type' => 'INTEGER', 'constraint' => 11],
            'qty_received'     => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'unit_cost'        => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0.00],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('goods_receipt_id');
        $this->forge->createTable('goods_receipt_items', true);

        // 11. ตาราง wallets (กระเป๋าเงินลูกค้า / Supplier: วงเงินฝาก Store Credit & หนี้ค้างชำระ Debt)
        $this->forge->addField([
            'id'             => ['type' => 'INTEGER', 'constraint' => 11, 'auto_increment' => true],
            'tenant_id'      => ['type' => 'INTEGER', 'constraint' => 11],
            'party_type'     => ['type' => 'VARCHAR', 'constraint' => 20], // customer, supplier
            'party_id'       => ['type' => 'INTEGER', 'constraint' => 11], // peoples.id
            'credit_balance' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0.00], // วงเงินฝากสะสม (Store Credit)
            'debt_balance'   => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0.00], // หนี้ค้างชำระ (ติดเงินก่อน)
            'credit_limit'   => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0.00], // วงเงินเชื่อสูงสุด
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['tenant_id', 'party_type', 'party_id']);
        $this->forge->createTable('wallets', true);

        // 12. ตาราง wallet_transactions (ประวัติเดินบัญชีวงเงิน)
        $this->forge->addField([
            'id'            => ['type' => 'INTEGER', 'constraint' => 11, 'auto_increment' => true],
            'wallet_id'     => ['type' => 'INTEGER', 'constraint' => 11],
            'tenant_id'     => ['type' => 'INTEGER', 'constraint' => 11],
            'type'          => ['type' => 'VARCHAR', 'constraint' => 30], // credit_deposit, credit_use, debt_increase, debt_payment
            'amount'        => ['type' => 'DECIMAL', 'constraint' => '12,2'],
            'balance_after' => ['type' => 'DECIMAL', 'constraint' => '12,2'],
            'ref_type'      => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true], // repair_job, pos_order, return_refund, manual
            'ref_id'        => ['type' => 'INTEGER', 'constraint' => 11, 'null' => true],
            'notes'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_by'    => ['type' => 'INTEGER', 'constraint' => 11, 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('wallet_id');
        $this->forge->createTable('wallet_transactions', true);

        // 13. ตาราง financial_transactions (กระแสเงินสดและเงินโอนทุกรายการในระบบ)
        $this->forge->addField([
            'id'             => ['type' => 'INTEGER', 'constraint' => 11, 'auto_increment' => true],
            'tenant_id'      => ['type' => 'INTEGER', 'constraint' => 11],
            'branch_id'      => ['type' => 'INTEGER', 'constraint' => 11],
            'type'           => ['type' => 'VARCHAR', 'constraint' => 20], // income, expense
            'category'       => ['type' => 'VARCHAR', 'constraint' => 50], // pos_sale, repair_fee, repair_deposit, shop_expense, supplier_payment, credit_topup
            'payment_method' => ['type' => 'VARCHAR', 'constraint' => 30], // cash, bank_transfer, credit_card, store_credit, debt
            'amount'         => ['type' => 'DECIMAL', 'constraint' => '12,2'],
            'ref_type'       => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true], // pos_sale, repair_job, expense_voucher, wallet
            'ref_id'         => ['type' => 'INTEGER', 'constraint' => 11, 'null' => true],
            'notes'          => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_by'     => ['type' => 'INTEGER', 'constraint' => 11, 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['tenant_id', 'branch_id']);
        $this->forge->addKey(['tenant_id', 'type']);
        $this->forge->createTable('financial_transactions', true);

        // 14. ตาราง cash_shifts (กะหน้าร้านและลิ้นชักเงินสด)
        $this->forge->addField([
            'id'                   => ['type' => 'INTEGER', 'constraint' => 11, 'auto_increment' => true],
            'tenant_id'            => ['type' => 'INTEGER', 'constraint' => 11],
            'branch_id'            => ['type' => 'INTEGER', 'constraint' => 11],
            'user_id'              => ['type' => 'INTEGER', 'constraint' => 11],
            'opened_at'            => ['type' => 'DATETIME'],
            'closed_at'            => ['type' => 'DATETIME', 'null' => true],
            'opening_cash'         => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0.00],
            'cash_sales'           => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0.00],
            'cash_expenses'        => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0.00],
            'cash_drops'           => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0.00],
            'expected_cash'        => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0.00],
            'closing_cash_counted' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => true],
            'difference'           => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => true],
            'status'               => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'open'], // open, closed
            'notes'                => ['type' => 'TEXT', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['tenant_id', 'branch_id', 'status']);
        $this->forge->createTable('cash_shifts', true);

        // 15. ตาราง repair_jobs (หัวบิลงานซ่อม)
        $this->forge->addField([
            'id'                 => ['type' => 'INTEGER', 'constraint' => 11, 'auto_increment' => true],
            'tenant_id'          => ['type' => 'INTEGER', 'constraint' => 11],
            'branch_id'          => ['type' => 'INTEGER', 'constraint' => 11],
            'job_no'             => ['type' => 'VARCHAR', 'constraint' => 50],
            'tracking_token'     => ['type' => 'VARCHAR', 'constraint' => 64], // Public QR Code Tracking
            'customer_id'        => ['type' => 'INTEGER', 'constraint' => 11, 'null' => true],
            'customer_name'      => ['type' => 'VARCHAR', 'constraint' => 100],
            'customer_phone'     => ['type' => 'VARCHAR', 'constraint' => 50],
            'device_type'        => ['type' => 'VARCHAR', 'constraint' => 50], // Smartphone, Notebook, etc.
            'brand'              => ['type' => 'VARCHAR', 'constraint' => 50],
            'model'              => ['type' => 'VARCHAR', 'constraint' => 100],
            'serial_imei'        => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'color'              => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'passcode'           => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'accessories'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'problem_reported'   => ['type' => 'TEXT'],
            'technician_notes'   => ['type' => 'TEXT', 'null' => true],
            'status'             => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'received'], // received, checking, quoted, approved, in_progress, repaired, rejected, delivered, cancelled
            'assigned_to'        => ['type' => 'INTEGER', 'constraint' => 11, 'null' => true],
            'estimated_price'    => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0.00],
            'deposit_amount'     => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0.00], // เงินมัดจำ
            'deposit_no'         => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true], // เลขที่ใบรับมัดจำ เช่น DP2609-0001
            'total_parts_price'  => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0.00],
            'total_labor_price'  => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0.00],
            'discount_amount'    => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0.00],
            'net_total'          => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0.00],
            'net_payable'        => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0.00], // ยอดชำระสุทธิหลังหักมัดจำ
            'paid_amount'        => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0.00],
            'final_receipt_no'   => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true], // เลขที่ใบเสร็จสมบูรณ์ เช่น RC2609-0001
            'payment_status'     => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'unpaid'], // unpaid, partial, paid
            'warranty_days'      => ['type' => 'INTEGER', 'constraint' => 11, 'default' => 90],
            'delivered_at'       => ['type' => 'DATETIME', 'null' => true],
            'created_at'         => ['type' => 'DATETIME', 'null' => true],
            'updated_at'         => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['tenant_id', 'job_no']);
        $this->forge->addKey('tracking_token');
        $this->forge->createTable('repair_jobs', true);

        // 16. ตาราง repair_items (รายการอะไหล่และค่าแรง รองรับ request / approve / return)
        $this->forge->addField([
            'id'            => ['type' => 'INTEGER', 'constraint' => 11, 'auto_increment' => true],
            'repair_job_id' => ['type' => 'INTEGER', 'constraint' => 11],
            'item_type'     => ['type' => 'VARCHAR', 'constraint' => 20], // part, service
            'product_id'    => ['type' => 'INTEGER', 'constraint' => 11, 'null' => true],
            'serial_id'     => ['type' => 'INTEGER', 'constraint' => 11, 'null' => true],
            'item_name'     => ['type' => 'VARCHAR', 'constraint' => 150],
            'qty'           => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 1.00],
            'cost_price'    => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0.00],
            'unit_price'    => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0.00],
            'total_price'   => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0.00],
            'status'        => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'requested'], // requested, approved, used, returned
            'approved_by'   => ['type' => 'INTEGER', 'constraint' => 11, 'null' => true],
            'approved_at'   => ['type' => 'DATETIME', 'null' => true],
            'returned_at'   => ['type' => 'DATETIME', 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('repair_job_id');
        $this->forge->createTable('repair_items', true);

        // 17. ตาราง repair_status_logs (ประวัติการเปลี่ยนสถานะงานซ่อม รองรับ Reject ตีกลับ)
        $this->forge->addField([
            'id'            => ['type' => 'INTEGER', 'constraint' => 11, 'auto_increment' => true],
            'repair_job_id' => ['type' => 'INTEGER', 'constraint' => 11],
            'from_status'   => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'to_status'     => ['type' => 'VARCHAR', 'constraint' => 50],
            'notes'         => ['type' => 'TEXT', 'null' => true],
            'created_by'    => ['type' => 'INTEGER', 'constraint' => 11, 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('repair_job_id');
        $this->forge->createTable('repair_status_logs', true);

        // 18. ตาราง tenant_settings (การตั้งค่าความยืดหยุ่น Onboarding Wizard ประจำร้าน)
        $this->forge->addField([
            'id'            => ['type' => 'INTEGER', 'constraint' => 11, 'auto_increment' => true],
            'tenant_id'     => ['type' => 'INTEGER', 'constraint' => 11],
            'setting_key'   => ['type' => 'VARCHAR', 'constraint' => 50],
            'setting_value' => ['type' => 'TEXT'],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['tenant_id', 'setting_key']);
        $this->forge->createTable('tenant_settings', true);

        // 19. ตาราง print_templates (รูปแบบการพิมพ์ A4, A5, Slip 80mm/58mm, Barcode)
        $this->forge->addField([
            'id'              => ['type' => 'INTEGER', 'constraint' => 11, 'auto_increment' => true],
            'tenant_id'       => ['type' => 'INTEGER', 'constraint' => 11],
            'doc_type'        => ['type' => 'VARCHAR', 'constraint' => 50], // repair_ticket, invoice, quotation, pos_receipt, barcode_label
            'paper_size'      => ['type' => 'VARCHAR', 'constraint' => 30], // a4_portrait, a5_landscape, slip_80mm, slip_58mm, label_barcode
            'header_title'    => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'header_logo_url' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'header_info'     => ['type' => 'TEXT', 'null' => true], // JSON
            'footer_terms'    => ['type' => 'TEXT', 'null' => true], // เงื่อนไขการรับประกัน
            'footer_notes'    => ['type' => 'TEXT', 'null' => true], // ข้อความขอบคุณ / ช่องเซ็นชื่อ
            'show_options'    => ['type' => 'TEXT', 'null' => true], // JSON แสดง/ซ่อนฟิลด์
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['tenant_id', 'doc_type']);
        $this->forge->createTable('print_templates', true);

        // 20. ตาราง price_tiers (กลุ่มระดับราคา: ทั่วไป, ช่างซ่อม, ราคาส่ง, VIP)
        $this->forge->addField([
            'id'           => ['type' => 'INTEGER', 'constraint' => 11, 'auto_increment' => true],
            'tenant_id'    => ['type' => 'INTEGER', 'constraint' => 11],
            'code'         => ['type' => 'VARCHAR', 'constraint' => 50], // retail, technician, wholesale, vip
            'name'         => ['type' => 'VARCHAR', 'constraint' => 100], // e.g. ราคาปลีก, ราคาช่างซ่อม, ราคาส่ง
            'calc_type'    => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'fixed'], // fixed, discount_percent, discount_amount, cost_plus_percent, cost_plus_amount
            'default_rate' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0.00], // ตัวเลข % หรือจำนวนเงินบาท
            'is_default'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0], // 1 = ราคาขายปลีกเริ่มต้น
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['tenant_id', 'code']);
        $this->forge->createTable('price_tiers', true);

        // 21. ตาราง product_tier_prices (การ Override ราคาพิเศษเฉพาะสินค้ารายชิ้น)
        $this->forge->addField([
            'id'         => ['type' => 'INTEGER', 'constraint' => 11, 'auto_increment' => true],
            'tenant_id'  => ['type' => 'INTEGER', 'constraint' => 11],
            'product_id' => ['type' => 'INTEGER', 'constraint' => 11],
            'tier_id'    => ['type' => 'INTEGER', 'constraint' => 11],
            'calc_type'  => ['type' => 'VARCHAR', 'constraint' => 30], // fixed, discount_percent, discount_amount, cost_plus_percent, cost_plus_amount
            'value'      => ['type' => 'DECIMAL', 'constraint' => '12,2'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['product_id', 'tier_id']);
        $this->forge->createTable('product_tier_prices', true);

        // 22. ตาราง repair_deposits (ประวัติใบรับเงินมัดจำงานซ่อม)
        $this->forge->addField([
            'id'             => ['type' => 'INTEGER', 'constraint' => 11, 'auto_increment' => true],
            'tenant_id'      => ['type' => 'INTEGER', 'constraint' => 11],
            'repair_job_id'  => ['type' => 'INTEGER', 'constraint' => 11],
            'deposit_no'     => ['type' => 'VARCHAR', 'constraint' => 50], // เช่น DP2609-0001
            'amount'         => ['type' => 'DECIMAL', 'constraint' => '12,2'],
            'payment_method' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'cash'],
            'receipt_no'     => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'notes'          => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'received_by'    => ['type' => 'INTEGER', 'constraint' => 11, 'null' => true],
            'received_at'    => ['type' => 'DATETIME'],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['tenant_id', 'deposit_no']);
        $this->forge->addKey('repair_job_id');
        $this->forge->createTable('repair_deposits', true);

        // 23. ตาราง product_cost_logs (ประวัติการปรับปรุงต้นทุนสินค้าจากการรับเข้าตามสูตรคำนวณ)
        $this->forge->addField([
            'id'             => ['type' => 'INTEGER', 'constraint' => 11, 'auto_increment' => true],
            'tenant_id'      => ['type' => 'INTEGER', 'constraint' => 11],
            'product_id'     => ['type' => 'INTEGER', 'constraint' => 11],
            'old_cost'       => ['type' => 'DECIMAL', 'constraint' => '12,2'],
            'new_cost'       => ['type' => 'DECIMAL', 'constraint' => '12,2'],
            'received_qty'   => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'received_cost'  => ['type' => 'DECIMAL', 'constraint' => '12,2'],
            'costing_method' => ['type' => 'VARCHAR', 'constraint' => 30], // moving_average, latest_cost, highest_cost, manual
            'ref_type'       => ['type' => 'VARCHAR', 'constraint' => 50], // direct_in, po_receive, manual_adjust
            'ref_id'         => ['type' => 'INTEGER', 'constraint' => 11, 'null' => true],
            'notes'          => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_by'     => ['type' => 'INTEGER', 'constraint' => 11, 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['tenant_id', 'product_id']);
        $this->forge->createTable('product_cost_logs', true);

        // 24. ตาราง pos_orders (บิลขายสินค้าหน้าร้าน)
        $this->forge->addField([
            'id'              => ['type' => 'INTEGER', 'constraint' => 11, 'auto_increment' => true],
            'tenant_id'       => ['type' => 'INTEGER', 'constraint' => 11],
            'branch_id'       => ['type' => 'INTEGER', 'constraint' => 11],
            'order_no'        => ['type' => 'VARCHAR', 'constraint' => 50], // เช่น POS2609-0001
            'customer_id'     => ['type' => 'INTEGER', 'constraint' => 11, 'null' => true],
            'cash_shift_id'   => ['type' => 'INTEGER', 'constraint' => 11, 'null' => true],
            'total_amount'    => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0.00],
            'discount_amount' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0.00],
            'net_amount'      => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0.00],
            'payment_method'  => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'cash'], // cash, bank_transfer, store_credit, debt
            'payment_status'  => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'paid'], // paid, partial, unpaid
            'notes'           => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_by'      => ['type' => 'INTEGER', 'constraint' => 11, 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['tenant_id', 'order_no']);
        $this->forge->addKey(['tenant_id', 'branch_id']);
        $this->forge->createTable('pos_orders', true);

        // 25. ตาราง pos_order_items (รายการสินค้าในบิลขายหน้าร้าน)
        $this->forge->addField([
            'id'           => ['type' => 'INTEGER', 'constraint' => 11, 'auto_increment' => true],
            'pos_order_id' => ['type' => 'INTEGER', 'constraint' => 11],
            'product_id'   => ['type' => 'INTEGER', 'constraint' => 11],
            'serial_id'    => ['type' => 'INTEGER', 'constraint' => 11, 'null' => true],
            'item_name'    => ['type' => 'VARCHAR', 'constraint' => 150],
            'qty'          => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 1.00],
            'cost_price'   => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0.00],
            'unit_price'   => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0.00],
            'total_price'  => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0.00],
            'track_stock'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1], // 1 = ตัดสต็อก, 0 = บริการไม่ตัดสต็อก
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('pos_order_id');
        $this->forge->addKey('product_id');
        $this->forge->createTable('pos_order_items', true);

        // 26. ตาราง stock_transfers (ใบโอนย้ายสินค้าระหว่างสาขา/ระหว่างคลัง)
        $this->forge->addField([
            'id'                => ['type' => 'INTEGER', 'constraint' => 11, 'auto_increment' => true],
            'tenant_id'         => ['type' => 'INTEGER', 'constraint' => 11],
            'transfer_no'       => ['type' => 'VARCHAR', 'constraint' => 50], // เช่น TR2609-0001
            'from_branch_id'    => ['type' => 'INTEGER', 'constraint' => 11],
            'from_warehouse_id' => ['type' => 'INTEGER', 'constraint' => 11],
            'to_branch_id'      => ['type' => 'INTEGER', 'constraint' => 11],
            'to_warehouse_id'   => ['type' => 'INTEGER', 'constraint' => 11],
            'status'            => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'requested'], // requested, in_transit, completed, cancelled
            'notes'             => ['type' => 'TEXT', 'null' => true],
            'dispatched_by'     => ['type' => 'INTEGER', 'constraint' => 11, 'null' => true],
            'dispatched_at'     => ['type' => 'DATETIME', 'null' => true],
            'received_by'       => ['type' => 'INTEGER', 'constraint' => 11, 'null' => true],
            'received_at'       => ['type' => 'DATETIME', 'null' => true],
            'created_by'        => ['type' => 'INTEGER', 'constraint' => 11, 'null' => true],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
            'updated_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['tenant_id', 'transfer_no']);
        $this->forge->createTable('stock_transfers', true);

        // 27. ตาราง stock_transfer_items (รายการสินค้าที่โอนย้าย)
        $this->forge->addField([
            'id'                => ['type' => 'INTEGER', 'constraint' => 11, 'auto_increment' => true],
            'stock_transfer_id' => ['type' => 'INTEGER', 'constraint' => 11],
            'product_id'        => ['type' => 'INTEGER', 'constraint' => 11],
            'serial_id'         => ['type' => 'INTEGER', 'constraint' => 11, 'null' => true],
            'qty'               => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 1.00],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('stock_transfer_id');
        $this->forge->addKey('product_id');
        $this->forge->createTable('stock_transfer_items', true);

        // 28. ตาราง stock_adjustments (หัวเอกสารตรวจนับและปรับปรุงสต็อก)
        $this->forge->addField([
            'id'               => ['type' => 'INTEGER', 'constraint' => 11, 'auto_increment' => true],
            'tenant_id'        => ['type' => 'INTEGER', 'constraint' => 11],
            'branch_id'        => ['type' => 'INTEGER', 'constraint' => 11],
            'warehouse_id'     => ['type' => 'INTEGER', 'constraint' => 11],
            'adjustment_no'    => ['type' => 'VARCHAR', 'constraint' => 50], // ADJ2609-xxxx
            'type'             => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'cycle_count'], // cycle_count, damage, loss, found
            'status'           => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'draft'], // draft, approved, cancelled
            'total_loss_value' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0.00],
            'total_gain_value' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0.00],
            'notes'            => ['type' => 'TEXT', 'null' => true],
            'created_by'       => ['type' => 'INTEGER', 'constraint' => 11, 'null' => true],
            'approved_by'      => ['type' => 'INTEGER', 'constraint' => 11, 'null' => true],
            'approved_at'      => ['type' => 'DATETIME', 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['tenant_id', 'adjustment_no']);
        $this->forge->createTable('stock_adjustments', true);

        // 29. ตาราง stock_adjustment_items (รายการปรับปรุงสต็อกรายชิ้น)
        $this->forge->addField([
            'id'                  => ['type' => 'INTEGER', 'constraint' => 11, 'auto_increment' => true],
            'stock_adjustment_id' => ['type' => 'INTEGER', 'constraint' => 11],
            'product_id'          => ['type' => 'INTEGER', 'constraint' => 11],
            'serial_id'           => ['type' => 'INTEGER', 'constraint' => 11, 'null' => true],
            'system_qty'          => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0.00],
            'counted_qty'         => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0.00],
            'diff_qty'            => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0.00],
            'cost_price'          => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0.00],
            'diff_amount'         => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0.00],
            'reason'              => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at'          => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('stock_adjustment_id');
        $this->forge->addKey('product_id');
        $this->forge->createTable('stock_adjustment_items', true);
    }

    public function down()
    {
        $this->forge->dropTable('stock_adjustment_items', true);
        $this->forge->dropTable('stock_adjustments', true);
        $this->forge->dropTable('stock_transfer_items', true);
        $this->forge->dropTable('stock_transfers', true);
        $this->forge->dropTable('pos_order_items', true);
        $this->forge->dropTable('pos_orders', true);
        $this->forge->dropTable('product_cost_logs', true);
        $this->forge->dropTable('repair_deposits', true);
        $this->forge->dropTable('product_tier_prices', true);
        $this->forge->dropTable('price_tiers', true);
        $this->forge->dropTable('print_templates', true);
        $this->forge->dropTable('tenant_settings', true);
        $this->forge->dropTable('repair_status_logs', true);
        $this->forge->dropTable('repair_items', true);
        $this->forge->dropTable('repair_jobs', true);
        $this->forge->dropTable('cash_shifts', true);
        $this->forge->dropTable('financial_transactions', true);
        $this->forge->dropTable('wallet_transactions', true);
        $this->forge->dropTable('wallets', true);
        $this->forge->dropTable('goods_receipt_items', true);
        $this->forge->dropTable('goods_receipts', true);
        $this->forge->dropTable('purchase_order_items', true);
        $this->forge->dropTable('purchase_orders', true);
        $this->forge->dropTable('stock_transactions', true);
        $this->forge->dropTable('product_serials', true);
        $this->forge->dropTable('products', true);
        $this->forge->dropTable('categories', true);
        $this->forge->dropTable('warehouses', true);
        $this->forge->dropTable('branches', true);
    }
}
