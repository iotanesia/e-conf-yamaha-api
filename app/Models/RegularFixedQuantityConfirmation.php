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
        "in_dc",
        "in_wh",
        "production",
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

    public function refFixedActualContainer()
    {
        return $this->belongsTo(RegularFixedActualContainer::class,'id_fixed_actual_container','id');
    }

    public function refFixedActualContainerCreation()
    {
        return $this->belongsTo(RegularFixedActualContainerCreation::class,'id_fixed_actual_container_creation','id');
    }

    public function refConsignee()
    {
        return $this->belongsTo(MstConsignee::class,'code_consignee','code');
    }

    public function manyFixedQuantityConfirmationBox()
    {
        return $this->hasMany(RegularFixedQuantityConfirmationBox::class,'id_fixed_quantity_confirmation','id')->orderBy('id','asc');
    }
}
