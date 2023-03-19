<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RegularOrderEntryUpload extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'regular_order_entry_upload';
    protected $primaryKey = 'id';
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

    public function refRegularOrderEntry()
    {
        return $this->belongsTo(RegularOrderEntry::class,'id_regular_order_entry','id');
    }

    public function manyDetail()
    {
        return $this->hasMany(RegularOrderEntryUploadDetail::class,'id_regular_order_entry_upload','id');
    }

    public static function boot() {
        parent::boot();

        static::deleting(function($item) { // before delete() method call this
             foreach ($item->manyDetail as $key => $box) {
                $box->manyDetailBox()->forceDelete();
             }
             $item->manyDetail()->forceDelete();
             // do the rest of the cleanup...
        });
    }


}
