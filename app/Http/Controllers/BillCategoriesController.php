<?php

namespace App\Http\Controllers;

use App\Models\bill_categories;
use App\Http\Requests\Storebill_categoriesRequest;
use App\Http\Requests\Updatebill_categoriesRequest;

class BillCategoriesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Storebill_categoriesRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(bill_categories $bill_categories)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(bill_categories $bill_categories)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Updatebill_categoriesRequest $request, bill_categories $bill_categories)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(bill_categories $bill_categories)
    {
        //
    }
}
