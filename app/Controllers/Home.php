<?php

namespace App\Controllers;

class Home extends BaseController
{
    /**
     * หน้าแรก / Landing Page สำหรับบุคคลทั่วไป (Public Area)
     */
    public function index(): string
    {
        $data = [
            'title' => 'ยินดีต้อนรับสู่ระบบบริหารจัดการ (Landing Page)',
        ];

        return view('home', $data);
    }

    /**
     * หน้า Dashboard ตัวอย่าง
     */
    public function starter(): string
    {
        $data = [
            'title' => 'Dashboard',
        ];

        return view('dashboard', $data);
    }

    /**
     * หน้าตัวอย่างการใช้งาน Layout
     */
    public function example(): string
    {
        $data = [
            'title' => 'หน้าตัวอย่าง (Example Page)',
        ];

        return view('layouts/example', $data);
    }
}
