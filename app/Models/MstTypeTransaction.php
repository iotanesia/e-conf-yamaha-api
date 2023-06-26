<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MstTypeTransaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'mst_type_transaction';
    public $fillable = [
        'id',
        'name',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by',
        'deleted_at'
    ];
}
