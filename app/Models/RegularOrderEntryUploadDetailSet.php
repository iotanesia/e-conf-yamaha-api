<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RegularOrderEntryUploadDetailSet extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'regular_order_entry_upload_detail_set';
    public $fillable = [
        "id",
        "id_detail",
        "item_no",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
        "deleted_at",
    ];
}
