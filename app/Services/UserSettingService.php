<?php

namespace App\Services;

use App\Models\UserSettingModel;

class UserSettingService
{
    protected UserSettingModel $settingModel;

    public function __construct()
    {
        $this->settingModel = new UserSettingModel();
    }

    /**
     * ดึงการตั้งค่าทั้งหมดของผู้ใช้พร้อม Default Values
     */
    public function getUserSettings(int $userId): array
    {
        $saved = $this->settingModel->getAllSettings($userId);

        return [
            'theme'          => $saved['theme'] ?? 'light',          // light, dark, auto
            'language'       => $saved['language'] ?? 'th',          // th, en
            'sound_enabled'  => $saved['sound_enabled'] ?? '1',      // 1 = เปิดเสียงแจ้งเตือน/สแกนบาร์โค้ด
            'notify_repair'  => $saved['notify_repair'] ?? '1',      // แจ้งเตือนเมื่อสถานะงานซ่อมเปลี่ยน
            'notify_stock'   => $saved['notify_stock'] ?? '1',       // แจ้งเตือนเมื่ออะไหล่สต็อกต่ำ
            'items_per_page' => $saved['items_per_page'] ?? '25',    // จำนวนรายการต่อหน้า
        ];
    }

    /**
     * บันทึกการตั้งค่าหลายค่าของผู้ใช้
     */
    public function saveUserSettings(int $userId, array $data): bool
    {
        foreach ($data as $key => $val) {
            $this->settingModel->setSetting($userId, $key, (string)$val);
        }
        return true;
    }

    /**
     * ดึงค่าเฉพาะ key
     */
    public function getSetting(int $userId, string $key, $default = null)
    {
        return $this->settingModel->getSetting($userId, $key, $default);
    }
}
