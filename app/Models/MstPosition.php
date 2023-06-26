<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MstPosition extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'mst_position';
    public $fillable = [
        'id',
        'name',
        'nickname',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by',
        'deleted_at'
    ];
}
