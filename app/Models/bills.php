<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class bills extends Model
{
    /** @use HasFactory<\Database\Factories\BillsFactory> */
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'bill_category_id',
        'name',
        'description',
        'amount',
        'currency',
        'billing_type',
        'frequency',
        'custom_frequency_days',
        'first_due_date',
        'next_due_date',
        'last_paid_date',
        'auto_advance',
        'notes',
        'attachment_url',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->{$model->getKeyName()} = (string) Str::uuid();
        });
    }
}
