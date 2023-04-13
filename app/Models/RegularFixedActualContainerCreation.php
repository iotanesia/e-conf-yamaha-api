<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RegularFixedActualContainerCreation extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'regular_fixed_actual_container_creation';
    public $fillable = [
        "id",
        "id_fixed_packing_container",
        "id_fixed_shipping_instruction",
        "id_fixed_shipping_instruction_creation",
        "id_type_delivery",
        "id_mot",
        "id_lsp",
        "id_container",
        "code_consignee",
        "etd_ypmi",
        "etd_wh",
        "etd_jkt",
        "summary_box",
        "measurement",
        "status",
        "is_booking",
        "datasource",
        "item_no",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
        "deleted_at"
    ];

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

    public function refMstContainer()
    {
        return $this->belongsTo(MstContainer::class,'id_container','id');
    }

    public function refConsignee()
    {
        return $this->belongsTo(MstConsignee::class,'code_consignee','code');
    }
}
