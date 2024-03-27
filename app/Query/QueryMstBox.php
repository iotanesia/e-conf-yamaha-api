<?php

namespace App\Query;

use App\Constants\Constant;
use App\Models\MstBox AS Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\ApiHelper as Helper;
use App\Models\MstGroupProduct;
use App\Models\MstPart;
use Illuminate\Support\Facades\Cache;

class QueryMstBox extends Model {


    const cast = 'master-box';


    public static function getAll($params)
    {
        $key = self::cast.json_encode($params->query());
        return Helper::storageCache($key, function () use ($params){
            $query = self::select('mst_box.id_box','mst_box.part_set',
            DB::raw("string_agg(DISTINCT mst_box.id::character varying, ',') as id_mst_box"),
            DB::raw("string_agg(DISTINCT mst_box.no_box::character varying, ',') as no_box"),
            DB::raw("string_agg(DISTINCT mst_box.id_group_product::character varying, ',') as id_group_product"),
            DB::raw("string_agg(DISTINCT mst_box.id_consignee::character varying, ',') as id_consignee"),
            DB::raw("string_agg(DISTINCT mst_box.id_part::character varying, ',') as id_part"),
            DB::raw("string_agg(DISTINCT mst_box.item_no::character varying, ',') as item_no"),
            DB::raw("string_agg(DISTINCT mst_box.item_no_series::character varying, ',') as item_no_series"),
            DB::raw("string_agg(DISTINCT mst_box.qty::character varying, ',') as qty"),
            DB::raw("string_agg(DISTINCT mst_box.unit_weight_gr::character varying, ',') as unit_weight_gr"),
            DB::raw("string_agg(DISTINCT mst_box.unit_weight_kg::character varying, ',') as unit_weight_kg"),
            DB::raw("string_agg(DISTINCT mst_box.outer_carton_weight::character varying, ',') as outer_carton_weight"),
            DB::raw("string_agg(DISTINCT mst_box.total_gross_weight::character varying, ',') as total_gross_weight"),
            DB::raw("string_agg(DISTINCT mst_box.length::character varying, ',') as length"),
            DB::raw("string_agg(DISTINCT mst_box.width::character varying, ',') as width"),
            DB::raw("string_agg(DISTINCT mst_box.height::character varying, ',') as height"),
            DB::raw("string_agg(DISTINCT mst_box.ratio::character varying, ',') as ratio"),
            DB::raw("string_agg(DISTINCT mst_box.fork_length::character varying, ',') as fork_length"),
            DB::raw("string_agg(DISTINCT mst_box.row_qty::character varying, ',') as row_qty"),
            DB::raw("string_agg(DISTINCT mst_box.box_in_cont::character varying, ',') as box_in_cont"),
            DB::raw("string_agg(DISTINCT mst_box.qty_in_cont::character varying, ',') as qty_in_cont"),
            DB::raw("string_agg(DISTINCT mst_box.fork_side::character varying, ',') as fork_side"),
            DB::raw("string_agg(DISTINCT mst_box.code_consignee::character varying, ',') as code_consignee"),
            DB::raw("string_agg(DISTINCT mst_box.stack_capacity::character varying, ',') as stack_capacity"),
            DB::raw("string_agg(DISTINCT mst_box.size::character varying, ',') as size"),
            DB::raw("string_agg(DISTINCT mst_box.volume::character varying, ',') as volume"),
            DB::raw("string_agg(DISTINCT mst_box.num_set::character varying, ',') as num_set"),
            )->where(function ($query) use ($params){
               if($params->kueri) $query->where('no_box',"like", "%$params->kueri%")
                                        ->orWhere('item_no',"like", "%$params->kueri%")
                                        ->orWhere('item_no_series',"like", "%$params->kueri%")
                                        ->orWhere('qty',"like", "%$params->kueri%")
                                        ->orWhere('part_set',"like", "%$params->kueri%");

            });
            if($params->withTrashed == 'true') $query->withTrashed();
            $data = $query
            ->groupBy('part_set','id_box')
            ->orderBy('id_mst_box','asc')
            ->paginate($params->limit ?? null);
            return [
                'items' => $data->getCollection()->transform(function($item){

                    if (count(explode(',',$item->id_part)) > 1) {
                        $part = MstPart::whereIn('id', explode(',',$item->id_part))->get();
                        $part_item_no = $part->pluck('item_no') ?? null;
                        $part_description = $part->pluck('description') ?? null;
                    } else {
                        $part_item_no = [$item->refPart->item_no] ?? null;
                        $part_description = [$item->refPart->description] ?? null;

                        unset(
                            $item->refPart
                        );
                    }
                    
                    $item->consignee = $item->refConsignee->nick_name ?? null;
                    $item->division = $item->refGroupProduct->group_product ?? null;
                    $item->part_item_no = $part_item_no;
                    $item->part_description = $part_description;
                    $item->id_group_product = explode(',',$item->id_group_product);
                    $item->id_part = explode(',',$item->id_part);
                    $item->item_no = explode(',',$item->item_no);
                    $item->item_no_series = explode(',',$item->item_no_series);

                    unset(
                        $item->refConsignee,
                        $item->refGroupProduct,
                    );
                    
                    return $item;
                }),
                'attributes' => [
                    'total' => $data->total(),
                    'current_page' => $data->currentPage(),
                    'from' => $data->currentPage(),
                    'per_page' => (int) $data->perPage(),
                ],
                'last_page' => $data->lastPage()
            ];
        });
    }

    public static function byId($id)
    {
        $query = self::select('mst_box.id_box','mst_box.part_set',
            DB::raw("string_agg(DISTINCT mst_box.id::character varying, ',') as id_mst_box"),
            DB::raw("string_agg(DISTINCT mst_box.no_box::character varying, ',') as no_box"),
            DB::raw("string_agg(DISTINCT mst_box.id_group_product::character varying, ',') as id_group_product"),
            DB::raw("string_agg(DISTINCT mst_box.id_consignee::character varying, ',') as id_consignee"),
            DB::raw("string_agg(DISTINCT mst_box.id_part::character varying, ',') as id_part"),
            DB::raw("string_agg(DISTINCT mst_box.item_no::character varying, ',') as item_no"),
            DB::raw("string_agg(DISTINCT mst_box.item_no_series::character varying, ',') as item_no_series"),
            DB::raw("string_agg(DISTINCT mst_box.qty::character varying, ',') as qty"),
            DB::raw("string_agg(DISTINCT mst_box.unit_weight_gr::character varying, ',') as unit_weight_gr"),
            DB::raw("string_agg(DISTINCT mst_box.unit_weight_kg::character varying, ',') as unit_weight_kg"),
            DB::raw("string_agg(DISTINCT mst_box.outer_carton_weight::character varying, ',') as outer_carton_weight"),
            DB::raw("string_agg(DISTINCT mst_box.total_gross_weight::character varying, ',') as total_gross_weight"),
            DB::raw("string_agg(DISTINCT mst_box.length::character varying, ',') as length"),
            DB::raw("string_agg(DISTINCT mst_box.width::character varying, ',') as width"),
            DB::raw("string_agg(DISTINCT mst_box.height::character varying, ',') as height"),
            DB::raw("string_agg(DISTINCT mst_box.ratio::character varying, ',') as ratio"),
            DB::raw("string_agg(DISTINCT mst_box.fork_length::character varying, ',') as fork_length"),
            DB::raw("string_agg(DISTINCT mst_box.row_qty::character varying, ',') as row_qty"),
            DB::raw("string_agg(DISTINCT mst_box.box_in_cont::character varying, ',') as box_in_cont"),
            DB::raw("string_agg(DISTINCT mst_box.qty_in_cont::character varying, ',') as qty_in_cont"),
            DB::raw("string_agg(DISTINCT mst_box.fork_side::character varying, ',') as fork_side"),
            DB::raw("string_agg(DISTINCT mst_box.code_consignee::character varying, ',') as code_consignee"),
            DB::raw("string_agg(DISTINCT mst_box.stack_capacity::character varying, ',') as stack_capacity"),
            DB::raw("string_agg(DISTINCT mst_box.size::character varying, ',') as size"),
            DB::raw("string_agg(DISTINCT mst_box.volume::character varying, ',') as volume"),
            DB::raw("string_agg(DISTINCT mst_box.num_set::character varying, ',') as num_set"),
            )->whereIn('id',explode(',',$id))
            ->groupBy('part_set','id_box')
            ->orderBy('id_mst_box','asc')
            ->paginate(1);

        $data = $query->getCollection()->transform(function($item){

            if (count(explode(',',$item->id_part)) > 1) {
                $part = MstPart::whereIn('id', explode(',',$item->id_part))->get();
                $part_item_no = $part->pluck('item_no') ?? null;
                $part_description = $part->pluck('description') ?? null;
            } else {
                $part_item_no = [$item->refPart->item_no] ?? null;
                $part_description = [$item->refPart->description] ?? null;

                unset(
                    $item->refPart
                );
            }

            $item->consignee = $item->refConsignee->nick_name ?? null;
            $item->part_item_no = $part_item_no;
            $item->part_description = $part_description;
            $item->id_mst_box = explode(',',$item->id_mst_box);
            $item->id_group_product = explode(',',$item->id_group_product);
            $item->id_part = explode(',',$item->id_part);
            $item->item_no = explode(',',$item->item_no);
            $item->item_no_series = explode(',',$item->item_no_series);
            $item->group_product = MstGroupProduct::whereIn('id', $item->id_group_product)->get()->pluck('group_product') ?? null;
            
            unset(
                $item->refConsignee,
            );

            return $item;
        });

        return $data[0] ?? null;
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
            $id_box = Model::where('id_box', Model::max('id_box'))->first()->id_box;

            for ($i=0; $i < count($params['item_no']); $i++) { 
                $mst_part = MstPart::where('item_no', $params['item_no'][$i])->get();
                self::create([
                    "no_box" => $params['no_box'] ?? null,
                    "id_group_product" => $params['id_group_product'][$i] ?? null,
                    "id_part" => $mst_part[0]->id ?? null,
                    "item_no" => $params['item_no'][$i] ?? null,
                    "item_no_series" => $mst_part[0]->item_serial ?? null,
                    "qty" => $params['qty'][$i] ?? null,
                    "unit_weight_gr" => $params['unit_weight_gr'][$i] ?? null,
                    "unit_weight_kg" => ($params['unit_weight_gr'][$i] / 1000) ?? null,
                    "outer_carton_weight" => $params['outer_carton_weight'] ?? null,
                    "total_gross_weight" => $params['total_gross_weight'] ?? null,
                    "length" => $params['length'] ?? null,
                    "width" => $params['width'] ?? null,
                    "height" => $params['height'] ?? null,
                    "ratio" => $params['ratio'][$i] ?? null,
                    "fork_length" => $params['fork_length'] ?? null,
                    "row_qty" => $params['row_qty'][$i] ?? null,
                    "box_in_cont" => $params['box_in_cont'][$i] ?? null,
                    "qty_in_cont" => $params['qty_in_cont'][$i] ?? null,
                    "fork_side" => $params['fork_side'] ?? null,
                    "code_consignee" => $params['code_consignee'] ?? null,
                    "size" => $params['size'] ?? null,
                    "volume" => (float)substr((($params['length'] * $params['width'] * $params['height']) / 1000000000),0,4),
                    "part_set" => count($params['item_no']) > 1 ? 'set' : 'single',
                    "id_box" => $id_box == null ? 1 : $id_box +1
                ]);
                
                $id_box = $id_box; 
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
                'no_box'
            ]);

            $params = $request->all();
            $update = self::whereIn('id', $params['id'])->get();
            if(count($update) == 0) throw new \Exception("data tidak ditemukan", 400);

            $params = $request->all();
            foreach ($update as $i => $update_data) {
                $mst_part = MstPart::where('item_no', $params['item_no'][$i])->get();
                $update_data->update([
                    "no_box" => $params['no_box'] ?? null,
                    "id_group_product" => $params['id_group_product'][$i] ?? null,
                    "id_part" => $mst_part[0]->id ?? null,
                    "item_no" => $params['item_no'][$i] ?? null,
                    "item_no_series" => $mst_part[0]->item_serial ?? null,
                    "qty" => $params['qty'][$i] ?? null,
                    "unit_weight_gr" => $params['unit_weight_gr'][$i] ?? null,
                    "unit_weight_kg" => ($params['unit_weight_gr'][$i]/1000) ?? null,
                    "outer_carton_weight" => $params['outer_carton_weight'] ?? null,
                    "total_gross_weight" => $params['total_gross_weight'] ?? null,
                    "length" => $params['length'] ?? null,
                    "width" => $params['width'] ?? null,
                    "height" => $params['height'] ?? null,
                    "ratio" => $params['ratio'][$i] ?? null,
                    "fork_length" => $params['fork_length'] ?? null,
                    "row_qty" => $params['row_qty'][$i] ?? null,
                    "box_in_cont" => $params['box_in_cont'][$i] ?? null,
                    "qty_in_cont" => $params['qty_in_cont'][$i] ?? null,
                    "fork_side" => $params['fork_side'] ?? null,
                    "code_consignee" => $params['code_consignee'] ?? null,
                    "size" => $params['size'] ?? null,
                    "volume" => (float)substr((($params['length'] * $params['width'] * $params['height']) / 1000000000),0,4),
                    "part_set" => count($params['item_no']) > 1 ? 'set' : 'single',
                ]);
            }

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
            $data = self::whereIn('id',explode(',',$id))->get();
            foreach ($data as $key => $delete) {
                $delete->delete();
            }
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
        $tes = self::where('part_set', 'single')
            ->where('item_no',trim($itemNo))
            ->where('code_consignee',trim($consingee))
            ->first() ?? null;

        DB::table('box_temporary')->insert([
            'item_no' => trim($itemNo),
            'consignee' => trim($consingee),
            'status' => $tes ? 1 : 0
        ]);
        return $tes;
    }

    public static function byItemNoCdConsigneeDatasource($itemNo,$consingee,$datasource)
    {
        // echo 'item no : '.$itemNo." consignee : ".$consingee;
        // die();
        $tes = self::where('part_set', 'single')
            ->where('item_no',trim($itemNo))
            ->where('code_consignee',trim($consingee))
            ->where('datasource',trim($datasource))
            ->first() ?? null;

        DB::table('box_temporary')->insert([
            'item_no' => trim($itemNo),
            'consignee' => trim($consingee),
            'status' => $tes ? 1 : 0
        ]);
        return $tes;
    }

    public static function byItemNoCdConsigneeSet($itemNo,$consingee)
    {
        // echo 'item no : '.$itemNo." consignee : ".$consingee;
        // die();
        $tes = self::where('part_set', 'set')
            ->where('item_no',trim($itemNo))
            ->where('code_consignee',trim($consingee))
            ->first() ?? null;

        DB::table('box_temporary')->insert([
            'item_no' => trim($itemNo),
            'consignee' => trim($consingee),
            'status' => $tes ? 1 : 0
        ]);
        return $tes;
    }

    public static function byItemNoCdConsigneeDatasourceSet($itemNo,$consingee,$datasource)
    {
        // echo 'item no : '.$itemNo." consignee : ".$consingee;
        // die();
        $tes = self::where('part_set', 'set')
            ->where('item_no',trim($itemNo))
            ->where('code_consignee',trim($consingee))
            ->where('datasource',trim($datasource))
            ->first() ?? null;

        DB::table('box_temporary')->insert([
            'item_no' => trim($itemNo),
            'consignee' => trim($consingee),
            'status' => $tes ? 1 : 0
        ]);
        return $tes;
    }


}
