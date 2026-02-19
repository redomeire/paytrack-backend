<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Models\bills;
use Illuminate\Http\Request;

class SearchController extends BaseController
{
    public function search(Request $request)
    {
        try {
            $query = $request->search;
            $bills = bills::search($query)
                ->where('user_id', $request->user()->id)
                ->get();

            return $this->sendResponse(['bills' => $bills], 'Search results');
        } catch (\Throwable $th) {
            return $this->sendError('Search failed', ['error' => $th->getMessage()], 500);
        }
    }
}
