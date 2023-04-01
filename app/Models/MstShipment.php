<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MstShipment extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'mst_shipment';
    public $fillable = [
        'id',
        'code',
        'shipment',
        'is_active',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by',
        'deleted_at'
    ];
}
