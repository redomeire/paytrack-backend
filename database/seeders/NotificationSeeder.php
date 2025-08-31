<?php

namespace Database\Seeders;

use App\Models\bills;
use App\Models\notification;
use App\Models\notification_type;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        $billIds = bills::pluck('id')->toArray();
        $notificationTypeIds = notification_type::pluck('id')->toArray();

        foreach ($billIds as $billId) {
            notification::create([
                'bill_id' => $billId,
                'notification_type_id' => $notificationTypeIds[0],
                'title' => 'Bill Reminder',
                'message' => 'This is a sample notification message for bill ID: ' . $billId,
            ]);
        }
    }
}
