<?php

namespace App\Query;

use App\Constants\Constant;
use App\Models\MstBox AS Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\ApiHelper as Helper;
use App\Models\MstPart;
use Illuminate\Support\Facades\Cache;

class QueryMstBox extends Model {


    const cast = 'master-box';


    public static function getAll($params)
    {
        $key = self::cast.json_encode($params->query());
        return Helper::storageCache($key, function () use ($params){
            $query = self::where(function ($query) use ($params){
               if($params->kueri) $query->where('no_box',"%$params->kueri%");

            });
            if($params->withTrashed == 'true') $query->withTrashed();
            $data = $query
            ->orderBy('id','asc')
            ->paginate($params->limit ?? null);
            return [
                'items' => $data->getCollection()->transform(function($item){
                    $item->part_item_no = $item->refPart->item_no ?? null;
                    $item->part_description = $item->refPart->description ?? null;
                    unset(
                        $item->refPart
                    );
                    return $item;
                }),
                'attributes' => [
                    'total' => $data->total(),
                    'current_page' => $data->currentPage(),
                    'from' => $data->currentPage(),
                    'per_page' => (int) $data->perPage(),
                ]
            ];
        });
    }

    public static function byId($id)
    {
        $result = self::find($id);
        if($result) {
            $result->part_item_no = $result->refPart->item_no ?? null;
            $result->part_description = $result->refPart->description ?? null;
            unset(
                $result->refPart
            );
        }
        return $result;
    }

    public static function store($request,$is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {

            Helper::requireParams([
                'no_box',
                'item_no'
            ]);

            $params = $request->all();
            $num_set = Model::orderByDesc('id')->first()->num_set;

            for ($i=0; $i < count($params['item_no']); $i++) { 
                $mst_part = MstPart::where('item_no', $params['item_no'][$i])->get();
                self::create([
                    "no_box" => $params['no_box'],
                    "id_group_product" => $params['id_group_product'][$i],
                    "id_part" => $mst_part[$i]->id,
                    "item_no" => $params['item_no'][$i],
                    "item_no_series" => $mst_part[$i]->item_serial,
                    "qty" => $params['qty'][$i],
                    "unit_weight_gr" => $params['unit_weight_gr'][$i],
                    "unit_weight_kg" => $params['unit_weight_kg'][$i],
                    "outer_carton_weight" => $params['outer_carton_weight'],
                    "total_gross_weight" => $params['total_gross_weight'],
                    "length" => $params['length'],
                    "width" => $params['width'],
                    "height" => $params['height'],
                    "ratio" => $params['ratio'],
                    "fork_length" => $params['fork_length'],
                    "row_qty" => $params['row_qty'],
                    "box_in_cont" => $params['box_in_cont'],
                    "qty_in_cont" => $params['qty_in_cont'],
                    "fork_side" => $params['fork_side'],
                    "code_consignee" => $params['code_consignee'],
                    "size" => $params['size'],
                    "volume" => (float)substr((($params['length'] * $params['width'] * $params['height']) / 1000000000),0,4),
                    "part_set" => $params['part_set'],
                    "num_set" => $num_set == null ? 1 : $num_set +1
                ]);
                
                $num_set = $num_set; 
            }
            
            if($is_transaction) DB::commit();
            Cache::flush([self::cast]); //delete cache

        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }

    public static function change($request,$is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {

            Helper::requireParams([
                'id',
                'no_box',
                'id_part'
            ]);

            $params = $request->all();
            $update = self::find($params['id']);
            if(!$update) throw new \Exception("id tida ditemukan", 400);
            $update->fill($params);
            $update->save();
            if($is_transaction) DB::commit();
            Cache::flush([self::cast]); //delete cache
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }

    public static function deleted($id,$is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {
            self::destroy($id);
            if($is_transaction) DB::commit();
            Cache::flush([self::cast]); //delete cache
        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }

    public static function byItemNoCdConsignee($itemNo,$consingee)
    {
        // echo 'item no : '.$itemNo." consignee : ".$consingee;
        // die();
        $tes = self::where('item_no',trim($itemNo))
            ->where('code_consignee',trim($consingee))
            ->first() ?? null;

        DB::table('box_temporary')->insert([
            'item_no' => trim($itemNo),
            'consignee' => trim($consingee),
            'status' => $tes ? 1 : 0
        ]);
        return $tes;
    }


}
