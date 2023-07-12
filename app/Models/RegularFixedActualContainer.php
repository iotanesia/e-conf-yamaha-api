<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RegularFixedActualContainer extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'regular_fixed_actual_container';
    public $fillable = [
        "id",
        "code_consignee",
        "no_packaging",
        "datasource",
        "etd_ypmi",
        "etd_wh",
        "etd_jkt",
        "is_actual",
        "id_mot",
        "id_type_delivery",
        "is_prospect",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
        "deleted_at"
    ];

    public function refConsignee()
    {
        return $this->belongsTo(MstConsignee::class,'code_consignee','code');
    }

    public function manyFixedPackingCreation()
    {
        return $this->hasMany(RegularFixedPackingCreation::class,'id_fixed_actual_container','id')->orderBy('id','asc');
    }

    public function manyFixedQuantityConfirmation()
    {
        return $this->hasMany(RegularFixedQuantityConfirmation::class,'id_fixed_actual_container','id')->orderBy('id','asc');
    }

    public function manyFixedActualContainerCreation()
    {
        return $this->hasMany(RegularFixedActualContainerCreation::class,'id_fixed_actual_container','id')->orderBy('id','asc');
    }

    public function refPartOfDischarge()
    {
        return $this->belongsTo(MstPortOfDischarge::class,'code_consignee','code_consignee');
    }

    public function refMot()
    {
        return $this->belongsTo(MstMot::class,'id_mot','id');
    }
}
