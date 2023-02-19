<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MstPortOfDischarge extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'mst_port_of_discharge';
    public $fillable = [
        'id',
        'id_consignee',
        'mot',
        'tipe',
        'id_port',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by',
        'deleted_at'
    ];

    public function refConsignee()
    {
        return $this->belongsTo(MstConsignee::class,'id_consignee','id');
    }

    public function refPort()
    {
        return $this->belongsTo(MstPort::class,'id_port','id');
    }
}
