<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MstBox extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'mst_box';
    public $fillable = [
        'id',
        'no_box',
        'id_part',
        'qty',
        'unit_weight_gr',
        'unit_weight_kg',
        'outer_carton_weight',
        'total_gross_weight',
        'length',
        'width',
        'height',
        'ratio',
        'fork_length',
        'row_qty',
        'box_in_cont',
        'qty_in_cont',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by',
        'deleted_at'
    ];

    public function refPart(){
        return $this->belongsTo(MstPart::class,'id_part','id');
    }

}
