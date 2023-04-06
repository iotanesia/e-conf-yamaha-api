<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegularStokConfirmation extends Model
{
    use HasFactory;
    protected $table = 'regular_stock_confirmation';
    public $fillable = [
        "id",
        "id_regular_delivery_plan",
        "count_box",
        "in_dc",
        "in_wh",
        "status",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
        "deleted_at"
    ];
}
