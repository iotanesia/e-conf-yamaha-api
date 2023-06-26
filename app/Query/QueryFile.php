<?php

namespace App\Query;

use App\ApiHelper as Helper;
use App\Constants\Constant;
use App\Models\RegularDeliveryPlanBox;
use Illuminate\Support\Facades\File;

class QueryFile {

    public static function download($params)
    {
        Helper::requireParams([
            'filename',
            'source'
        ]);
        $file = null;
        $pathfile = null;
        if($params->source == 'qr_labeling') {
            $file = RegularDeliveryPlanBox::where('qrcode',$params->filename)->first();
            $pathfile = storage_path().Constant::PATHFILE['qr'].'/'.$file->qrcode;
        }
        if(!File::exists($pathfile)) throw new \Exception("file not found", 400);
        return [
            'path' => $pathfile,
            'filename' => $file->qrcode
        ];
    }

}
