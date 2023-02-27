<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class RegularDeliveryPlan extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'regular_delivery_plan';
    public $fillable = [
        "id",
        "id_regular_order_entry",
        "code_consignee",
        "model",
        "item_no",
        "disburse",
        "delivery",
        "qty",
        "status",
        "order_no",
        "cust_item_no",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
        "deleted_at",
        "uuid"
    ];

    public static function boot()
    {
        parent::boot();
        static::creating(function ($model){
            $model->uuid = (string) Str::uuid();
        });
    }

    public function refRegularOrderEntry()
    {
        return $this->belongsTo(RegularOrderEntry::class,'id_regular_order_entry','id');
    }

    public function manyDeliveryPlanBox()
    {
        return $this->hasMany(RegularDeliveryPlanBox::class,'id_regular_delivery_plan','id')->orderBy('id','asc');
    }
}
