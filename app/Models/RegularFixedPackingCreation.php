<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RegularFixedPackingCreation extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'regular_fixed_packing_creation';
    public $fillable = [
        "id",
        "id_fixed_actual_container",
        "invoice",
        "date",
        "container",
        "no_seal",
        "etd_jkt",
        "etd_manaus",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
        "deleted_at"
    ];

    public function refRegularFixedActualContainer()
    {
        return $this->belongsTo(RegularFixedActualContainer::class,'id_fixed_actual_container','id');
    }
}
