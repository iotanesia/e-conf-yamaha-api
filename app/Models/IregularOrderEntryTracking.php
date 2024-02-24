<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class IregularOrderEntryTracking extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $table = 'iregular_order_entry_tracking';
    public $fillable = [
        "id",
        "id_iregular_order_entry",
        "id_user",
        "id_role",
        "status",
        "description",
        "created_by",
	    "created_at",
	    "updated_by",
	    "updated_at",
	    "deleted_at"
    ];
}
