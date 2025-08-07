<?php

namespace Database\Seeders;

use App\Models\notification;
use App\Models\notification_read;
use App\Models\User;
use Illuminate\Database\Seeder;

class NotificationReadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $notificationIds = notification::pluck('id')->toArray();
        $userIds = User::pluck('id')->toArray();

        foreach ($userIds as $userId) {
            notification_read::create([
                'notification_id' => $notificationIds[0],
                'user_id' => $userId,
                'is_read' => false,
                'read_at' => null,
            ]);
        }
    }
}
