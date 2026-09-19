<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class LocaleFilter implements FilterInterface
{
    /**
     * ดักจับทุก Request เพื่อตั้งค่า Locale ให้ระบบ CodeIgniter 4
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $supportedLocales = config('App')->supportedLocales ?? ['th', 'en'];
        $defaultLocale    = config('App')->defaultLocale ?? 'th';

        $locale = null;

        // 1. ตรวจสอบจาก Session ก่อนเป็นอันดับแรก
        if (session()->has('locale')) {
            $sessLocale = session()->get('locale');
            if (in_array($sessLocale, $supportedLocales, true)) {
                $locale = $sessLocale;
            }
        }

        // 2. หากยังไม่มีใน Session และผู้ใช้ล็อกอินอยู่ ให้ตรวจจาก User Settings
        if (!$locale && function_exists('auth_user') && auth_user('id')) {
            try {
                $userSettingService = new \App\Services\UserSettingService();
                $userLocale = $userSettingService->getSetting((int)auth_user('id'), 'language');
                if ($userLocale && in_array($userLocale, $supportedLocales, true)) {
                    $locale = $userLocale;
                }
            } catch (\Throwable $e) {
                // Ignore DB error during early boot/migration
            }
        }

        // 3. หากยังไม่มี ให้ตรวจจาก Tenant Setting (shop_language)
        if (!$locale && function_exists('tenant') && tenant('id')) {
            try {
                $tenantSettingService = new \App\Services\TenantSettingService();
                $settings = $tenantSettingService->getSettings((int)tenant('id'));
                if (!empty($settings['shop_language']) && in_array($settings['shop_language'], $supportedLocales, true)) {
                    $locale = $settings['shop_language'];
                }
            } catch (\Throwable $e) {
                // Ignore DB error
            }
        }

        // 4. หากยังไม่มี ให้ใช้ defaultLocale
        if (!$locale || !in_array($locale, $supportedLocales, true)) {
            $locale = $defaultLocale;
        }

        // ตั้งค่า Locale ให้กับ Request
        $request->setLocale($locale);

        // บันทึกกลับเข้า Session เพื่อความเสถียรใน Request ถัดไป
        if (!session()->has('locale') || session()->get('locale') !== $locale) {
            session()->set('locale', $locale);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
