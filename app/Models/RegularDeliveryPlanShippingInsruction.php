<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RegularDeliveryPlanShippingInsruction extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'regular_delivery_plan_shipping_instruction';
    public $fillable = [
        "id",
        "no_booking",
        "booking_date",
        "datasource",
        "status",
        "datasource",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
        "deleted_at"
    ];

    public static function boot()
    {
        parent::boot();
        static::creating(function ($model){
            // $model->uuid = (string) Str::uuid();
        });
    }
}
