<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RegularFixedPackingCreationNote extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'regular_fixed_packing_creation_note';
    public $fillable = [
        "id",
        "id_fixed_packing_creation",
        "id_fixed_actual_conatiner",
        "shipper",
        "yth",
        "consignee",
        "no_letters",
        "delivery_date",
        "truck_type",
        "truck_no",
        "shippment",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
        "deleted_at"
    ];

    public function manyRegularFixedPackingCreationNoteDetail()
    {
        return $this->hasMany(RegularFixedPackingCreationNoteDetail::class,'id_fixed_packing_creation_note','id');
    }

    public function refConsignee()
    {
        return $this->belongsTo(MstConsignee::class,'consignee','code');
    }
}
