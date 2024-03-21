<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class IregularDeliveryPlanPackingDetail extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'iregular_delivery_plan_packing_detail';
    public $fillable = [
        'id',
        'id_iregular_delivery_plan_packing',
        'description',
        'qty',
        'nett_weight',
        'gross_weight',
        'length',
        'width',
        'height',
        'created_by',
	    'created_at',
	    'updated_by',
	    'updated_at',
	    'deleted_at'
    ];

    public function refDeliveryPlanPacking(){
        return $this->belongsTo(IregularDeliveryPlanPacking::class,'id_iregular_delivery_plan_packing','id');
    }
}
