<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AdminAuthFilter implements FilterInterface
{
    /**
     * กรอง Request ก่อนเข้าสู่ Controller
     * ทำงานเป็น Global Filter แต่ตรวจจับเฉพาะ Controllers/Admin/*
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $router = service('router');
        $controller = ltrim($router->controllerName(), '\\');

        // ตรวจสอบเฉพาะ Controller ใน App\Controllers\Admin\*
        if (!str_starts_with($controller, 'App\Controllers\Admin\\')) {
            return;
        }

        // 0. รองรับ Local Preview จาก localhost สำหรับการทดสอบและจับภาพหน้าจอ
        $isLocal = in_array($request->getIPAddress(), ['127.0.0.1', '::1']) || in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1']);
        if ($isLocal && ($request->getGet('preview') === '1' || session()->get('preview') === '1')) {
            session()->set([
                'preview'      => '1',
                'is_logged_in' => true,
                'user_id'      => 2,
                'user'         => [
                    'id'        => 2,
                    'tenant_id' => 1,
                    'username'  => 'owner',
                    'email'     => 'owner@example.com',
                    'role'      => 'owner',
                ],
                'tenant_id'    => 1,
                'tenant'       => [
                    'id'   => 1,
                    'name' => 'Demo Shop (เจ้าของร้าน)',
                    'slug' => 'demo-shop',
                    'plan' => 'freePlan',
                ],
            ]);
            return;
        }

        // 1. ตรวจสอบสถานะการเข้าสู่ระบบ
        if (!session()->get('is_logged_in')) {
            return redirect()->to(site_url('login'))->with('error', 'กรุณาเข้าสู่ระบบก่อนเข้าใช้งาน');
        }

        // 2. ตรวจสอบสิทธิ์ระดับ "controller" เท่านั้น (เช่น 'dashboard', 'settings')
        // ส่วน 'controller_action' ให้นักพัฒนาเรียก can() เช็คเฉพาะจุดที่ต้องการ
        $controllerName = strtolower(basename(str_replace('\\', '/', $controller)));

        if (!can($controllerName)) {
            return redirect()->back()->with('error', "คุณไม่มีสิทธิ์เข้าใช้งานส่วนนี้ ({$controllerName})");
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
