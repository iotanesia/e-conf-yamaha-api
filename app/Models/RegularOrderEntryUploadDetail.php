<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RegularOrderEntryUploadDetail extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'regular_order_entry_upload_detail';
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
        "order_no",
        "cust_item_no",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
        "deleted_at",
        "uuid"
    ];

    public function refRegularOrderEntryUpload()
    {
        return $this->belongsTo(RegularOrderEntryUpload::class,'id_regular_order_entry_upload','id');
    }

    public function refRegularOrderEntryUploadDetailBox()
    {
        return $this->belongsTo(RegularOrderEntryUploadDetailBox::class,'uuid_regular_order_entry_upload_detail','uuid');
    }

    public function manyDetailBox()
    {
        return $this->hasMany(RegularOrderEntryUploadDetailBox::class,'uuid_regular_order_entry_upload_detail','uuid');
    }
}
