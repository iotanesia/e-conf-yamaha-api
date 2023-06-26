<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RegularFixedShippingInstructionRevision extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'regular_fixed_shipping_instruction_revision';

    public $fillable = [
        'id',
        'id_fixed_shipping_instruction',
        'id_user',
        'note',
        'type',
        'created_by',
        'created_at',
        'updated_by',
        'updated_at',
        'deleted_at'
    ];

    public function refUser()
    {
        return $this->belongsTo(User::class,'id_user','id');
    }

    public static function boot() {
        parent::boot();
    }
}
