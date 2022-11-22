<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Position extends Model {
    protected $table = 'mst_position';

    protected $fillable = [
        'id_role',
        'name',
        'nickname',
	    'created_by',
	    'created_at',
	    'updated_by',
	    'updated_at',
	    'deleted_at'
    ];
}
