<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class bills extends Model
{
    /** @use HasFactory<\Database\Factories\BillsFactory> */
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;
}
