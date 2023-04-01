<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RegularProspectContainerFifo extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'regular_prospect_container_fifo';
    public $fillable = [
        "id",
        "id_prospect_container",
        "id_type_delivery",
        "id_mot",
        "id_lsp",
        "id_container",
        "summary_box",
        "measurement",
        "item_no",
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
