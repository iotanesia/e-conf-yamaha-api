<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MstDatasource extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'mst_datasource';
    protected $primary_key = "nama";
    public $incrementing = false;

    public $fillable = [
        'nama',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by',
        'deleted_at'
    ];
}
