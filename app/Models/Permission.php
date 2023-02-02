<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model {
    protected $table = 'permissions';

    protected $fillable = [
        'id_menu',
        'id_role',
	    'created_by',
	    'created_at',
	    'updated_by',
	    'updated_at',
	    'deleted_at'
    ];

    public function refMenu()
    {
        return $this->belongsTo(Menu::class,'id_menu','id');
    }

}
