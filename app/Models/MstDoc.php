<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MstDoc extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'mst_doc';
    public $fillable = [
        'id',
        'id_good_payment',
        'id_doc_type',
        'name',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by',
        'deleted_at'
    ];

    public function refGoodPayment()
    {
        return $this->belongsTo(MstGoodPayment::class,'id_good_payment','id');
    }

    public function refDocType()
    {
        return $this->belongsTo(MstDocType::class,'id_doc_type','id');
    }
}
