<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MstTypeDelivery extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'mst_type_delivery';
    public $fillable = [
        'id',
        'id_mot',
        'name',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by',
        'deleted_at'
    ];

    public function refMot(){
        return $this->belongsTo(MstMot::class,'id_mot','id');
    }
}