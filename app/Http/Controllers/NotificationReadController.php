<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Models\notification_read;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NotificationReadController extends BaseController
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;
        try {
            $notificationReads = notification_read::where('user_id', $userId)->get();
            return $this->sendResponse($notificationReads, 'Notification reads retrieved successfully');
        } catch (\Throwable $th) {
            return $this->sendError('Error retrieving notification reads', [$th->getMessage()]);
        }
    }
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'notification_id' => 'required|exists:notifications,id',
                'user_id' => 'required|exists:users,id',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors());
            }

            $notificationReadRequest = $request->all();
            $notificationRead = notification_read::create($notificationReadRequest);
            return $this->sendResponse($notificationRead, 'Notification read created successfully', 201);
        } catch (\Throwable $th) {
            return $this->sendError('Error creating notification read', [$th->getMessage()]);
        }
    }
    public function show($id)
    {
        try {
            $notificationRead = notification_read::findOrFail($id);
            return $this->sendResponse($notificationRead, 'Notification read retrieved successfully');
        } catch (\Throwable $th) {
            return $this->sendError('Notification read not found', [$th->getMessage()], 404);
        }
    }
    public function update(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'notification_id' => 'sometimes|exists:notifications,id',
                'user_id' => 'sometimes|exists:users,id',
                'is_read' => 'boolean',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors());
            }

            $notificationRead = notification_read::findOrFail($id);
            $notificationRead->update($request->all());
            return $this->sendResponse($notificationRead, 'Notification read updated successfully');
        } catch (\Throwable $th) {
            return $this->sendError('Error updating notification read', [$th->getMessage()]);
        }
    }
    public function delete($id)
    {
        try {
            $notificationRead = notification_read::findOrFail($id);
            $notificationRead->delete();
            return $this->sendResponse(null, 'Notification read deleted successfully');
        } catch (\Throwable $th) {
            return $this->sendError('Error deleting notification read', [$th->getMessage()]);
        }
    }
}
