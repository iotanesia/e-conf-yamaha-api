<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class RegularOrderEntry extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'regular_order_entry';
    public $fillable = [
        'id',
        'uuid',
        'year',
        'month',
        'period',
        'uploaded',
        'status',
        'datasource',
        'created_by',
	    'created_at',
	    'updated_by',
	    'updated_at',
	    'deleted_at'
    ];

    public static function boot()
    {
        parent::boot();
        static::creating(function ($model){
            $model->uuid = (string) Str::uuid();
        });
    }
}
