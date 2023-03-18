<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RegularOrderEntryUploadRevision extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'regular_order_entry_upload';
    public $fillable = [
        'id',
        'id_regular_order_entry_upload',
        'id_user',
        'note',
        'created_by',
	    'created_at',
	    'updated_by',
	    'updated_at',
	    'deleted_at'
    ];

    public function refUser()
    {
        return $this->belongsTo(User::class,'id_user','id');
    }



    public static function boot() {
        parent::boot();


    }


}
