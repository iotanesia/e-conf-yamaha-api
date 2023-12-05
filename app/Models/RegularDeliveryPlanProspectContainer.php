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
        "uuid",
        "is_prospect",
        "id_mot",
        "id_type_delivery",
    ];

    public static function boot()
    {
        parent::boot();
        static::creating(function ($model){
            // $model->uuid = (string) Str::uuid();
        });
    }

    public function refConsignee()
    {
        return $this->belongsTo(MstConsignee::class,'code_consignee','code');
    }

    public function refMot()
    {
        return $this->belongsTo(MstMot::class,'id_mot','id');
    }

    public function manyRegularDeliveryPlan()
    {
        return $this->hasMany(RegularDeliveryPlan::class,'id_prospect_container','id')->orderBy('id','asc');
    }

    public function manyRegularDeliveryPlanProspectContainerCreation()
    {
        return $this->hasMany(RegularDeliveryPlanProspectContainerCreation::class,'id_prospect_container','id')->orderBy('id','asc');
    }
}
