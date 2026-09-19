<?php

return [
    'settings_title'             => 'System Settings & Shop Policies',
    'onboarding_wizard'          => 'Onboarding Wizard',
    
    // Tabs
    'tab_general'                => 'General & Shop Info',
    'tab_repair'                 => 'Repairs',
    'tab_sales'                  => 'Sales & POS',
    'tab_stock'                  => 'Stock & Inventory',

    // Tab 1: General
    'shop_name'                  => 'Shop / Legal Entity Name',
    'shop_slug'                  => 'Shop Code (Slug)',
    'shop_slug_desc'             => 'Multi-tenant shop identifier code (read-only)',
    'shop_phone'                 => 'Phone Number',
    'shop_tax_id'                => 'Tax ID Number',
    'shop_address'               => 'Shop Address / Head Office',
    'shop_address_desc'          => 'Appears on receipts, tax invoices, and repair tickets',
    'header_note'                => 'Document Header Note',
    'header_note_desc'           => 'e.g. Complete mobile, computer repair and IT supplies',
    'footer_note'                => 'Document Footer Note',
    'footer_note_desc'           => 'Thank-you note or policy disclaimer printed at bottom of receipts',
    'default_language'           => 'Default Shop Language',
    'default_language_desc'      => 'Default language for printed receipts, invoices, and new users',

    // Tab 2: Repair
    'parts_approval'             => 'Require Parts Approval Before Dispense',
    'parts_approval_desc'        => 'Requires customer or manager quotation approval before parts can be dispensed from inventory',
    'repair_require_serial'      => 'Require Serial/IMEI for Repair Parts',
    'repair_require_serial_desc' => 'For parts tracked with Serial/IMEI, scanning or assigning specific unit is required for warranty',
    'repair_notify_customer'     => 'Repair Status Customer Notifications',
    'repair_notify_customer_desc'=> 'Send automated alerts when device is received, quotation approved, or repair finished',
    'labor_fee'                  => 'Default Inspection / Labor Fee (THB)',
    'warranty_days'              => 'Standard Repair Warranty Period',
    'warranty_terms'             => 'Repair Warranty Terms & Conditions',
    'warranty_terms_desc'        => 'This statement appears on repair intake slips and delivery warranty receipts',

    // Tab 3: Sales
    'require_cash_shift'         => 'Require Cashier Shift Open/Close',
    'require_cash_shift_desc'    => 'Cashiers must count opening drawer float before sales and balance register at shift end',
    'enable_wallet_credit'       => 'Customer Deposit & Credit Receivable',
    'enable_wallet_credit_desc'  => 'Allows applying repair advance deposits and billing sales on customer credit account',
    'allow_manual_discount'      => 'Allow Cashier Manual Bill Discounts',
    'allow_manual_discount_desc' => 'If unchecked, cashier must obtain manager override to apply bill discounts',
    'paper_size'                 => 'Default Slip Paper Size',
    'paper_slip_80mm'            => 'Thermal Slip 80mm (Standard)',
    'paper_slip_58mm'            => 'Thermal Slip 58mm',
    'paper_a5'                   => 'A5 Paper (Landscape)',
    'paper_a4'                   => 'A4 Paper (Full Page)',
    'vat_mode'                   => 'POS Value Added Tax (VAT) Mode',
    'vat_none'                   => 'No VAT (Disabled)',
    'vat_include'                => 'Prices Include VAT (Included 7%)',
    'vat_exclude'                => 'Add VAT on Checkout (Excluded +7%)',

    // Tab 4: Stock
    'procurement_workflow'       => 'Procurement & Inbound Workflow',
    'wf_simple'                  => 'Simple Mode / Direct Inbound',
    'wf_simple_desc'             => 'Receive inventory directly without purchase orders (PO). Fast and agile for repair shops.',
    'wf_standard'                => 'Standard Corporate Mode / PO Required',
    'wf_standard_desc'           => 'Requires formal Purchase Orders (PO) and Goods Receipt Notes (GRN) for warehouse control.',
    'costing_method'             => 'Inventory Costing Method',
    'costing_method_desc'        => 'Used to compute inventory valuation and gross margin across sales and service tickets',
    'costing_ma'                 => 'Moving Average (Standard)',
    'costing_latest'             => 'Latest Cost',
    'costing_highest'            => 'Highest Cost',
    'costing_manual'             => 'Manual Fixed Cost',
    'landed_cost_method'         => 'Freight & Landed Cost Allocation',
    'landed_by_value'            => 'Allocate by Value',
    'landed_by_qty'              => 'Allocate by Quantity',
    'enable_landed_cost'         => 'Enable freight cost capitalization into inventory unit cost',
    'serial_tracking'            => 'Warehouse Serial / IMEI Tracking',
    'serial_tracking_desc'       => 'Track and control individual serial and IMEI units in stock',
    'stock_low_threshold'        => 'Low Stock Warning Threshold',
    'stock_low_threshold_desc'   => 'Displays alert on dashboard when quantity drops below this threshold',
    'unit_pcs'                   => 'pcs',

    // Flash Messages
    'saved_general_success'      => 'General and shop profile settings saved successfully',
    'saved_repair_success'       => 'Repair workflow settings saved successfully',
    'saved_sales_success'        => 'Sales and POS settings saved successfully',
    'saved_stock_success'        => 'Inventory and procurement settings saved successfully',
];
