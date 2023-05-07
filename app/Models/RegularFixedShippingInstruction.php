<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RegularFixedShippingInstruction extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'regular_fixed_shipping_instruction';
    public $fillable = [
        "no_booking",
        "booking_date",
        "datasource",
        "status",
        "etd_jkt",
        "etd_ypmi",
        "etd_wh",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
        "deleted_at"
    ];
}
