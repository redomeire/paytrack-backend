<?php

namespace App\Http\Controllers;

use App\Models\bills;
use App\Http\Requests\StorebillsRequest;
use App\Http\Requests\UpdatebillsRequest;

class BillsController extends Controller
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
    public function store(StorebillsRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(bills $bills)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(bills $bills)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatebillsRequest $request, bills $bills)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(bills $bills)
    {
        //
    }
}
