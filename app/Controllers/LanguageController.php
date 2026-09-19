<?php

namespace App\Controllers;

class LanguageController extends BaseController
{
    /**
     * สลับภาษาของระบบ (th, en)
     */
    public function switch(string $locale)
    {
        $supportedLocales = config('App')->supportedLocales ?? ['th', 'en'];

        if (in_array($locale, $supportedLocales, true)) {
            session()->set('locale', $locale);

            // หากล็อกอินอยู่ ให้บันทึกการตั้งค่าลง profile ผู้ใช้ด้วย
            if (function_exists('auth_user') && auth_user('id')) {
                try {
                    $userSettingService = new \App\Services\UserSettingService();
                    $userSettingService->saveUserSettings((int)auth_user('id'), ['language' => $locale]);
                } catch (\Throwable $e) {
                    // Ignore error
                }
            }
        }

        // Redirect กลับหน้าเดิม หรือหน้าแรก
        $previousUrl = previous_url();
        if ($previousUrl && $previousUrl !== current_url()) {
            return redirect()->to($previousUrl);
        }

        return redirect()->back();
    }
}
