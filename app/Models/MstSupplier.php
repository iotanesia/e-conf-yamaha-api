<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MstSupplier extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'mst_supplier';
    public $fillable = [
        'id',
        'code',
        'supplier',
        'is_active',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by',
        'deleted_at'
    ];
}
