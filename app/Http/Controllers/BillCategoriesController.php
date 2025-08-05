<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\bill_categories;
use App\Http\Controllers\BaseController;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\Storebill_categoriesRequest;
use App\Http\Requests\Updatebill_categoriesRequest;

class BillCategoriesController extends BaseController
{
    public function getBillCategories()
    {
        $bill_categories = bill_categories::all();
        return $this->sendResponse($bill_categories, 'Bill categories retrieved successfully.');
    }
    
    public function create(Request $request)
    {
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
        $bill_category = bill_categories::create($data);
        return $this->sendResponse($bill_category, 'Bill category created successfully.', 201);
    }

    public function detail($id)
    {
        $bill_category = bill_categories::find($id);

        if (!$bill_category) {
            return $this->sendError('Bill category not found', [], 404);
        }
        return $this->sendResponse($bill_category, 'Bill category retrieved successfully.');
    }

    public function update(Request $request, $id)
    {
        $bill_categories = bill_categories::find($id);
        if (!$bill_categories) {
            return $this->sendError('Bill category not found', [], 404);
        }
        
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:100',
            'description' => 'nullable|string|max:500',
            'color' => 'nullable|string|size:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'icon' => 'nullable|string|max:100',
            'is_default' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors(), 422);
        }

        $bill_categories->update($request->all());
        return $this->sendResponse($bill_categories, 'Bill category updated successfully.');
    }

    public function delete($id)
    {
        $bill_category = bill_categories::find($id);
        if (!$bill_category) {
            return $this->sendError('Bill category not found', [], 404);
        }
        $bill_category->delete();
        return $this->sendResponse(null, 'Bill category deleted successfully.');        
    }
}
