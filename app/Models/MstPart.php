<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MstPart extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'mst_part';
    public $fillable = [
        'id',
        'item_no',
        'description',
        'id_consignee',
        'hs_code',
        'id_group_product',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by',
        'deleted_at'
    ];

    public function refConsignee()
    {
        return $this->belongsTo(MstConsignee::class,'id_consignee','id');
    }

    public function refGrooupProduct()
    {
        return $this->belongsTo(MstGroupProduct::class,'id_group_product','id');
    }
}
