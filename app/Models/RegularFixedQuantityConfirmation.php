<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RegularFixedQuantityConfirmation extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'regular_fixed_quantity_confirmation';
    public $fillable = [
        "id",
        "id_regular_delivery_plan",
        "datasource",
        "code_consignee",
        "model",
        "item_no",
        "item_serial",
        "disburse",
        "delivery",
        "qty",
        "order_no",
        "cust_item_no",
        "etd_ypmi",
        "etd_wh",
        "etd_jkt",
        "is_actual",
        "id_fixed_actual_container",
        "id_fixed_actual_container_creation",
        "status",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
        "deleted_at"
    ];

    public function refRegularDeliveryPlan()
    {
        return $this->belongsTo(RegularDeliveryPlan::class,'id_regular_delivery_plan','id');
    }
}
