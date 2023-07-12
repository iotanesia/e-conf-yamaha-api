<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RegularDeliveryPlanShippingInsructionCreation extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'regular_delivery_plan_shipping_instruction_creation';
    public $fillable = [
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
        "cosignee_address",
        "count_container",
        "measurement",
        "peb",
        "other",
        "issued",
        "status",
        "tel",
        "fax",
        "fax_id",
        "tel_consignee",
        "fax_consignee",
        "tel_noitfy_part",
        "fax_notify_part",
        "container_value",
        "container_type",
        "description_of_goods_1",
        "description_of_goods_2",
        "seal_no",
        "packing_list_no",
        "issued",
        "created_at",
        "created_by" ,
        "updated_at",
        "updated_by",
        "deleted_at"
    ];

    public static function boot()
    {
        parent::boot();
        static::creating(function ($model){
            // $model->uuid = (string) Str::uuid();
        });
    }
}
