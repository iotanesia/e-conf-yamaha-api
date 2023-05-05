<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MstConsumable extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'mst_consumable';
    public $fillable = [
        'id',
        'name',
        'price',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by',
        'deleted_at'
    ];
}
