<?php

namespace App\Cells;

use CodeIgniter\View\Cells\Cell;

class NotificationCell extends Cell
{
    /**
     * ตัวแปร public ใน Controlled Cell จะถูกส่งไปยัง view อัตโนมัติ
     * ในการใช้งานจริง สามารถคิวรีจาก Database หรือ Model ในคลาสนี้ได้เลย
     */
    public int $unreadCount = 3;

    public array $notifications = [
        [
            'title' => '4 new messages',
            'icon'  => 'bi-envelope',
            'time'  => '3 mins',
        ],
        [
            'title' => '8 friend requests',
            'icon'  => 'bi-people-fill',
            'time'  => '12 hours',
        ],
        [
            'title' => '3 new reports',
            'icon'  => 'bi-file-earmark-fill',
            'time'  => '2 days',
        ],
    ];
}
