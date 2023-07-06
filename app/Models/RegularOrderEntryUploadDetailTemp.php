<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RegularOrderEntryUploadDetailTemp extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'regular_order_entry_upload_detail_temp';
    public $fillable = [
        "id",
        "id_regular_order_entry_upload",
        "code_consignee",
        "model",
        "item_no",
        "disburse",
        "delivery",
        "qty",
        "status",
        "etd_jkt",
        "etd_ypmi",
        "etd_wh",
        "order_no",
        "cust_item_no",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
        "deleted_at",
        "uuid",
    ];
}
