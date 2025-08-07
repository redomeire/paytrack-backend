<?php

namespace App\Models;

use App\Models\bills;
use App\Models\notification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class notification extends Model
{
    /** @use HasFactory<\Database\Factories\NotificationFactory> */
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'bill_id',
        'notification_type_id',
        'title',
        'message',
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

    public function notificationType()
    {
        return $this->belongsTo(notification_type::class, 'notification_type_id', 'id');
    }

    public function notificationReads()
    {
        return $this->hasMany(notification_read::class);
    }
}
