<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RegularDeliveryPlanProspectContainer extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'regular_delivery_plan_prospect_container';
    public $fillable = [
        "id",
        "code_consignee",
        "no_packaging",
        "measurement",
        "summary_box",
        "datasource",
        "etd_ypmi",
        "etd_wh",
        "etd_jkt",
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
