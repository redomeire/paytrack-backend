<?php

namespace App\Models;

use App\Models\BillingInformation;
use App\Models\bill_categories;
use App\Models\bill_series;
use App\Models\payments;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;

class bills extends Model
{
    /** @use HasFactory<\Database\Factories\BillsFactory> */
    use HasFactory;
    use Searchable;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'bill_category_id',
        'bill_series_id',
        'name',
        'description',
        'amount',
        'currency',
        'billing_type',
        'frequency',
        'custom_frequency_days',
        'due_date',
        'notes',
        'attachment_url',
        'billing_information_id',
        'account_number',
        'account_name',
        'bank_code',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->{$model->getKeyName()} = (string) Str::uuid();
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function billSeries()
    {
        return $this->belongsTo(bill_series::class, 'bill_series_id', 'id');
    }

    public function billCategory()
    {
        return $this->belongsTo(bill_categories::class, 'bill_category_id', 'id');
    }

    public function payment()
    {
        return $this->hasOne(payments::class);
    }

    public function billingInformation()
    {
        return $this->belongsTo(BillingInformation::class);
    }

    public function searchableAs()
    {
        return 'bills_index';
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->getKey(),
            'name' => $this->name,
        ];
    }
}
