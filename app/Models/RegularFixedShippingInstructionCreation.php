<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RegularFixedShippingInstructionCreation extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'regular_fixed_shipping_intruction_creation';
    public $fillable = [
        "id",
        "to",
        "cc",
        "attn_to",
        "attn_cc",
        "shipper",
        "instruction_date",
        "si_number",
        "invoice_no",
        "consignee",
        "notify_part",
        "information_date",
        "container",
        "via",
        "freight_charge",
        "do_no",
        "connecting_vessel",
        "eta_destination",
        "shipped_by",
        "pod",
        "pol",
        "incoterm",
        "feeder_vessel",
        "etd_jkt",
        "carton_box_qty",
        "gross_weight",
        "bl",
        "no_open",
        "hs_code",
        "net_weight",
        "measurement",
        "peb",
        "other",
        "count_container",
        "status",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
        "deleted_at"
    ];
}
