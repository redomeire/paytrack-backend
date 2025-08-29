<?php

namespace App\Models;

use App\Models\User;
use App\Models\bill_categories;
use Illuminate\Database\Eloquent\Model;

class MonthlyCategorySummary extends Model
{
    protected $fillable = [
        'user_id',
        'bill_category_id',
        'summary_year',
        'summary_month',
        'total_amount_spent',
        'bill_count',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function billCategory()
    {
        return $this->belongsTo(bill_categories::class);
    }
}
