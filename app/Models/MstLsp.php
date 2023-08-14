<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MstLsp extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'mst_lsp';
    public $fillable = [
        'id',
        'name',
        'code_consignee',
        'id_type_delivery',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by',
        'deleted_at'
    ];

    public function refTypeDelivery()
    {
        return $this->belongsTo(MstTypeDelivery::class,'id_type_delivery','id');
    }
}
