<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Models\bills;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BillsController extends BaseController
{
    public function index(Request $request)
    {
        try {
            $userId = $request->user()->id;
            $bills = bills::where('user_id', $userId)
                ->with('billCategory')
                ->orderBy('updated_at', 'asc')
                ->get();
            return $this->sendResponse(
                $bills,
                'Bills retrieved successfully.'
            );
        } catch (\Throwable $th) {
            return $this->sendError(
                $th->getMessage(),
                null,
                500
            );
        }
    }
    public function store(Request $request)
    {
        try {
            $userId = $request->user()->id;
            $validator = Validator::make(
                $request->all(),
                [
                    'name' => 'required|string|max:100',
                    'bill_category_id' => 'required|exists:bill_categories,id',
                    'description' => 'nullable|string|max:255',
                    'amount' => 'required|numeric|min:0',
                    'currency' => 'required|string|size:3',
                    'billing_type' => 'required|in:fixed,recurring',
                    'frequency' => 'required|in:monthly,annual,custom',
                    'custom_frequency_days' => 'required_if:frequency,custom|integer|min:1',
                    'notes' => 'nullable|string|max:500',
                    'attachment_url' => 'nullable|url|max:255',
                    'due_date' => 'required|date',
                ]
            );

            if ($validator->fails()) {
                return $this->sendError(
                    $validator->errors(),
                    null,
                    422
                );
            }

            $data = $request->all();
            $bill = bills::create(
                array_merge($data, ['user_id' => $userId])
            );
            return $this->sendResponse(
                $bill,
                'Bill created successfully.',
                201
            );
        } catch (\Exception $e) {
            return $this->sendError(
                $e->getMessage(),
            );
        }
    }
    public function detail(Request $request, $id)
    {
        try {
            $userId = $request->user()->id;
            $bill = bills::where('id', $id)
                ->where('user_id', $userId)
                ->with('billCategory')
                ->first();

            if (!$bill) {
                return $this->sendError(
                    'Bill not found',
                    null,
                    404
                );
            }
            return $this->sendResponse(
                $bill,
                'Bill retrieved successfully.'
            );
        } catch (\Throwable $th) {
            return $this->sendError(
                $th->getMessage(),
                null,
                500
            );
        }
    }
    public function update(Request $request, $id)
    {
        try {
            $userId = $request->user()->id;
            $bill = bills::where('id', $id)
                ->where('user_id', $userId)
                ->first();
            if (!$bill) {
                return $this->sendError(
                    'Bill not found',
                    null,
                    404
                );
            }

            $validator = Validator::make(
                $request->all(),
                [
                    'name' => 'sometimes|required|string|max:100',
                    'user_id' => 'sometimes|required|exists:users,id',
                    'bill_category_id' => 'sometimes|required|exists:bill_categories,id',
                    'description' => 'nullable|string|max:255',
                    'amount' => 'sometimes|required|numeric|min:0',
                    'currency' => 'sometimes|required|string|size:3',
                    'billing_type' => 'sometimes|required|in:fixed,recurring',
                    'frequency' => 'sometimes|required|in:monthly,annual,custom',
                    'custom_frequency_days' => 'required_if:frequency,custom|integer|min:1',
                    'first_due_date' => 'sometimes|required|date',
                    'next_due_date' => 'sometimes|required|date|after_or_equal:first_due_date',
                    'last_paid_date' => 'nullable|date|after_or_equal:first_due_date',
                    'auto_advance' => 'boolean',
                    'notes' => 'nullable|string|max:500',
                    'attachment_url' => 'nullable|url|max:255',
                ]
            );

            if ($validator->fails()) {
                return $this->sendError(
                    'Validation Error',
                    $validator->errors()
                );
            }

            $data = $request->all();

            $bill->update($data);
            return $this->sendResponse(
                $bill,
                'Bill updated successfully.'
            );
        } catch (\Throwable $th) {
            return $this->sendError(
                $th->getMessage(),
                null,
                500
            );
        }
    }
    public function delete(Request $request, $id)
    {
        try {
            $userId = $request->user()->id;
            $bill = bills::where('id', $id)
                ->where('user_id', $userId)
                ->first();
            if (!$bill) {
                return $this->sendError(
                    'Bill not found',
                    null,
                    404
                );
            }
            $bill->delete();
            return $this->sendResponse(
                null,
                'Bill deleted successfully.'
            );
        } catch (\Throwable $th) {
            return $this->sendError(
                $th->getMessage(),
                null,
                500
            );
        }
    }
}
