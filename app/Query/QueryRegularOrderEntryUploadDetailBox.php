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
        $part = RegularOrderEntryUploadDetail::find($id);
        $data = MstBox::where('code_consignee', $part->code_consignee)
            ->where('item_no', $part->item_no)
            ->get();

        return [
            'items' => $data->map(function ($item) use ($id){

                $p = $item->length == null || $item->length == 0 ? 0 : round($item->length/1000,1);
                $l = $item->width == null || $item->width == 0 ? 0 : round($item->width/1000,1);
                $t = $item->height == null || $item->height == 0 ? 0 : round($item->height/1000,1);

                $set["id_box"] = $item->id;
                $set["no_box"] = $item->no_box;
                $set["name"] = $item->qty." pcs";
                $set["size"] = $p." x ".$l." x ".$t;
                $set["count"] = self::getCountBox($id, $item->id);

                return $set;
            }),
            'attributes' => [
                'total' => count($data),
                'current_page' => null,
                'from' => null,
                'per_page' => null,
            ],
            'last_page' => null
        ];
    }

    public static function getCountBox($id,$id_box){
        return Model::where('id_regular_order_entry_upload_detail', $id)
            ->where('id_box', $id_box)->count() ?? 0;

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
