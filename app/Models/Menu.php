<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model {
    protected $table = 'mst_menu';

    protected $fillable = [
        'name',
        'category',
        'icon',
        'url',
        'tag_variant',
        'parent',
        'other',
	    'created_by',
	    'created_at',
	    'updated_by',
	    'updated_at',
	    'deleted_at',
        'mst_part'
    ];

    public function manyChild()
    {
        return $this->hasMany(Menu::class,'parent','id');
    }
}
