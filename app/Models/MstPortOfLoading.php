<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MstPortOfLoading extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'mst_port_of_loading';
    public $fillable = [
        'id',
        'name',
        'id_type_delivery',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by',
        'deleted_at'
    ];
}
