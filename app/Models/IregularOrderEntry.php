<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class IregularOrderEntry extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'iregular_order_entry';
    public $fillable = [
        'id',
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
        'tracking',
        'id_freight',
        'id_good_criteria',
        'created_by',
	    'created_at',
	    'updated_by',
	    'updated_at',
	    'deleted_at'
    ];

    public function manyOrderEntryCheckbox()
    {
        return $this->hasMany(IregularOrderEntryCheckbox::class,'id_iregular_order_entry','id')->orderBy('id','asc');
    }
}
