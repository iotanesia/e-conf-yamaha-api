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
        'email',
        'date',
        'bill_to',
        'invoice_no',
        'shipped_to',
        'attn',
        'city',
        'phone_no',
        'shipped_by',
        'fax',
        'description_invoice',
        'type_package',
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
