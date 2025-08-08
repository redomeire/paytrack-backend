<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Models\notification_type;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NotificationTypeController extends BaseController
{
    public function index()
    {
        try {
            $notification_types = notification_type::all();
            return $this->sendResponse($notification_types, 'Notification Types retrieved successfully.');
        } catch (\Throwable $th) {
            return $this->sendError('Error retrieving notification types', [$th->getMessage()], 500);
        }
    }
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:50',
                'description' => 'nullable|string|max:500',
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->errors(), 422);
            }
            $notification_type = notification_type::create($request->all());
            return $this->sendResponse($notification_type, 'Notification Type created successfully.', 201);
        } catch (\Throwable $th) {
            return $this->sendError('Error creating notification type', [$th->getMessage()], 500);
        }
    }
    public function detail($id)
    {
        try {
            $notification_type = notification_type::findOrFail($id);
            return $this->sendResponse($notification_type, 'Notification Type retrieved successfully.');
        } catch (\Throwable $th) {
            return $this->sendError('Error retrieving notification type', [$th->getMessage()], 404);
        }
    }
    public function update(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:50',
                'description' => 'nullable|string|max:500',
            ]);
            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }
            $notification_type = notification_type::findOrFail($id);
            $notification_type->update($request->all());
            return $this->sendResponse($notification_type, 'Notification Type updated successfully.');
        } catch (\Throwable $th) {
            return $this->sendError('Error updating notification type', [$th->getMessage()], 500);
        }
    }
    public function delete($id)
    {
        try {
            $notification_type = notification_type::findOrFail($id);
            $notification_type->delete();
            return $this->sendResponse(null, 'Notification Type deleted successfully.');
        } catch (\Throwable $th) {
            return $this->sendError('Error deleting notification type', [$th->getMessage()], 500);
        }
    }
}
