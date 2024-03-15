<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class IregularShippingInstructionCreation extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'iregular_shipping_instruction_creation';
    public $fillable = [
        "to",
        "id_iregular_shipping_instruction",
        "cc",
        "attn_to",
        "attn_cc",
        "shipper_address",
        "shipper_tel",
        "shipper_fax",
        "shipper_tax_id",
        "instruction_date",
        "si_number",
        "invoice_no",
        "consignee_name",
        "consignee_address",
        "consignee_tel",
        "consignee_fax",
        "notify_part_address",
        "notify_part_tel",
        "notify_part_fax",
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
        "container_value",
        "container_type",
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
