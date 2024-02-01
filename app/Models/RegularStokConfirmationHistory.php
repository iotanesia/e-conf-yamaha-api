<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegularStokConfirmationHistory extends Model
{
    use HasFactory;
    protected $table = 'regular_stock_confirmation_history';
    public $fillable = [
        "id",
        "id_regular_delivery_plan",
        "id_regular_delivery_plan_box",
        "id_stock_confirmation",
        "id_box",
        "type",
        "qty_pcs_perbox",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
        "deleted_at"
    ];
    
    public function refRegularDeliveryPlan()
    {
        return $this->belongsTo(RegularDeliveryPlan::class,'id_regular_delivery_plan','id');
    }
}
