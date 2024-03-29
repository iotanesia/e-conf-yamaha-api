<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class RegularDeliveryPlanBox extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'regular_delivery_plan_box';
    public $fillable = [
        "id",
        "id_regular_delivery_plan",
        "id_box",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
        "deleted_at"
    ];

    public function refBox()
    {
        return $this->belongsTo(MstBox::class,'id_box','id');
    }

    public function refRegularDeliveryPlan()
    {
        return $this->belongsTo(RegularDeliveryPlan::class,'id_regular_delivery_plan','id');
    }
}
