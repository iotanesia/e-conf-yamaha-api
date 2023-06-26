<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MstGroupProduct extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'mst_group_product';
    public $fillable = [
        'id',
        'group_product',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by',
        'deleted_at'
    ];
}
