<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class IregularOrderEntryCheckbox extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'iregular_order_entry_checkbox';
    public $fillable = [
        'id',
        'id_iregular_order_entry',
        'type',
        'table',
        'id_value',
        'value',
        'created_by',
	    'created_at',
	    'updated_by',
	    'updated_at',
	    'deleted_at'
    ];
}
