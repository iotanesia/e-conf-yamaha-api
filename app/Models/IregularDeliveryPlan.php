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
        'status_invoice_spv',
        'status_packing_list_spv',
        'status_casemark_spv',
        'status_excel_converter_spv',
        'status_csv_spv',
        'remark_invoice_spv',
        'remark_packing_list_spv',
        'remark_casemark_spv',
        'remark_excel_converter_spv',
        'status_invoice_mgr',
        'status_packing_list_mgr',
        'status_casemark_mgr',
        'status_excel_converter_mgr',
        'status_csv_mgr',
        'remark_invoice_mgr',
        'remark_packing_list_mgr',
        'remark_casemark_mgr',
        'remark_excel_converter_mgr',
        'remark_csv_mgr',
        'created_by',
	    'created_at',
	    'updated_by',
	    'updated_at',
	    'deleted_at'
    ];

    public function refOrderEntry(){
        return $this->belongsTo(IregularOrderEntry::class,'id_iregular_order_entry','id');
    }
}
