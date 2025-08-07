<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Models\notification;
use App\Models\notification_read;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class NotificationController extends BaseController
{
    // USER : mendapatkan semua notifikasi user (public maupun khusus user tertentu)
    public function getAllNotificationUser(Request $request)
    {
        $userId = $request->user()->id;
        try {
            $notifications = DB::table('notifications')
                ->join('notification_reads', 'notifications.id', '=', 'notification_reads.notification_id')
                ->join('users', 'notification_reads.user_id', '=', 'users.id')
                ->where('notifications.user_id', $userId)
                ->orWhere('notifications.user_id', null)
                ->where('notification_reads.user_id', $userId)
                ->orderBy('notifications.created_at', 'desc')
                ->select(
                    'notifications.id',
                    'notification_reads.id as read_id',
                    'notifications.title',
                    'notifications.message',
                    'notifications.created_at',
                    'notification_reads.is_read',
                    'notification_reads.read_at'
                )->get();

            return $this->sendResponse($notifications, 'Notifications retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }
    // USER : membaca notifikasi tertentu
    public function readNotification($readId)
    {
        $userId = $request->user()->id;
        try {
            $notification = notification_read::find($readId);
            if (!$notification || $notification->user_id !== $userId) {
                return $this->sendError('Notification not found', _, 404);
            }
            $notification->is_read = true;
            $notification->read_at = now();
            $notification->save();

            return $this->sendResponse($notification, 'Notification has been read successfully');
        } catch (\Exception $e) {
            Log::error('Error reading notification: ' . $e->getMessage());
            return $this->sendError('Server error', _, 500);
        }
    }
    // ADMIN : mendapatkan semua notifikasi public
    public function getAllNotificationAdminPublic(Request $request)
    {
        try {
            $notifications = notification::whereNull('user_id')
                ->orderBy('created_at', 'desc')
                ->get();

            return $this->sendResponse($notifications, 'Public notifications retrieved successfully');
        } catch (\Throwable $th) {
            Log::error('Error retrieving public notifications: ' . $th->getMessage());
            return $this->sendError('Server error', _, 500);
        }
    }
    // ADMIN : membuat notifikasi baru untuk semua user
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'bill_id' => 'required|exists:bills,id',
                'notification_type_id' => 'required|exists:notification_types,id',
                'title' => 'required|string|max:200',
                'message' => 'required|string|max:500',
            ]);
            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors());
            }
            $notificationRequest = $request->all();
            $notification = notification::create($notificationRequest);
            return $this->sendResponse($notification, 'Notification created successfully', 201);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }
    // ADMIN : mengupdate notifikasi tertentu
    public function update(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'notification_type_id' => 'sometimes|exists:notification_types,id',
                'title' => 'required|string|max:200',
                'message' => 'required|string|max:500',
            ]);
            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors());
            }
            $notification = notification::findOrFail($id);
            $notification->update($request->all());
            return $this->sendResponse($notification, 'Notification updated successfully');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }
    // ADMIN : menghapus notifikasi tertentu
    public function delete($id)
    {
        try {
            $notification = notification::findOrFail($id);
            $notification->delete();
            return $this->sendResponse(null, 'Notification deleted successfully');
        } catch (\Exception $e) {
            return $this->sendError('Notification not found', _, 404);
        }
    }
}
