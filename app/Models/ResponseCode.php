<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResponseCode extends Model {
    protected $table = 'responses_code';

    static function getListBySlug($param) {
        return ResponseCode::where('slug', $param)->first();
    }
}
