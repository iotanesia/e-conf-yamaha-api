<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
class RegularProspectContainerDetail extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'regular_prospect_container_detail';
    public $fillable = [
        "id",
        "id_prospect_container_fifo",
        "id_prospect_container",
        "code_consignee",
        "model",
        "item_no",
        "disburse",
        "delivery",
        "qty",
        "status",
        "order_no",
        "cust_item_no",
        "etd_wh",
        "etd_jkt",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
        "deleted_at",
        "uuid"
    ];

    public function refRegularProspectContainer()
    {
        return $this->belongsTo(RegularProspectContainer::class,'id_prospect_container','id');
    }

    public function manyBox()
    {
        return $this->hasMany(RegularProspectContainerDetailBox::class,'id_prospect_container_detail','id');
    }

    public static function boot()
    {
        parent::boot();
        static::creating(function ($model){
            $model->uuid = (string) Str::uuid();
        });
    }
}
