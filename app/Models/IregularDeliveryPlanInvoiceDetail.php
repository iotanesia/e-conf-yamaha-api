<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class IregularDeliveryPlanInvoiceDetail extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'iregular_delivery_plan_invoice_detail';
    public $fillable = [
        'id',
        'id_iregular_delivery_plan_invoice',
        'order_no',
        'hs_code',
        'no_package',
        'description',
        'qty',
        'unit_price',
        'amount',
        'created_by',
	    'created_at',
	    'updated_by',
	    'updated_at',
	    'deleted_at'
    ];

    public function refDeliveryPlanInvoice(){
        return $this->belongsTo(IregularDeliveryPlanInvoice::class,'id_iregular_delivery_plan_invoice','id');
    }
}
