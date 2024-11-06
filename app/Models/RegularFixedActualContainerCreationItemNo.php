<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RegularFixedActualContainerCreationItemNo extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'regular_fixed_actual_container_creation_item_no';
    public $fillable = [
        "id_regular_fixed_actual_container_creation",
        "item_no",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
        "deleted_at"
    ];
}
