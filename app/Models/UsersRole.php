<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsersRole extends Model {
    protected $table = 'users_roles';

    protected $fillable = [
        'id_users',
        'id_roles',
        'id_position',
	    'created_by',
	    'created_at',
	    'updated_by',
	    'updated_at',
	    'deleted_at'
    ];
}
