<?php

namespace App\Models;

use App\Models\bills;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class payments extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentsFactory> */
    use HasFactory;
    use Searchable;

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

    public function searchbleAs()
    {
        return 'payments_index';
    }
}
