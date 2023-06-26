<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MstPort extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'mst_port';
    public $fillable = [
        'id',
        'name',
        'code',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by',
        'deleted_at'
    ];
}
