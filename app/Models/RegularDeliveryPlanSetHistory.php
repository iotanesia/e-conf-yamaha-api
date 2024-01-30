<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RegularDeliveryPlanSetHistory extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'regular_delivery_plan_set_history';
    public $fillable = [
        "id",
        "id_delivery_plan",
        "item_no",
        "id_delivery_plan_box",
        "qty",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
        "deleted_at"
    ];

    public function refBox(){
        return $this->belongsTo(MstBox::class,'item_no','item_no');
    }
    
    public function refPart()
    {
        return $this->belongsTo(MstPart::class,'item_no','item_no');
    }
}
