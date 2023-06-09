<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MstConsignee extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'mst_consignee';
    public $fillable = [
        'id',
        'code',
        'category',
        'nick_name',
        'name',
        'address1',
        'address2',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by',
        'deleted_at'
    ];

    public function manyPortOfDischarge()
    {
        return $this->hasMany(MstPortOfDischarge::class,'code_consignee','code');
    }

    public function manyLsp()
    {
        return $this->hasMany(MstLsp::class,'code_consignee','code');
    }

}
