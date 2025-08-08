<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Models\payments;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaymentsController extends BaseController
{
    public function index(Request $request)
    {
        try {
            $userId = $request->user()->id;
            $payments = payments::with('bill')
                ->whereHas('bill', function ($query) use ($userId) {
                    $query->where('user_id', $userId);
                })
                ->orderBy('paid_date', 'desc')
                ->paginate(10);
            return $this->sendResponse($payments, 'Payments retrieved successfully.');
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage(), null, 500);
        }
    }
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'bill_id' => 'required|uuid|exists:bills,id',
                'amount' => 'required|numeric',
                'currency' => 'required|string|in:IDR,USD',
                'paid_date' => 'required|date',
                'due_date' => 'required|date|after_or_equal:paid_date',
                'payment_method' => 'required|string|max:50',
                'payment_reference' => 'required|string|max:100',
                'notes' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors());
            }

            try {
                $payment = payments::create($request->all());
                return $this->sendResponse($payment, 'Payment created successfully.', 201);
            } catch (\Throwable $th) {
                return $this->sendError($th->getMessage());
            }
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage(), null, 500);
        }
    }
    public function detail($id)
    {
        try {
            $userId = $request->user()->id;
            $payment = payments::find($id);
            if (!$payment) {
                return $this->sendError('Payment not found', null, 404);
            }
            return $this->sendResponse($payment, 'Payment retrieved successfully.');
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage(), null, 500);
        }
    }
    public function update(Request $request, $id)
    {
        try {
            $userId = $request->user()->id;
            $validator = Validator::make($request->all(), [
                'bill_id' => 'sometimes|uuid|exists:bills,id',
                'amount' => 'sometimes|numeric',
                'currency' => 'sometimes|string|in:IDR,USD',
                'paid_date' => 'sometimes|date',
                'due_date' => 'sometimes|date|after_or_equal:paid_date',
                'payment_method' => 'sometimes|string|max:50',
                'payment_reference' => 'sometimes|string|max:100',
                'notes' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors());
            }

            $payment = payments::where('id', $id)
                ->whereHas('bill', function ($query) use ($userId) {
                    $query->where('user_id', $userId);
                })
                ->first();
            if (!$payment) {
                return $this->sendError('Payment not found', null, 404);
            }
            $payment->update($request->all());
            return $this->sendResponse($payment, 'Payment updated successfully.');
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage());
        }
    }
    public function delete($id)
    {
        try {
            $payment = payments::where('id', $id)
                ->whereHas('bill', function ($query) use ($userId) {
                    $query->where('user_id', $userId);
                })
                ->first();
            if (!$payment) {
                return $this->sendError('Payment not found', null, 404);
            }
            $payment->delete();
            return $this->sendResponse(null, 'Payment deleted successfully.');
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage(), null, 500);
        }
    }
}
