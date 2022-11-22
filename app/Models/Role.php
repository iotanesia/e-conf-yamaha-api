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

    public function manyPermission()
    {
        return $this->hasMany(Permission::class,'id','id_role');
    }

    public function refPermission()
    {
        return $this->belongsTo(Permission::class,'id_role','id');
    }

    public function refPosition()
    {
        return $this->belongsTo(Position::class,'id_position','id');
    }
}
