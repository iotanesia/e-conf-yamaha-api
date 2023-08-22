<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RegularDeliveryPlanShippingInsructionCreationDraft extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'regular_delivery_plan_shipping_instruction_creation_draft';
    public $fillable = [
        "to",
        "id_regular_delivery_plan_shipping_instruction_creation",
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
        "container_count",
        'count',
        "other",
        "description_of_goods_1",
        "description_of_goods_1_detail",
        "description_of_goods_2",
        "description_of_goods_2_detail",
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
