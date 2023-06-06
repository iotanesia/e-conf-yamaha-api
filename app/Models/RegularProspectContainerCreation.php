<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegularProspectContainerCreation extends Model
{
    use HasFactory;
    protected $table = 'regular_delivery_plan_prospect_container_creation';
    public $fillable = [
        "id",
        "id_prospect_container",
        "id_type_delivery",
        "id_mot",
        "id_lsp",
        "id_container",
        "item_no",
        "code_consignee",
        "etd_ypmi",
        "etd_wh",
        "etd_jkt",
        "summary_box",
        "measurement",
        "status",
        "is_booking",
        "id_shipping_instruction",
        "id_shipping_instruction_creation",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
        "deleted_at",
        "datasource",
        "iteration",
        "is_completed"
    ];

    public function refRegularProspectContainer()
    {
        return $this->belongsTo(RegularProspectContainer::class,'id_prospect_container','id');
    }

    public function refMstTypeDelivery()
    {
        return $this->belongsTo(MstTypeDelivery::class,'id_type_delivery','id');
    }

    public function refMstMot()
    {
        return $this->belongsTo(MstMot::class,'id_mot','id');
    }

    public function refMstLsp()
    {
        return $this->belongsTo(MstLsp::class,'id_lsp','id');
    }

    public function refMstContainer()
    {
        return $this->belongsTo(MstContainer::class,'id_container','id');
    }

    public function refMstConsignee()
    {
        return $this->belongsTo(MstConsignee::class,'code_consignee','code');
    }
}
