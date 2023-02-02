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

    public function refPermission()
    {
        return $this->belongsTo(Permission::class,'id_roles','id_role');
    }

    public function manyPermission()
    {
        return $this->hasMany(Permission::class,'id_role','id_roles');
    }

    public function refRole()
    {
        return $this->belongsTo(Role::class,'id_roles','id_role');
    }
}
