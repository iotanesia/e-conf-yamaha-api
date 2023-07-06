<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RegularOrderEntryUploadDetail extends Model
{
    use HasFactory;
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
        "jenis",
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

    public function refMstPart()
    {
        return $this->belongsTo(MstPart::class, 'item_no', 'item_no');
    }

    public function refConsignee()
    {
        return $this->belongsTo(MstConsignee::class,'code_consignee','code');
    }
}
