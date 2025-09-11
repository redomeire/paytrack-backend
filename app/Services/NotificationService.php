<?php
namespace App\Services;

use App\Dto\NotificationDto;
use App\Models\notification;
use App\Models\notification_read;
use App\Models\notification_type;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    public function createNotification(NotificationDto $dto): void {
        try {
            Log::info("Creating notification for bill ID {$dto->billId} and user ID {$dto->userId}");
            $notificationType = notification_type::firstOrCreate([
                'name' => $dto->type,
            ], [
                'name' => $dto->type,
                'description' => $dto->description,
            ]);
            if (!$notificationType) {
                Log::error("Notification type ID {$dto->type} not found.");
                return;
            }
            DB::transaction(
                function () use ($notificationType, $dto) {
                    $notification = notification::create([
                        'bill_id' => $dto->billId,
                        'notification_type_id' => $notificationType->id,
                        'title' => $dto->title,
                        'message' => $dto->message,
                    ]);

                    notification_read::create([
                        'notification_id' => $notification->id,
                        'user_id' => $dto->userId,
                    ]);
                });
        } catch (\Throwable $th) {
            Log::error('Error creating notification: ' . $th->getMessage());
            throw $th;
        }
    }
}
