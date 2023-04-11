<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegularStokConfirmation extends Model
{
    use HasFactory;
    protected $table = 'regular_stock_confirmation';
    public $fillable = [
        "id",
        "id_regular_delivery_plan",
        "count_box",
        "in_dc",
        "in_wh",
        "status",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
        "deleted_at",
        "status_instock",
        "status_outstock"
    ];

    public function refRegularDeliveryPlan()
    {
        return $this->belongsTo(RegularDeliveryPlan::class,'id_regular_delivery_plan','id');
    }

    public function manyDeliveryPlanBox()
    {
        return $this->hasMany(RegularDeliveryPlanBox::class,'id_regular_delivery_plan','id_regular_delivery_plan')->orderBy('id','asc');
    }
}
