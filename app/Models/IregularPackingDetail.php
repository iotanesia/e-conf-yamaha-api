<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class IregularPackingDetail extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'iregular_packing_detail';
    public $fillable = [
        'id',
        'id_iregular_packing',
        'item_number',
        'item_name',
        'po_no',
        'qty',
        'invoice_no',
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
