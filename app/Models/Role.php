<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model {
    protected $table = 'roles';

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
