<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class bill_categories extends Model
{
    /** @use HasFactory<\Database\Factories\BillCategoriesFactory> */
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;
}
