<?php

namespace Database\Seeders;

use App\Models\notification_type;
use Illuminate\Database\Seeder;

class NotificationTypeSeeder extends Seeder
{
    public function run(): void
    {
        notification_type::create(
            [
                'name' => 'Bil Reminder',
                'description' => 'A reminder for upcoming bills',
            ],
            [
                'name' => 'Payment Confirmation',
                'description' => 'A confirmation for successful payments',
            ],
            [
                'name' => 'Bill Overdue',
                'description' => 'Notification for overdue bills',
            ],
            [
                'name' => 'Payment Failed',
                'description' => 'Notification for failed payment attempts',
            ],
            [
                'name' => 'New Bill Created',
                'description' => 'Notification for newly created bills',
            ]
        );
    }
}
