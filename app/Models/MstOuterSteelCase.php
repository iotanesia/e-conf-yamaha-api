<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MstOuterSteelCase extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'mst_outer_steel_case';
    public $fillable = [
        'id',
        'length',
        'width',
        'height',
        'type_outer',
        'outer_weight',
        'measurement',
        'name',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by',
        'deleted_at'
    ];
}
