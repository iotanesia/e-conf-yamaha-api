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
        "id_lsp",
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
        "uuid",
        "status_bml",
        "iteration"
    ];

    public function refRegularDeliveryPlanPropspectContainer()
    {
        return $this->belongsTo(RegularDeliveryPlanProspectContainer::class,'id_prospect_container','id');
    }

    public function manyDeliveryPlan()
    {
        return $this->hasMany(RegularDeliveryPlan::class,'id_prospect_container_creation','id')->orderBy('id','asc');
    }

    public function refMstLsp()
    {
        return $this->belongsTo(MstLsp::class,'id_lsp','id');
    }

    public function refMstTypeDelivery()
    {
        return $this->belongsTo(MstTypeDelivery::class,'id_type_delivery','id');
    }

    public function refMstMot()
    {
        return $this->belongsTo(MstMot::class,'id_mot','id');
    }

    public function refMstConsignee()
    {
        return $this->belongsTo(MstConsignee::class,'code_consignee','code');
    }

    public function refMstContainer()
    {
        return $this->belongsTo(MstContainer::class,'id_container','id');
    }

    public static function boot()
    {
        parent::boot();
        static::creating(function ($model){
            // $model->uuid = (string) Str::uuid();
        });
    }
}
