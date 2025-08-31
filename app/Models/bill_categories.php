<?php

namespace App\Models;

use App\Models\bills;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class bill_categories extends Model
{
    /** @use HasFactory<\Database\Factories\BillCategoriesFactory> */
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'description',
        'color',
        'icon',
        'is_default',
        'user_id',
    ];

    protected $hidden = [
        'user_id',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->{$model->getKeyName()} = (string) Str::uuid();
        });
    }

    public function bills()
    {
        return $this->hasMany(bills::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
