<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RegularOrderEntryUploadDetailBox extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'regular_order_entry_upload_detail_box';
    public $fillable = [
        "id",
        "uuid_regular_order_entry_upload_detail",
        "id_box",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
        "deleted_at",
        "uuid"
    ];

    public function refBox(){
        return $this->belongsTo(MstBox::class,'id_box','id');
    }

}
