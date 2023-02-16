<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RegularOrderEntryUpload extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'reguler_order_entry_upload';
    public $fillable = [
        'id',
        'uuid',
        'id_regular_order_entry',
        'filename',
        'filepath',
        'upload_date',
        'iteration',
        'status',
        'created_by',
	    'created_at',
	    'updated_by',
	    'updated_at',
	    'deleted_at'
    ];
}
