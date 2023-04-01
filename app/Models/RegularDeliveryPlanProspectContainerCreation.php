<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RegularDeliveryPlanProspectContainerCreation extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'regular_delivery_plan_prospect_container_creation';
    public $fillable = [
        "id",
        "id_type_delivery",
        "id_prospect_container",
        "id_mot",
        "id_mot",
        "id_container",
        "item_no",
        "code_consignee",
        "no_packaging",
        "measurement",
        "summary_box",
        "datasource",
        "etd_ypmi",
        "etd_wh",
        "etd_jkt",
        "status",
        "id_booking",
        "id_shipping_instruction",
        "id_shipping_instruction_creation",
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
            // $model->uuid = (string) Str::uuid();
        });
    }
}
