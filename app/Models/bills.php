<?php

namespace App\Models;

use App\Models\bill_categories;
use App\Models\payments;
use App\Models\User;
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
        'due_date',
        'notes',
        'attachment_url',
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
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function billCategory()
    {
        return $this->belongsTo(bill_categories::class, 'bill_category_id', 'id');
    }

    public function payments()
    {
        return $this->hasMany(payments::class);
    }
}
