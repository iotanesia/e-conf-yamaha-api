<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VRegularOrderEntryUploadDetail extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'v_regular_order_entry_upload_detail';

}
