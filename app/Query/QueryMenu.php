<?php

namespace App\Query;

use App\Constants\Constant;
use App\Models\Menu AS Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\ApiHelper as Helper;
use Illuminate\Support\Facades\Cache;

class QueryMenu extends Model {


    const cast = 'setting-menu';


    public static function getAll($params)
    {
        $key = self::cast.json_encode($params->query());
        return Helper::storageCache($key, function () use ($params){
            $query = self::where(function ($query) use ($params){
               if($params->kueri) $query->where('name',"%$params->kueri%");

            });
            if($params->withTrashed == 'true') $query->withTrashed();
            $limit = isset($params->dropdown) && intval($params->dropdown) == Constant::IS_ACTIVE? Model::count() : ($params->limit?? null);
            $data = $query
            ->whereNull('parent')
            ->orderBy('order','asc')
            ->paginate($limit);

            $items = $data->getCollection()->transform(function ($item){
                $item->children = $item->manyChild;
                $i = 0;
                foreach($item->manyChild  as $child){
                    if($child->url == "#"){
                        $item->children[$i]->children = self::where('parent',$child->id)->orderBy('order', 'asc')->get();
                    }
                    $i++;
                }
                unset($item->manyChild);
                return $item;
            });

            if(isset($params->dropdown) && intval($params->dropdown) && isset($params->parent) && intval($params->parent) == 1){
                $menuList = [];
                foreach($items->toArray() as $item){
                    $menu = new \stdClass;
                    $menu->id = $item['id'];
                    $menu->name = $item['name'];
                    $menu->category = $item['category'];
                    $menu->icon = $item['icon'];
                    $menu->url = $item['url'];
                    $menu->tag_variant = $item['tag_variant'];
                    $menu->order = $item['order'];

                    array_push($menuList, $menu);
                }

                $items = $menuList;
            }

            return [
                'items' => $items,
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
        return self::find($id);
    }

    public static function store($request,$is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {

            Helper::requireParams([
                'name',
                'url',
                'order'
            ]);


            $params = $request->all();
            self::create($params);
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
                'name',
                'url',
                'order'
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


}
