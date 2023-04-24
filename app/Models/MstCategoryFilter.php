<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MstCategoryFilter extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'mst_category_filter';
    public $fillable = [
        'id',
        'module',
        'label',
        'value',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by',
        'deleted_at'
    ];
}
