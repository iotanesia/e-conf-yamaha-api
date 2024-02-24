<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class IregularDeliveryPlan extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'iregular_delivery_plan';
    public $fillable = [
        'id',
        'id_iregular_order_entry',
        'requestor',
        'ext',
        'cost_center',
        'section',
        'id_type_transaction',
        'id_good_payment',
        'id_doc_type',
        'reason_foc',
        'id_freight_charge',
        'id_insurance',
        'id_duty_tax',
        'id_inland_cost',
        'id_shipped',
        'pick_up_location',
        'name_consignee',
        'company_consignee',
        'email_consignee',
        'phone_consignee',
        'fax_consignee',
        'address_consignee',
        'description_goods',
        'invoice_no',
        'entity_site',
        'rate',
        'receive_date',
        'delivery_date',
        'etd_date',
        'stuffing_date',
        'id_freight',
        'id_good_criteria',
        'created_by',
	    'created_at',
	    'updated_by',
	    'updated_at',
	    'deleted_at'
    ];

    public function manyDeliveryPlanCheckbox()
    {
        return $this->hasMany(IregularDeliveryPlanCheckbox::class,'id_iregular_delivery_plan','id')->orderBy('id','asc');
    }

    public function manyDeliveryPlanDoc()
    {
        return $this->hasMany(IregularDeliveryPlanDoc::class,'id_iregular_delivery_plan','id')->orderBy('id','asc');
    }
    
    public function manyDeliveryPlanPart()
    {
        return $this->hasMany(IregularDeliveryPlanPart::class,'id_iregular_delivery_plan','id')->orderBy('id','asc');
    }

    public function manyTracking()
    {
        return $this->hasMany(IregularOrderEntryTracking::class,'id_iregular_order_entry','id_iregular_order_entry')->orderBy('id','desc');
    }

    public function refTypeTransaction(){
        return $this->belongsTo(MstTypeTransaction::class,'id_type_transaction','id');
    }
}
