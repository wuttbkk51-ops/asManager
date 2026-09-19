<?php

namespace App\Services;

use App\Models\ProductModel;
use App\Models\PriceTierModel;
use App\Models\ProductTierPriceModel;

class PriceTierService
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    /**
     * คำนวณราคาสินค้า:
     * กฎ:
     * 1. Tier price จะถูกกำหนดที่ Customer (ลูกค้า)
     * 2. หาก Customer รายนั้นไม่ได้เลือก Tier (หรือเป็น Walk-in / ไม่มี price_tier_id) 
     *    ระบบจะดึงราคาขายหลัก (sell_price) จากตารางสินค้ามาขายเป็น Default 100% ทันที
     * 3. หาก Customer มีการผูก Tier ไว้ จะคำนวณตาม Tier นั้น (รองรับ 5 รูปแบบสูตร และการ Override รายสินค้า)
     */
    public function calculatePrice(int $productId, ?int $tierId = null, ?int $customerId = null): array
    {
        $productModel = new ProductModel();
        $product = $productModel->find($productId);
        if (!$product) {
            return [
                'final_price'         => 0.00,
                'tier_name'           => 'ไม่พบสินค้า',
                'calc_type'           => 'default_sell_price',
                'original_sell_price' => 0.00,
                'cost_price'          => 0.00,
                'formula_detail'      => 'Product not found',
            ];
        }

        $sellPrice = (float)$product['sell_price'];
        $costPrice = (float)$product['cost_price'];
        $tenantId  = (int)$product['tenant_id'];

        // 1. ระบุ Tier ที่จะใช้: ถ้าไม่ได้ส่ง tierId มาตรงๆ แต่ส่ง customerId มา ให้ดูจากข้อมูลลูกค้า
        $tierIdToUse = $tierId;
        if ($tierIdToUse === null && $customerId !== null) {
            $customer = $this->db->table('peoples')->where('id', $customerId)->get()->getRowArray();
            if ($customer && !empty($customer['price_tier_id'])) {
                $tierIdToUse = (int)$customer['price_tier_id'];
            }
        }

        // 2. หากลูกค้าไม่ได้เลือก Tier (หรือไม่มีการระบุ Tier): ดึงราคาขายหลัก sell_price มาใช้ขายทันที!
        if (empty($tierIdToUse)) {
            return [
                'product_id'          => $productId,
                'product_name'        => $product['name'],
                'tier_id'             => null,
                'tier_code'           => 'default',
                'tier_name'           => 'ราคาขายหลัก (Default)',
                'calc_type'           => 'default_sell_price',
                'rate'                => 0.00,
                'is_overridden'       => false,
                'original_sell_price' => $sellPrice,
                'cost_price'          => $costPrice,
                'final_price'         => $sellPrice,
                'formula_detail'      => 'ใช้ราคาขายหลักของสินค้า (ลูกค้าไม่ได้ระบุกลุ่มราคา Tier)',
            ];
        }

        // 3. ตรวจสอบข้อมูลกลุ่มราคา Tier ที่ระบุ
        $tierModel = new PriceTierModel();
        $tier = $tierModel->where('id', $tierIdToUse)->where('tenant_id', $tenantId)->first();
        if (!$tier) {
            // หากไม่พบ Tier ในระบบ ให้ Fallback ใช้ราคาขายหลักทันที
            return [
                'product_id'          => $productId,
                'product_name'        => $product['name'],
                'tier_id'             => null,
                'tier_code'           => 'default',
                'tier_name'           => 'ราคาขายหลัก (Default)',
                'calc_type'           => 'default_sell_price',
                'rate'                => 0.00,
                'is_overridden'       => false,
                'original_sell_price' => $sellPrice,
                'cost_price'          => $costPrice,
                'final_price'         => $sellPrice,
                'formula_detail'      => 'ใช้ราคาขายหลักของสินค้า (ไม่พบกลุ่มราคาที่ระบุ)',
            ];
        }

        // 4. ตรวจสอบว่าสินค้านี้มีการ Override ราคาพิเศษเฉพาะตัวใน Tier นี้หรือไม่
        $tierPriceModel = new ProductTierPriceModel();
        $override = $tierPriceModel->getOverride($productId, (int)$tier['id']);

        $calcType = $override ? $override['calc_type'] : $tier['calc_type'];
        $rate     = $override ? (float)$override['value'] : (float)$tier['default_rate'];
        $isOverridden = !empty($override);

        // 5. คำนวณราคาตามสูตรของ Tier
        $finalPrice = $sellPrice;
        $detail = '';

        switch ($calcType) {
            case 'fixed':
                $finalPrice = $rate > 0 ? $rate : $sellPrice;
                $detail = 'กำหนดราคาคงที่: ' . number_format($finalPrice, 2) . ' บาท';
                break;

            case 'discount_percent':
                $discountAmount = $sellPrice * ($rate / 100);
                $finalPrice = max(0.00, $sellPrice - $discountAmount);
                $detail = "ลด {$rate}% จากราคาขาย (" . number_format($sellPrice, 2) . ") ลดไป " . number_format($discountAmount, 2) . " บาท";
                break;

            case 'discount_amount':
                $finalPrice = max(0.00, $sellPrice - $rate);
                $detail = "ลด " . number_format($rate, 2) . " บาท จากราคาขาย (" . number_format($sellPrice, 2) . ")";
                break;

            case 'cost_plus_percent':
                $profitAmount = $costPrice * ($rate / 100);
                $finalPrice = $costPrice + $profitAmount;
                $detail = "บวกกำไร {$rate}% จากต้นทุน (" . number_format($costPrice, 2) . ") บวกเพิ่ม " . number_format($profitAmount, 2) . " บาท";
                break;

            case 'cost_plus_amount':
                $finalPrice = $costPrice + $rate;
                $detail = "บวกกำไร " . number_format($rate, 2) . " บาท จากต้นทุน (" . number_format($costPrice, 2) . ")";
                break;

            default:
                $finalPrice = $sellPrice;
                $detail = 'ราคาขายปกติ: ' . number_format($sellPrice, 2) . ' บาท';
                break;
        }

        return [
            'product_id'          => $productId,
            'product_name'        => $product['name'],
            'tier_id'             => $tier['id'],
            'tier_code'           => $tier['code'] ?? 'retail',
            'tier_name'           => $tier['name'],
            'calc_type'           => $calcType,
            'rate'                => $rate,
            'is_overridden'       => $isOverridden,
            'original_sell_price' => $sellPrice,
            'cost_price'          => $costPrice,
            'final_price'         => round($finalPrice, 2),
            'formula_detail'      => $detail,
        ];
    }
}
