<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegularPropspectContainer extends Model {
    protected $table = 'regular_prospect_container';

    protected $fillable = [
        'code_consignee',
        'id_container',
        'id_mot',
        'id_lsp',
        'no_packaging',
        'measurement',
        'summary_box',
        'datasource',
        'etd_ypmi' ,
        'etd_wh' ,
        'etd_jkt' ,
        'created_at',
        'created_by',
        'updated_at',
        'updated_by',
        'deleted_at'
    ];
}

