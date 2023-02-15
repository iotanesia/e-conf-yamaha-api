<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RegularOrderEntryUploadDetail extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'reguler_order_entry_upload_detail';
}
