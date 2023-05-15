<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class IregularOrderEntryDoc extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'iregular_order_entry_doc';
    public $fillable = [
        'id',
        'id_iregular_order_entry',
        'id_doc',
        'path',
        'extension',
        'filename',
        'is_completed',
        'created_by',
	    'created_at',
	    'updated_by',
	    'updated_at',
	    'deleted_at'
    ];

    public function MstDoc(){
        return $this->belongsTo(MstDoc::class,'id_doc','id');
    }
}
