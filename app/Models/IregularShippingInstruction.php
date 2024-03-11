<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class IregularShippingInstruction extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'iregular_shipping_instruction';
    public $fillable = [
        'id',
        'id_iregular_delivery_plan',
        'status',
        'created_by',
	    'created_at',
	    'updated_by',
	    'updated_at',
	    'deleted_at'
    ];

    public function refDeliveryPlan()
    {
        return $this->belongsTo(IregularDeliveryPlan::class,'id_iregular_delivery_plan','id');
    }
}
