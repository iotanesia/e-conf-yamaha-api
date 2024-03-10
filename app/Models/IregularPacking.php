<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class IregularPacking extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'iregular_packing';
    public $fillable = [
        'id',
        'id_iregular_delivery_plan',
        'truck_no',
        'jenis_truck',
        'ref_invoice_no',
        'delivery_date',
        'yth',
        'username',
        'status',
        'created_by',
	    'created_at',
	    'updated_by',
	    'updated_at',
	    'deleted_at'
    ];

    public function refDeliveryPlan(){
        return $this->belongsTo(IregularDeliveryPlan::class,'id_iregular_delivery_plan','id');
    }

    public function manyDetail()
    {
        return $this->hasMany(IregularPackingDetail::class,'id_iregular_packing','id');
    }
}
