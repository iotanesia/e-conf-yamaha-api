<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class IregularDeliveryPlanPacking extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'iregular_delivery_plan_packing';
    public $fillable = [
        'id',
        'id_iregular_delivery_plan',
        'to',
        'date',
        'shipped_to',
        'attn',
        'city',
        'phone_no',
        'shipped_by',
        'fax',
        'description_packing',
        'mark_number',
        'created_by',
	    'created_at',
	    'updated_by',
	    'updated_at',
	    'deleted_at'
    ];

    public function refDeliveryPlan(){
        return $this->belongsTo(IregularDeliveryPlan::class,'id_iregular_delivery_plan','id');
    }
}
