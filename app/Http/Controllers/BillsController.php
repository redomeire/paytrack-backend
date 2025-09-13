<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Models\bills;
use App\Models\bill_categories;
use App\Models\bill_series;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BillsController extends BaseController
{
    public function getUpcomingBills(Request $request)
    {
        try {
            $userId = $request->user()->id;
            $search = $request->query('search', '');
            $categoryId = $request->query('bill_category_id');
            $limit = $request->query('limit', 10);

            $bills = bills::where('user_id', $userId)
                ->when($categoryId, function ($query) use ($categoryId) {
                    return $query->where('bill_category_id', $categoryId);
                })
                ->where('name', 'like', '%' . $search . '%')
                ->where('status', 'pending')
                ->with('billCategory')
                ->orderBy('due_date', 'asc')
                ->paginate($limit ?? 10);

            return $this->sendResponse(
                $bills,
                'Upcoming bills retrieved successfully.'
            );
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage());
        }
    }
    public function getRecurringSeries(Request $request)
    {
        try {
            $userId = $request->user()->id;
            $search = $request->query('search', '');
            $categoryId = $request->query('bill_category_id');

            $series = bill_series::where('user_id', $userId)
                ->when($categoryId, function ($query) use ($categoryId) {
                    return $query->where('bill_category_id', $categoryId);
                })
                ->where('name', 'like', '%' . $search . '%')
                ->with('billCategory') 
                ->with('bills')
                ->orderBy('updated_at', 'desc')
                ->paginate(10);

            return $this->sendResponse(
                $series,
                'Bill series retrieved successfully.'
            );
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage());
        }
    }
    public function storeBill(Request $request)
    {
        try {
            $userId = $request->user()->id;
            $validator = Validator::make(
                $request->all(),
                [
                    'name' => 'required|string|max:100',
                    'bill_category_id' => 'nullable|exists:bill_categories,id',
                    'description' => 'nullable|string|max:255',
                    'amount' => 'required|numeric|min:0',
                    'currency' => 'required|string|size:3',
                    'due_date' => 'required|string',
                    'period' => 'nullable|string',
                    'notes' => 'nullable|string|max:500',
                    'attachment_url' => 'nullable|url|max:255',
                    'billing_information_id' => 'nullable|exists:billing_information,id',
                    'account_number' => 'required|string|max:50',
                    'account_name' => 'required|string|max:100',
                    'bank_code' => 'required|string|max:20',
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
    public function storeBillSeries(Request $request)
    {
        try {
            $validator = Validator::make(
                $request->all(),
                [
                    'name' => 'required|string|max:100',
                    'bill_category_id' => 'required|exists:bill_categories,id',
                    'description' => 'nullable|string|max:255',
                    'currency' => 'required|string|size:3',
                    'amount' => 'nullable|numeric|min:0',
                    'frequency' => 'required|in:monthly,annual,custom',
                    'frequency_interval' => 'required|integer|min:1',
                    'custom_frequency_days' => 'required_if:frequency,custom|integer|min:1',
                    'due_day' => 'required|integer|min:1|max:31',
                    'start_date' => 'required|date',
                    'is_active' => 'required|boolean',
                    'attachment_url' => 'nullable|url|max:255',
                    'billing_information_id' => 'nullable|exists:billing_information,id',
                    'account_number' => 'required|string|max:50',
                    'account_name' => 'required|string|max:100',
                    'bank_code' => 'required|string|max:20',
                ]
            );

            if ($validator->fails()) {
                return $this->sendError(
                    $validator->errors(),
                    null,
                    422
                );
            }

            $bill_series = DB::transaction(function () use ($request) {
                $userId = $request->user()->id;
                $data = $request->all();

                $data['is_active'] = $request->input('is_active', true);
                $data['frequency_interval'] = $request->input('frequency_interval', 1);

                if ($data['frequency'] !== 'custom') {
                    $data['custom_frequency_days'] = null;
                }

                $series = bill_series::create(array_merge($data, ['user_id' => $userId]));

                $startDate = Carbon::parse($series->start_date);
                $firstDueDate = $startDate->day($series->due_day);

                if ($firstDueDate->isPast()) {
                    $firstDueDate->addMonth();
                }

                bills::create([
                    'user_id' => $userId,
                    'bill_series_id' => $series->id,
                    'bill_category_id' => $series->bill_category_id,
                    'name' => $series->name,
                    'description' => $series->description,
                    'amount' => $series->amount,
                    'currency' => $series->currency,
                    'period' => $firstDueDate->startOfMonth(),
                    'due_date' => $firstDueDate,
                    'notes' => 'Invoice pertama dibuat secara otomatis.',
                    'attachment_url' => $data['attachment_url'] ?? null,
                ]);
                return $series;
            });

            return $this->sendResponse(
                $bill_series,
                'Bill series created successfully.',
                201
            );
        } catch (\Exception $e) {
            return $this->sendError(
                $e->getMessage(),
            );
        }
    }
    public function detailBill(Request $request, $id)
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
    public function detailBillSeries(Request $request, $id)
    {
        try {
            $userId = $request->user()->id;
            $billSeries = bill_series::where('id', $id)
                ->where('user_id', $userId)
                ->with('billCategory')
                ->with('bills')
                ->first();

            if (!$billSeries) {
                return $this->sendError(
                    'Bill series not found',
                    null,
                    404
                );
            }
            return $this->sendResponse(
                $billSeries,
                'Bill series retrieved successfully.'
            );
        } catch (\Throwable $th) {
            return $this->sendError(
                $th->getMessage(),
                null,
                500
            );
        }
    }
    public function updateBill(Request $request, $id)
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
                    'bill_category_id' => 'sometimes|required|exists:bill_categories,id',
                    'description' => 'nullable|string|max:255',
                    'amount' => 'sometimes|required|numeric|min:0',
                    'currency' => 'sometimes|required|string|size:3',
                    'due_date' => 'sometimes|required|string',
                    'period' => 'nullable|string',
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
    public function updateBillSeries(Request $request, $id)
    {
        try {
            $userId = $request->user()->id;
            $bill_series = bill_series::where('id', $id)
                ->where('user_id', $userId)
                ->first();
            if (!$bill_series) {
                return $this->sendError(
                    'Bill series not found',
                    null,
                    404
                );
            }

            $validator = Validator::make(
                $request->all(),
                [
                    'name' => 'sometimes|required|string|max:100',
                    'bill_category_id' => 'sometimes|required|exists:bill_categories,id',
                    'description' => 'nullable|string|max:255',
                    'due_date' => 'sometimes|required|string',
                    'frequency' => 'sometimes|required|in:monthly,annual,custom',
                    'custom_frequency_days' => 'nullable|required_if:frequency,custom|integer|min:1',
                    'frequency_interval' => 'sometimes|required|integer|min:1',
                    'due_day' => 'sometimes|required|integer|min:1|max:31',
                    'start_date' => 'sometimes|required|date',
                    'is_active' => 'sometimes|required|boolean'
                ]
            );

            if ($validator->fails()) {
                return $this->sendError(
                    'Validation Error',
                    $validator->errors()
                );
            }

            $data = $request->all();

            if ($data['frequency'] !== 'custom') {
                $data['custom_frequency_days'] = null;
            }

            $bill_series->update($data);
            return $this->sendResponse(
                $bill_series,
                'Bill series updated successfully.'
            );
        } catch (\Throwable $th) {
            return $this->sendError(
                $th->getMessage(),
                null,
                500
            );
        }
    }
    public function deleteBill(Request $request, $id)
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
    public function deleteBillSeries(Request $request, $id)
    {
        try {
            $userId = $request->user()->id;
            $bill_series = bill_series::where('id', $id)
                ->where('user_id', $userId)
                ->first();
            if (!$bill_series) {
                return $this->sendError(
                    'Bill series not found',
                    null,
                    404
                );
            }
            DB::transaction(function () use ($bill_series) {
                bills::where('bill_series_id', $bill_series->id)->delete();
                $bill_series->delete();
            });
            return $this->sendResponse(
                null,
                'Bill series and associated bills deleted successfully.'
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
