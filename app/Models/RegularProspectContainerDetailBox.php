<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
class RegularProspectContainerDetailBox extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'regular_prospect_container_detail_box';
    public $fillable = [
        "id",
        "id_box",
        "id_prospect_container_detail",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
        "deleted_at",
        "uuid"
    ];

    public static function boot()
    {
        parent::boot();
        static::creating(function ($model){
            // $model->uuid = (string) Str::uuid();
        });
    }
}
