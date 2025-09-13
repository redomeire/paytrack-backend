<?php

namespace App\Models;

use App\Models\User;
use App\Models\bills;
use App\Models\bill_series;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BillingInformation extends Model
{
    /** @use HasFactory<\Database\Factories\BillingInformationFactory> */
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'bill_id',
        'name',
        'type',
        'details',
        'default'
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

    public function bills()
    {
        return $this->hasMany(bills::class);
    }

    public function billSeries()
    {
        return $this->hasMany(bill_series::class);
    }
}
