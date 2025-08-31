<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Models\bill_categories;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BillCategoriesController extends BaseController
{
    public function getBillCategories(Request $request)
    {
        try {
            $userId = $request->user()->id;
            $bill_categories = bill_categories::where('user_id', $userId)
                ->orderBy('created_at', 'asc')
                ->get();
            return $this->sendResponse($bill_categories, 'Bill categories retrieved successfully.');
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage(), null, 500);
        }
    }

    public function create(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:100',
                'description' => 'nullable|string|max:500',
                'color' => 'nullable|string|size:7|regex:/^#[0-9A-Fa-f]{6}$/',
                'icon' => 'nullable|string|max:100',
                'is_default' => 'boolean',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }

            $data = $request->all();
            $userId = $request->user()->id;
            $bill_category = bill_categories::create(
                array_merge($data, ['user_id' => $userId])
            );
            return $this->sendResponse($bill_category, 'Bill category created successfully.', 201);
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage(), null, 500);
        }
    }

    public function detail(Request $request, $id)
    {
        try {
            $bill_category = bill_categories::where('id', $id)
                ->where('user_id', $request->user()->id)
                ->first();

            if (!$bill_category) {
                return $this->sendError('Bill category not found', null, 404);
            }
            return $this->sendResponse($bill_category, 'Bill category retrieved successfully.');
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage(), null, 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $userId = $request->user()->id;
            $bill_categories = bill_categories::where('id', $id)
                ->where('user_id', $userId)
                ->first();
            if (!$bill_categories) {
                return $this->sendError('Bill category not found', null, 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:100',
                'description' => 'nullable|string|max:500',
                'color' => 'nullable|string|size:7|regex:/^#[0-9A-Fa-f]{6}$/',
                'icon' => 'nullable|string|max:100',
                'is_default' => 'boolean',
            ]);

            if ($validator->fails()) {
                return $this->sendError($validator->errors(), null, 422);
            }

            $bill_categories->update($request->all());
            return $this->sendResponse($bill_categories, 'Bill category updated successfully.');
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage(), null, 500);
        }
    }

    public function delete($id)
    {
        try {
            $userId = request()->user()->id;
            $bill_category = bill_categories::where('id', $id)
                ->where('user_id', $userId)
                ->first();
            if (!$bill_category) {
                return $this->sendError('Bill category not found', null, 404);
            }
            $bill_category->delete();
            return $this->sendResponse(null, 'Bill category deleted successfully.');
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage(), null, 500);
        }
    }
}
