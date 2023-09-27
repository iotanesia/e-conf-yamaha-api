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
        'customer_use',
        'id_group_product',
        'item_serial',
        'code_consignee',
        'cost_center',
        'coa',
        'gl_account',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by',
        'deleted_at'
    ];

    public function refConsignee()
    {
        return $this->belongsTo(MstConsignee::class,'code_consignee','code');
    }

    public function refGrooupProduct()
    {
        return $this->belongsTo(MstGroupProduct::class,'id_group_product','id');
    }
}
