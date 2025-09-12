<?php

namespace App\Models;

use App\Models\User;
use App\Models\bills;
use Illuminate\Support\Str;
use App\Models\bill_categories;
use App\Models\BillingInformation;

use Illuminate\Database\Eloquent\Model;
use Database\Factories\BillSeriesFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class bill_series extends Model
{
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'bill_category_id',
        'name',
        'description',
        'frequency',
        'custom_frequency_days',
        'frequency_interval',
        'due_day',
        'start_date',
        'is_active',
        'amount',
        'currency'
    ];

    protected $casts = [
        'start_date' => 'date',
        'is_active' => 'boolean',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->{$model->getKeyName()} = (string) Str::uuid();
        });
    }

    protected static function newFactory(): Factory
    {
        return BillSeriesFactory::new();
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function bills()
    {
        return $this->hasMany(bills::class, 'bill_series_id', 'id');
    }

    public function billCategory()
    {
        return $this->belongsTo(bill_categories::class, 'bill_category_id', 'id');
    }

    public function billingInformation()
    {
        return $this->belongsTo(BillingInformation::class);
    }
}
