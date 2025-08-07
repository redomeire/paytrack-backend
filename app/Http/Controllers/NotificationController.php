<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Models\notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NotificationController extends BaseController
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;
        try {
            // $notifications = DB::table('notifications')
            //     ->join('notification_reads', 'notifications.id', '=', 'notification_reads.notification_id')
            //     ->join('users', 'notification_reads.user_id', '=', 'users.id')
            //     ->where('users.id', $userId)
            //     ->get();
            $notifications = notification::join('notification_reads', 'notifications.id', '=', 'notification_reads.notification_id')
                ->join('users', 'notification_reads.user_id', '=', 'users.id')
                ->where('users.id', $userId)
                ->select([
                    'notifications.id as id',
                    'notification_reads.id as notification_read_id',
                    'users.id as user_id',
                    'notifications.title',
                    'notification_reads.read_at',
                ])
                ->get()->makeHidden(['password', 'remember_token']);

            return $this->sendResponse($notifications, 'Notifications retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }
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
    public function detail(Request $request, $id)
    {
        $userId = $request->user()->id;
        try {
            // $notification = notification::with(['notificationReads' => function ($query) use ($userId) {
            //     $query
            //         ->where('user_id', $userId);
            // }])
            //     ->where('id', $id)
            //     ->first();
            $notification = notification::join('notification_reads', 'notifications.id', '=', 'notification_reads.notification_id')
                ->join('users', 'notification_reads.user_id', '=', 'users.id')
                ->where('notifications.id', $id)
                ->where('users.id', $userId)
                ->select([
                    'notifications.id as id',
                    'notification_reads.id as notification_read_id',
                    'users.id as user_id',
                    'notifications.title',
                    'notifications.message',
                    'notification_reads.is_read',
                ])
                ->firstOrFail();

            return $this->sendResponse($notification, 'Notification retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError('Notification not found', _, 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'bill_id' => 'sometimes|exists:bills,id',
                'notification_type_id' => 'sometimes|exists:notification_types,id',
                'title' => 'sometimes|string|max:200',
                'message' => 'sometimes|string|max:500',
                'is_read' => 'sometimes|boolean',
                'read_at' => 'nullable|date',
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
    public function delete($id)
    {
        try {
            $notification = notification::findOrFail($id);
            $notification->delete();
            return $this->sendResponse(null, 'Notification deleted successfully');
        } catch (\Exception $e) {
            return $this->sendError('Notification not found', [], 404);
        }
    }
}
