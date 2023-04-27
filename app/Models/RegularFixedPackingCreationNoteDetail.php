<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RegularFixedPackingCreationNoteDetail extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'regular_fixed_packing_creation_note_detail';
    public $fillable = [
        "id",
        "id_fixed_packing_creation_note",
        "item_no",
        "order_no",
        "qty",
        "no_packing",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
        "deleted_at"
    ];
}
