<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class IregularDeliveryPlanPart extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $table = 'iregular_delivery_plan_part';
    public $fillable = [
        "id",
        "id_iregular_delivery_plan",
        "item_code",
        "item_name",
        "order_no",
        "qty",
        "price",
        "net_weight",
        "gross_weight",
        "measurement",
        "summary_box",
        "created_by",
	    "created_at",
	    "updated_by",
	    "updated_at",
	    "deleted_at"
    ];
}
