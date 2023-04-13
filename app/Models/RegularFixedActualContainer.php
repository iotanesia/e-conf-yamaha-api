<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RegularFixedActualContainer extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'regular_fixed_actual_container';
    public $fillable = [
        "id",
        "code_consignee",
        "no_packaging",
        "datasource",
        "etd_ypmi",
        "etd_wh",
        "etd_jkt",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
        "deleted_at"
    ];

    public function refConsignee()
    {
        return $this->belongsTo(MstConsignee::class,'code_consignee','code');
    }
}
