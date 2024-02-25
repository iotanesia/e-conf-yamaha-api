<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class IregularDeliveryPlanShippingInstruction extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'iregular_delivery_plan_shipping_instruction';
    public $fillable = [
        'id',
        'id_iregular_delivery_plan',
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

    public function refDeliveryPlan()
    {
        return $this->belongsTo(IregularDeliveryPlan::class,'id_iregular_delivery_plan','id');
    }
    public function refTypeTransaction(){
        return $this->belongsTo(MstTypeTransaction::class,'id_type_transaction','id');
    }
    public function refGoodPayment(){
        return $this->belongsTo(MstGoodPayment::class,'id_good_payment','id');
    }
    public function refDocType(){
        return $this->belongsTo(MstDocType::class,'id_doc_type','id');
    }
    public function refFreightCharge(){
        return $this->belongsTo(MstFreightCharge::class,'id_freight_charge','id');
    }
    public function refInsurance(){
        return $this->belongsTo(MstInsurance::class,'id_insurance','id');
    }
    public function refDutyTax(){
        return $this->belongsTo(MstDutyTax::class,'id_duty_tax','id');
    }
    public function refInlandCost(){
        return $this->belongsTo(MstInlandCost::class,'id_inland_cost','id');
    }
    public function refShippedBy(){
        return $this->belongsTo(MstShippedBy::class,'id_shipped','id');
    }
    public function refFreight(){
        return $this->belongsTo(MstFreight::class,'id_freight','id');
    }
    public function refGoodCriteria(){
        return $this->belongsTo(MstGoodCriteria::class,'id_good_criteria','id');
    }
}
