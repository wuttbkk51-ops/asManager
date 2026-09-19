<?php

namespace App\Services;

use App\Models\PrintTemplateModel;

class PrintService
{
    /**
     * เรนเดอร์เอกสารสำหรับพิมพ์ พร้อม CSS ป้องกันตารางขาดครึ่งหน้า (Zero-Break) และ Sticky Footer ชิดล่างกระดาษ
     */
    public function renderDocument(int $tenantId, string $docType, array $docData): string
    {
        $templateModel = new PrintTemplateModel();
        $template = $templateModel->getTemplate($tenantId, $docType) ?: [
            'paper_size'   => 'a4_portrait',
            'header_title' => 'เอกสาร (Document)',
            'header_info'  => ['shop_name' => 'Demo Shop'],
            'footer_terms' => '',
            'footer_notes' => 'ขอบคุณที่ใช้บริการ',
            'show_options' => [],
        ];

        $paperSize = $template['paper_size'] ?? 'a4_portrait';
        $css = $this->getPrintCss($paperSize);

        $html = '<!DOCTYPE html>';
        $html .= '<html lang="th">';
        $html .= '<head>';
        $html .= '<meta charset="UTF-8">';
        $html .= '<title>' . htmlspecialchars($template['header_title'] ?? 'Document') . '</title>';
        $html .= '<style>' . $css . '</style>';
        $html .= '</head>';
        $html .= '<body>';
        $html .= '<div class="print-container">';

        // ส่วนหัวเอกสาร (Header)
        $html .= '<div class="print-header">';
        $html .= '<div class="shop-title">' . htmlspecialchars($template['header_info']['shop_name'] ?? 'ร้านค้า') . '</div>';
        if (!empty($template['header_info']['tax_id'])) {
            $html .= '<div class="shop-sub">เลขประจำตัวผู้เสียภาษี: ' . htmlspecialchars($template['header_info']['tax_id']) . '</div>';
        }
        if (!empty($template['header_info']['phone'])) {
            $html .= '<div class="shop-sub">โทร: ' . htmlspecialchars($template['header_info']['phone']) . '</div>';
        }
        if (!empty($template['header_info']['address'])) {
            $html .= '<div class="shop-sub">' . htmlspecialchars($template['header_info']['address']) . '</div>';
        }
        $html .= '<div class="doc-title">' . htmlspecialchars($template['header_title'] ?? '') . '</div>';
        $html .= '</div>';

        // ข้อมูลอ้างอิงเอกสาร (Doc Meta)
        $html .= '<div class="doc-meta">';
        if (!empty($docData['doc_no'])) {
            $html .= '<div><strong>เลขที่:</strong> ' . htmlspecialchars($docData['doc_no']) . '</div>';
        }
        if (!empty($docData['date'])) {
            $html .= '<div><strong>วันที่:</strong> ' . htmlspecialchars($docData['date']) . '</div>';
        }
        if (!empty($docData['customer_name'])) {
            $html .= '<div><strong>ลูกค้า:</strong> ' . htmlspecialchars($docData['customer_name']) . ' (' . htmlspecialchars($docData['customer_phone'] ?? '') . ')</div>';
        }
        if (!empty($docData['device_info'])) {
            $html .= '<div><strong>เครื่อง:</strong> ' . htmlspecialchars($docData['device_info']) . '</div>';
        }
        $html .= '</div>';

        // เนื้อหาหลัก / ตารางรายการ (Table Content)
        $html .= '<div class="print-content">';
        if (!empty($docData['items'])) {
            $html .= '<table class="items-table">';
            $html .= '<thead>';
            $html .= '<tr>';
            $html .= '<th style="width: 8%;">#</th>';
            $html .= '<th style="text-align: left;">รายการ</th>';
            $html .= '<th style="width: 15%; text-align: right;">จำนวน</th>';
            $html .= '<th style="width: 20%; text-align: right;">ราคา/หน่วย</th>';
            $html .= '<th style="width: 20%; text-align: right;">รวมเงิน</th>';
            $html .= '</tr>';
            $html .= '</thead>';
            $html .= '<tbody>';
            $idx = 1;
            foreach ($docData['items'] as $item) {
                $html .= '<tr>';
                $html .= '<td style="text-align: center;">' . $idx++ . '</td>';
                $html .= '<td>' . htmlspecialchars($item['name']) . '</td>';
                $html .= '<td style="text-align: right;">' . number_format($item['qty'], 2) . '</td>';
                $html .= '<td style="text-align: right;">' . number_format($item['unit_price'], 2) . '</td>';
                $html .= '<td style="text-align: right;">' . number_format($item['total_price'], 2) . '</td>';
                $html .= '</tr>';
            }
            $html .= '</tbody>';
            $html .= '</table>';
        }

        // ยอดสรุปการเงิน
        $html .= '<div class="totals-section">';
        if (isset($docData['total_amount'])) {
            $html .= '<div class="total-row"><span>ยอดรวม:</span> <span>' . number_format($docData['total_amount'], 2) . ' บาท</span></div>';
        }
        if (isset($docData['deposit_amount']) && $docData['deposit_amount'] > 0) {
            $html .= '<div class="total-row"><span>หัก เงินมัดจำ:</span> <span>-' . number_format($docData['deposit_amount'], 2) . ' บาท</span></div>';
        }
        if (isset($docData['net_total'])) {
            $html .= '<div class="total-row grand-total"><span>ยอดชำระสุทธิ:</span> <span>' . number_format($docData['net_total'], 2) . ' บาท</span></div>';
        }
        $html .= '</div>';

        // QR Code สำหรับติดตามงาน (ถ้ามี)
        if (!empty($docData['tracking_token'])) {
            $html .= '<div class="qr-section">';
            $html .= '<div class="qr-box">[ QR TRACKING CODE: ' . htmlspecialchars($docData['tracking_token']) . ' ]</div>';
            $html .= '<div class="qr-hint">สแกนเพื่อตรวจสอบสถานะงานซ่อมแบบเรียลไทม์</div>';
            $html .= '</div>';
        }
        $html .= '</div>'; // end print-content

        // ส่วนท้ายเอกสาร (Footer ชิดล่างกระดาษเสมอ)
        $html .= '<div class="print-footer">';
        if (!empty($template['footer_terms'])) {
            $html .= '<div class="terms-box">';
            $html .= '<strong>เงื่อนไขและข้อกำหนด:</strong><br>';
            $html .= nl2br(htmlspecialchars($template['footer_terms']));
            $html .= '</div>';
        }
        if (!empty($template['footer_notes'])) {
            $html .= '<div class="notes-text">' . htmlspecialchars($template['footer_notes']) . '</div>';
        }
        $html .= '<div class="signature-section">';
        $html .= '<div class="sig-block">ลงชื่อ ..........................................<br>( ลูกค้า / ผู้ส่งเครื่อง )</div>';
        $html .= '<div class="sig-block">ลงชื่อ ..........................................<br>( พนักงาน / ผู้รับเครื่อง )</div>';
        $html .= '</div>';
        $html .= '</div>'; // end print-footer

        $html .= '</div>'; // end print-container
        $html .= '</body></html>';

        return $html;
    }

    /**
     * สร้างชุดคำสั่ง CSS Print รองรับ A4, A5, Slip 80mm/58mm พร้อมเทคนิค Zero-Break และ Flex Sticky Footer
     */
    public function getPrintCss(string $paperSize): string
    {
        $pageSizeRule = match ($paperSize) {
            'a5_landscape' => '@page { size: A5 landscape; margin: 8mm; }',
            'slip_80mm'    => '@page { size: 80mm auto; margin: 3mm; }',
            'slip_58mm'    => '@page { size: 58mm auto; margin: 2mm; }',
            'label_barcode'=> '@page { size: 50mm 30mm; margin: 1mm; }',
            default        => '@page { size: A4 portrait; margin: 10mm; }',
        };

        $fontSize = match ($paperSize) {
            'slip_80mm', 'slip_58mm' => '11px',
            'label_barcode'          => '9px',
            default                  => '13px',
        };

        return "
            {$pageSizeRule}
            * { box-sizing: border-box; }
            body {
                font-family: 'Sarabun', Tahoma, sans-serif;
                font-size: {$fontSize};
                line-height: 1.4;
                margin: 0;
                padding: 0;
                color: #111;
            }
            .print-container {
                display: flex;
                flex-direction: column;
                min-height: 98vh; /* ยึดเต็มหน้ากระดาษเพื่อให้ Footer ชิดล่างเสมอ */
            }
            .print-content {
                flex: 1; /* ผลักส่วนท้ายลงล่างสุด */
            }
            .print-header {
                text-align: center;
                border-bottom: 2px solid #333;
                padding-bottom: 6px;
                margin-bottom: 10px;
            }
            .shop-title { font-size: 1.4em; font-weight: bold; }
            .shop-sub { font-size: 0.9em; color: #555; }
            .doc-title { font-size: 1.2em; font-weight: bold; margin-top: 5px; color: #0d47a1; }
            .doc-meta {
                display: flex;
                flex-wrap: wrap;
                justify-content: space-between;
                margin-bottom: 10px;
                font-size: 0.95em;
                background: #fdfdfd;
                padding: 6px;
                border: 1px solid #eee;
            }
            .items-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 12px;
            }
            .items-table th, .items-table td {
                border: 1px solid #ccc;
                padding: 6px 8px;
            }
            .items-table th {
                background: #f0f4f8;
                font-weight: bold;
            }
            /* กฎสำคัญ: ป้องกันตารางและแถวขาดครึ่งหน้า */
            tr, td, th {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            thead { display: table-header-group; }
            tfoot { display: table-footer-group; }
            
            .totals-section {
                width: 50%;
                margin-left: auto;
                margin-bottom: 12px;
            }
            .total-row {
                display: flex;
                justify-content: space-between;
                padding: 3px 0;
            }
            .grand-total {
                font-weight: bold;
                font-size: 1.15em;
                border-top: 2px solid #222;
                padding-top: 4px;
            }
            .qr-section {
                text-align: center;
                margin: 10px 0;
                padding: 6px;
                border: 1px dashed #666;
            }
            .qr-box { font-family: monospace; font-weight: bold; font-size: 1.05em; color: #0277bd; }
            .qr-hint { font-size: 0.85em; color: #666; }
            .print-footer {
                margin-top: auto; /* ชิดล่างกระดาษเสมอ */
                border-top: 1px solid #ccc;
                padding-top: 8px;
            }
            .terms-box {
                font-size: 0.82em;
                color: #555;
                margin-bottom: 8px;
            }
            .notes-text {
                font-size: 0.85em;
                text-align: center;
                font-style: italic;
                margin-bottom: 10px;
            }
            .signature-section {
                display: flex;
                justify-content: space-between;
                padding-top: 20px;
                text-align: center;
                font-size: 0.9em;
            }
            .sig-block { width: 45%; }
        ";
    }
}
