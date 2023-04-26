<?php

namespace App\Query;

use Barryvdh\DomPDF\Facade\Pdf;
use Dompdf\Dompdf;
use Illuminate\Support\Str;

class QueryDocument {


    public static function main($params)
    {
        try {
            if($params->name == 'PL') return self::packingList($params);
            if($params->name == 'PLD') return self::packingList($params);
            else throw new \Exception("Reference name not found", 400);

        } catch (\Throwable $th) {
            throw $th;
        }
    }


    public static function packingListDetail($params)
    {
        try {
            $filename = Str::uuid().'-'.$params->id.'.pdf';
            $path = storage_path().'/app/regular/'.$filename;
            $template = 'pdf.packing-list-detail';

            Pdf::loadView($template)
              ->save($path)
              ->setPaper('A4','potrait')
              ->download($filename);

            return [
                'filename' => $filename,
                'path' => $path,
            ];
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public static function packingList($params)
    {
        try {
            $filename = Str::uuid().'-'.$params->id.'.pdf';
            $path = storage_path().'/app/regular/'.$filename;
            $template = 'pdf.packing-list';

            Pdf::loadView($template)
              ->save($path)
              ->setPaper('A4','potrait')
              ->download($filename);

            return [
                'filename' => $filename,
                'path' => $path,
            ];
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
