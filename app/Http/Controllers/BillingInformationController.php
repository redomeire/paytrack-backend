<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Models\BillingInformation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class BillingInformationController extends BaseController
{
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $search = $request->query('search');
            $type = $request->query('type');
            $billingInformation = BillingInformation::where('user_id', $user->id)
                ->where(function ($query) use ($search, $type) {
                    if ($search) {
                        $query->where('name', 'like', '%' . $search . '%');
                    }
                    if ($type) {
                        $query->where('type', $type);
                    }
                })
                ->paginate(10);
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
            'details' => 'required|string',
            'default' => 'sometimes|required|boolean',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors(), null, 422);
        }

        try {
            $user = $request->user();
            $payload = $request->all();
            // if default is true, set all other billing information to false
            if ($request->has('default') && $request->default) {
                $user = $request->user();
                BillingInformation::where('user_id', $user->id)->update(['default' => false]);
            }

            $billingInformation = BillingInformation::create([
                'user_id' => $user->id,
                'name' => $payload['name'],
                'type' => $payload['type'],
                'details' => $payload['details'],
                'default' => $payload['default'],
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
    public function setAsDefault(Request $request, $id)
    {
        try {
            $user = $request->user();
            DB::beginTransaction();
            $billingInformation = BillingInformation::where([
                'user_id' => $user->id,
                'default' => false,
                'id' => $id,
            ])->first();
            if (!$billingInformation) {
                return $this->sendError('Billing Information not found.', [], 404);
            }
            BillingInformation::where([
                'user_id' => $user->id,
                'default' => true,
            ])->update(['default' => false]);
            $billingInformation->default = true;
            $billingInformation->save();
            DB::commit();
            return $this->sendResponse($billingInformation, 'Billing Information set as default successfully.');
        } catch (\Throwable $th) {
            Log::error('Error setting billing information as default: ' . $th->getMessage());
            return $this->sendError('An error occurred while setting billing information as default.', ['error' => $th->getMessage()], 500);
        }
    }
}
