<?php

namespace App\Models;

use App\Models\bills;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class payments extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentsFactory> */
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'bill_id',
        'amount',
        'currency',
        'paid_date',
        'due_date',
        'payment_method',
        'payment_reference',
        'notes',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->{$model->getKeyName()} = (string) Str::uuid();
        });
    }

    public function bill()
    {
        return $this->belongsTo(bills::class, 'bill_id', 'id');
    }
}
