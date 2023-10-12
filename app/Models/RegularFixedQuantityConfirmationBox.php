<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RegularFixedQuantityConfirmationBox extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'regular_fixed_quantity_confirmation_box';
    public $fillable = [
        "id",
        "id_fixed_quantity_confirmation",
        "id_regular_delivery_plan",
        "id_regular_delivery_plan_box",
        "id_prospect_container_creation",
        "id_box",
        "id_proc",
        "qty_pcs_box",
        "lot_packing",
        "packing_date",
        "qrcode",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
        "deleted_at",
        "is_labeling"
    ];

    public function refRegularDeliveryPlan()
    {
        return $this->belongsTo(RegularDeliveryPlan::class,'id_regular_delivery_plan','id');
    }

    public function refFixedQuantityConfirmation()
    {
        return $this->belongsTo(RegularFixedQuantityConfirmation::class,'id_fixed_quantity_confirmation','id');
    }

    public function refMstBox()
    {
        return $this->belongsTo(MstBox::class,'id_box','id');
    }
}
