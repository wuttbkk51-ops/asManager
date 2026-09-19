<?php

namespace App\Models;

use CodeIgniter\Model;

class PrintTemplateModel extends Model
{
    protected $table            = 'print_templates';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'tenant_id',
        'doc_type', // repair_ticket, invoice, quotation, pos_receipt, barcode_label
        'paper_size', // a4_portrait, a5_landscape, slip_80mm, slip_58mm, label_barcode
        'header_title',
        'header_logo_url',
        'header_info', // JSON
        'footer_terms',
        'footer_notes',
        'show_options', // JSON
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * ดึงแม่แบบพิมพ์สำหรับเอกสารที่ระบุของร้าน
     */
    public function getTemplate(int $tenantId, string $docType): ?array
    {
        $tpl = $this->where('tenant_id', $tenantId)
                    ->where('doc_type', $docType)
                    ->first();

        if ($tpl) {
            $tpl['header_info']  = json_decode($tpl['header_info'] ?? '[]', true) ?: [];
            $tpl['show_options'] = json_decode($tpl['show_options'] ?? '[]', true) ?: [];
        }

        return $tpl;
    }
}
