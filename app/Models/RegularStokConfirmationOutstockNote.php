<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegularStokConfirmationOutstockNote extends Model
{
    use HasFactory;
    protected $table = 'regular_stock_confirmation_outstock_note';
    protected $casts = [
        'id_stock_confirmation' => 'json',
    ];
    public $fillable = [
        "id",
        "shipper",
        "yth",
        "consignee",
        "no_letters",
        "delivery_date",
        "truck_type",
        "truck_no",
        "id_stock_confirmation",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
        "deleted_at"
    ];
   
    public function manyRegularStockConfirmationOutstockNoteDetail()
    {
        return $this->hasMany(RegularStokConfirmationOutstockNoteDetail::class,'id_stock_confirmation_outstock_note','id');
    }
}
