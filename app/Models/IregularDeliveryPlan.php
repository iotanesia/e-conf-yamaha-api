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
        'is_files_downloaded',
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
