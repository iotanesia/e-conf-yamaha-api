<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegularStokConfirmationTemp extends Model
{
    use HasFactory;
    protected $table = 'regular_stock_confirmation_temp';
    public $fillable = [
        "id",
        "id_regular_delivery_plan",
        "id_stock_confirmation",
        "count_box",
        "in_dc",
        "in_wh",
        "status",
        "etd_ypmi",
        "etd_wh",
        "etd_jkt",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
        "deleted_at",
        "status_instock",
        "status_outstock",
        "code_consignee",
        "datasource",
        "production",
        "is_actual",
        "qty",
        "qr_key",
    ];
}
