<?php

namespace App\Query;

use App\Models\RegularOrderEntryUploadDetailBox AS Model;
use App\Models\RegularOrderEntryUploadDetail;
use App\Models\MstBox;
use BaconQrCode\Common\Mode;
use DB;
use Illuminate\Support\Str;
use PhpParser\Node\Expr\AssignOp\Mod;

class QueryRegularOrderEntryUploadDetailBox extends Model {

    const cast = 'regular-order-entry-upload-detail-box';

    public static function getBoxPivot($id)
    {
        $data = Model::select('id_box', DB::raw('count(id) as count'))
            ->where('id_regular_order_entry_upload_detail', $id)
            ->groupBy('id_box')
            ->paginate(10);

        return [
            'items' => $data->map(function ($item){

                $p = $item->refBox->length == null || $item->refBox->length == 0 ? 0 : round($item->refBox->length/1000,1);
                $l = $item->refBox->width == null || $item->refBox->width == 0 ? 0 : round($item->refBox->width/1000,1);
                $t = $item->refBox->height == null || $item->refBox->height == 0 ? 0 : round($item->refBox->height/1000,1);

                $set["id_box"] = $item->id_box;
                $set["no_box"] = $item->refBox->no_box;
                $set["name"] = $item->refBox->qty." pcs";
                $set["size"] = $p." x ".$l." x ".$t;
                $set["count"] = $item->count;

                unset($item->refBox);
                return $set;
            }),
            'last_page' => $data->lastPage(),
            'attributes' => [
                'total' => $data->total(),
                'current_page' => $data->currentPage(),
                'from' => $data->currentPage(),
                'per_page' => (int) $data->perPage(),
            ],
            'last_page' => $data->lastPage()
        ];
    }

    public static function editBoxPivot($id, $params,$is_transaction = true){

        if($is_transaction) DB::beginTransaction();
        try {
            $box = $params->all();

            if (!count($box['id_box'])) throw new \Exception("Request must be filled", 400);
            $dataOrdered = RegularOrderEntryUploadDetail::find($id) ?? null;
            $countOrdered = $dataOrdered->qty ?? 0;
            $uuidOrdered = $dataOrdered->uuid ?? null;
            $countBox = 0;
            foreach ($box['id_box'] as $value) {
                $qtyBox = MstBox::find($value)->qty ?? 0;
                $countBox = $countBox + $qtyBox;
            }
            if($countBox < $countOrdered)
                throw new \Exception("the total quantity of the box is not sufficient for the total quantity of the order", 400);
            Model::where('id_regular_order_entry_upload_detail',$id)->forceDelete();
            foreach ($box['id_box'] as $value){
                $data["id_box"] = $value;
                $data["uuid"] = Str::uuid();
                $data["uuid_regular_order_entry_upload_detail"] = $uuidOrdered;
                $data["id_regular_order_entry_upload_detail"] = $id;
                $data["created_at"] = date('Y-m-d H:i:s');
                $data["updated_at"] = date('Y-m-d H:i:s');
                Model::insert($data);
            }
            if($is_transaction)DB::commit();
        }catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }
}
