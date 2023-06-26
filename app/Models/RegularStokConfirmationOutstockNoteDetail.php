<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RegularStokConfirmationOutstockNoteDetail extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'regular_stock_confirmation_outstock_note_detail';
    public $fillable = [
        "id",
        "item_no",
        "order_no",
        "qty",
        "no_packing",
        "id_stock_confirmation",
        "id_stock_confirmation_outstock_note",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
        "deleted_at"
    ];

    public function refMstPart()
    {
        return $this->belongsTo(MstPart::class, 'item_no', 'item_no');
    }
}
