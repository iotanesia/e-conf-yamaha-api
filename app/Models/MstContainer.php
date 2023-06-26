<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MstContainer extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'mst_container';
    public $fillable = [
        'id',
        'container_type',
        'capacity',
        'long',
        'wide',
        'height',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by',
        'deleted_at'
    ];
}
