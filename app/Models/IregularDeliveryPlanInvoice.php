<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class IregularDeliveryPlanInvoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'iregular_delivery_plan_invoice';
    public $fillable = [
        'id',
        'id_iregular_delivery_plan',
        'messrs',
        'date',
        'bill_to',
        'invoice_no',
        'shipped_to',
        'logistic_division',
        'city',
        'phone_no',
        'shipped_by',
        'fax',
        'from',
        'to',
        'trading_term',
        'payment',
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
