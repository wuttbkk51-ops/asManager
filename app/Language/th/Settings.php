<?php

return [
    'settings_title'             => 'ตั้งค่าระบบและนโยบายร้านค้า',
    'onboarding_wizard'          => 'Onboarding Wizard',
    
    // Tabs
    'tab_general'                => 'ทั่วไป & ข้อมูลร้าน',
    'tab_repair'                 => 'งานซ่อม',
    'tab_sales'                  => 'งานขาย & POS',
    'tab_stock'                  => 'คลังสินค้า & จัดซื้อ',

    // Tab 1: General
    'shop_name'                  => 'ชื่อร้านค้า / นิติบุคคล',
    'shop_slug'                  => 'รหัสร้านค้า (Slug)',
    'shop_slug_desc'             => 'รหัสอ้างอิงร้านค้าในระบบ Multi-tenant (ไม่สามารถแก้ไขได้)',
    'shop_phone'                 => 'เบอร์โทรศัพท์',
    'shop_tax_id'                => 'เลขประจำตัวผู้เสียภาษี (Tax ID)',
    'shop_address'               => 'ที่อยู่ร้านค้า / สำนักงานใหญ่',
    'shop_address_desc'          => 'แสดงบนหัวใบเสร็จรับเงิน ใบกำกับภาษี และใบรับซ่อม',
    'header_note'                => 'ข้อความหัวเอกสาร',
    'header_note_desc'           => 'เช่น บริการซ่อมมือถือ คอมพิวเตอร์ และอุปกรณ์ไอทีครบวงจร',
    'footer_note'                => 'ข้อความท้ายเอกสารทั่วไป',
    'footer_note_desc'           => 'ข้อความคำขอบคุณหรือคำชี้แจงทั่วไปที่จะแสดงท้ายใบเสร็จ',
    'default_language'           => 'ภาษาเริ่มต้นของร้านค้า (Default Language)',
    'default_language_desc'      => 'ภาษาหลักสำหรับพิมพ์เอกสาร ใบเสร็จ และหน้าจอเริ่มต้น',

    // Tab 2: Repair
    'parts_approval'             => 'บังคับอนุมัติราคาอะไหล่ก่อนเบิกซ่อม',
    'parts_approval_desc'        => 'ต้องให้ลูกค้าหรือผู้มีอำนาจอนุมัติราคาประเมินก่อนเบิกอะไหล่ออกจากคลัง',
    'repair_require_serial'      => 'บังคับสแกน Serial Number อะไหล่ในงานซ่อม',
    'repair_require_serial_desc' => 'สำหรับอะไหล่ที่มี Serial/IMEI บังคับเลือกหรือสแกนชิ้นที่ใส่ในเครื่องเพื่อคุมรับประกัน',
    'repair_notify_customer'     => 'ระบบแจ้งเตือนสถานะงานซ่อมแก่ลูกค้า',
    'repair_notify_customer_desc'=> 'แจ้งเตือนสถานะอัตโนมัติเมื่อรับเครื่องเข้าซ่อม, อนุมัติราคา, หรือซ่อมเสร็จพร้อมส่งมอบ',
    'labor_fee'                  => 'ค่าบริการตรวจเช็คเริ่มต้น (บาท)',
    'warranty_days'              => 'ระยะเวลารับประกันงานซ่อมมาตรฐาน',
    'warranty_terms'             => 'เงื่อนไขการรับประกันงานซ่อม',
    'warranty_terms_desc'        => 'ข้อความนี้จะแสดงในใบรับเครื่องและใบเสร็จส่งมอบงานซ่อม',

    // Tab 3: Sales
    'require_cash_shift'         => 'บังคับเปิด-ปิดกะเงินสดแคชเชียร์',
    'require_cash_shift_desc'    => 'แคชเชียร์ต้องนับเงินทอนเปิดกะก่อนทำการขาย และปิดกะสรุปยอดเงินสดทุกสิ้นวัน',
    'enable_wallet_credit'       => 'ระบบเงินมัดจำล่วงหน้าและเครดิตลูกหนี้',
    'enable_wallet_credit_desc'  => 'อนุญาตให้นำเงินมัดจำใบซ่อมมาหักชำระ หรือบันทึกยอดค้างชำระ (ลูกหนี้การค้า) ได้',
    'allow_manual_discount'      => 'อนุญาตให้พนักงานขายใส่ส่วนลดท้ายบิลได้เอง',
    'allow_manual_discount_desc' => 'หากปิดตัวเลือกนี้ พนักงานต้องได้รับอนุมัติจากผู้จัดการก่อนใส่ส่วนลด',
    'paper_size'                 => 'ขนาดกระดาษพิมพ์สลิปเริ่มต้น',
    'paper_slip_80mm'            => 'สลิปความร้อน 80 มม. (มาตรฐาน)',
    'paper_slip_58mm'            => 'สลิปความร้อน 58 มม.',
    'paper_a5'                   => 'กระดาษ A5 แนวนอน',
    'paper_a4'                   => 'กระดาษ A4',
    'vat_mode'                   => 'รูปแบบภาษีมูลค่าเพิ่มหน้าร้าน (VAT)',
    'vat_none'                   => 'ไม่คิดภาษี (No VAT)',
    'vat_include'                => 'ราคารวมภาษีมูลค่าเพิ่มแล้ว (Included 7%)',
    'vat_exclude'                => 'คำนวณบวกภาษีเพิ่มต่างหาก (Excluded +7%)',

    // Tab 4: Stock
    'procurement_workflow'       => 'กระบวนการจัดซื้อรับเข้า (Procurement Workflow)',
    'wf_simple'                  => 'โหมดง่าย / Direct Inbound',
    'wf_simple_desc'             => 'รับสินค้าเข้าคลังได้ทันทีโดยไม่ต้องสร้างใบสั่งซื้อ (PO) เหมาะสำหรับร้านทั่วไป คล่องตัว รวดเร็ว',
    'wf_standard'                => 'โหมดมาตรฐาน / Corporate PO Required',
    'wf_standard_desc'           => 'ต้องเปิดใบสั่งซื้อ (PO) และตรวจรับด้วยใบรับสินค้า (GRN) เหมาะสำหรับองค์กรที่มีฝ่ายจัดซื้อ',
    'costing_method'             => 'สูตรคำนวณต้นทุนสินค้าหลัก',
    'costing_method_desc'        => 'ใช้สำหรับคำนวณกำไรขั้นต้น (Gross Margin) ของการขายและงานซ่อม',
    'costing_ma'                 => 'Moving Average (ถัวเฉลี่ยเคลื่อนที่ - มาตรฐาน)',
    'costing_latest'             => 'Latest Cost (ใช้ต้นทุนครั้งล่าสุด)',
    'costing_highest'            => 'Highest Cost (ใช้ต้นทุนสูงสุด)',
    'costing_manual'             => 'Manual Fixed (กำหนดต้นทุนมาตรฐานเอง)',
    'landed_cost_method'         => 'การปันส่วนค่าขนส่งเข้าต้นทุน (Landed Cost)',
    'landed_by_value'            => 'ปันส่วนตามมูลค่าสินค้า (By Value)',
    'landed_by_qty'              => 'ปันส่วนตามจำนวนชิ้น (By Quantity)',
    'enable_landed_cost'         => 'เปิดใช้การปันส่วนค่าส่งเข้าเป็นต้นทุนสินค้า',
    'serial_tracking'            => 'ระบบคุม Serial / IMEI ในคลัง',
    'serial_tracking_desc'       => 'เปิดใช้งานการติดตามหมายเลข Serial รายชิ้นในคลังสินค้า',
    'stock_low_threshold'        => 'เกณฑ์แจ้งเตือนสต็อกใกล้หมด',
    'stock_low_threshold_desc'   => 'เมื่อสินค้าคงเหลือต่ำกว่าเกณฑ์นี้ ระบบจะแสดงการแจ้งเตือนบนหน้า Dashboard',
    'unit_pcs'                   => 'ชิ้น',

    // Flash Messages
    'saved_general_success'      => 'บันทึกข้อมูลทั่วไปและข้อมูลร้านค้าเรียบร้อยแล้ว',
    'saved_repair_success'       => 'บันทึกการตั้งค่างานซ่อมเรียบร้อยแล้ว',
    'saved_sales_success'        => 'บันทึกการตั้งค่างานขายและจุดขาย (POS) เรียบร้อยแล้ว',
    'saved_stock_success'        => 'บันทึกการตั้งค่าคลังสินค้าและการจัดซื้อเรียบร้อยแล้ว',
];
