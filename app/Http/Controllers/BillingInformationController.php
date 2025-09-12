<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Models\BillingInformation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class BillingInformationController extends BaseController
{
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $billingInformation = BillingInformation::where('user_id', $user->id)->get();
            if ($billingInformation->isEmpty()) {
                return $this->sendError('No Billing Information found.', [], 404);
            }
            return $this->sendResponse($billingInformation, 'Billing Information retrieved successfully.');
        } catch (\Throwable $th) {
            Log::error('Error retrieving billing information: ' . $th->getMessage());
            return $this->sendError('An error occurred while retrieving billing information.', ['error' => $th->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'type' => 'required|in:BANK_ACCOUNT,EWALLET',
            'details' => 'required|array',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors(), null, 422);
        }

        try {
            $user = $request->user();
            $payload = $request->all();
            $billingInformation = BillingInformation::create([
                'user_id' => $user->id,
                'name' => $payload['name'],
                'type' => $payload['type'],
                'details' => json_encode($payload['details']),
            ]);
            return $this->sendResponse($billingInformation, 'Billing Information created successfully.', 201);
        } catch (\Throwable $th) {
            Log::error('Error creating billing information: ' . $th->getMessage());
            return $this->sendError('An error occurred while creating billing information.', ['error' => $th->getMessage()], 500);
        }
    }

    public function show(Request $request, $id)
    {
        try {
            $user = request()->user();
            $billingInformation = BillingInformation::where([
                'user_id' => $user->id,
                'id' => $id,
            ])->first();
            if (!$billingInformation) {
                return $this->sendError('Billing Information not found.', [], 404);
            }
            return $this->sendResponse($billingInformation, 'Billing Information retrieved successfully.');
        } catch (\Throwable $th) {
            Log::error('Error retrieving billing information: ' . $th->getMessage());
            return $this->sendError('An error occurred while retrieving billing information.', ['error' => $th->getMessage()], 500);
        }
    }
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'default' => 'sometimes|required|boolean',
            'name' => 'sometimes|required|string|max:100',
            'type' => 'sometimes|required|in:BANK_ACCOUNT,EWALLET',
            'details' => 'sometimes|required|array',
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors(), null, 422);
        }
        try {
            $user = $request->user();
            $billingInformation = BillingInformation::where('user_id', $user->id)->where('id', $id)->first();
            if (!$billingInformation) {
                return $this->sendError('Billing Information not found.', [], 404);
            }
            $payload = $request->all();
            $billingInformation->update($payload);
            return $this->sendResponse($billingInformation, 'Billing Information updated successfully.');
        } catch (\Throwable $th) {
            Log::error('Error updating billing information: ' . $th->getMessage());
            return $this->sendError('An error occurred while updating billing information.', ['error' => $th->getMessage()], 500);
        }
    }
    public function delete(Request $request, $id)
    {
        try {
            $user = $request->user();
            $billingInformation = BillingInformation::where('user_id', $user->id)->where('id', $id)->first();
            if (!$billingInformation) {
                return $this->sendError('Billing Information not found.', [], 404);
            }
            $billingInformation->delete();
            return $this->sendResponse([], 'Billing Information deleted successfully.');
        } catch (\Throwable $th) {
            Log::error('Error deleting billing information: ' . $th->getMessage());
            return $this->sendError('An error occurred while deleting billing information.', ['error' => $th->getMessage()], 500);
        }
    }
}
