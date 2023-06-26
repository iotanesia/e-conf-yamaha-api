<?php

namespace App\Query;

use App\Constants\Constant;
use App\Models\Permission AS Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\ApiHelper as Helper;
use Illuminate\Support\Facades\Cache;

class QueryPermission extends Model {

    const cast = 'setting-permission';

    public static function getParentMenu($id_role)
    {

       $key = self::cast.$id_role;
       return Helper::storageCache($key,function () use ($id_role){
            return self::where('id_role',$id_role)
            ->whereHas('refMenu',function ($query){
                $query->whereNull('parent')->orderBy('order', 'asc');
            })
            ->get()
            ->map(function ($item) use ($id_role){
                $id_menu = $item->refMenu->id  ?? null;
                return [
                    'icon'=> $item->refMenu->icon ?? 'Home',
                    'id_menu'=> $id_menu,
                    'title'=> $item->refMenu->name  ?? null,
                    'subMenu'=> self::getChildMenu($id_role,$id_menu),
                    'pathname'=> $item->refMenu->url  ?? null
                ];
            });
        });

    }

    public static function getChildMenu($id_role,$id_menu)
    {
        $key = self::cast.$id_role.$id_menu;
        return Helper::storageCache($key,function () use ($id_role,$id_menu){
            return self::where('id_role',$id_role)
            ->whereHas('refMenu',function ($query) use ($id_menu){
                $query->where('parent',$id_menu)->orderBy('order', 'asc');
            })
            ->get()
            ->map(function ($item) use ($id_role){
                $id_menu = $item->refMenu->id  ?? null;
                return [
                    'icon'=> $item->refMenu->icon ?? 'Activity',
                    'id_menu'=> $id_menu,
                    'title'=> $item->refMenu->name  ?? null,
                    //'subMenu'=> self::getChildMenu($id_role,$id_menu),
                    'pathname'=> $item->refMenu->url  ?? null
                ];
            });
        });
    }

    public static function store($request,$is_transaction = true)
    {
        if($is_transaction) DB::beginTransaction();
        try {

            Helper::requireParams([
                'id_role',
                'id_menu'
            ]);

            $params = $request->all();
            self::where('id_role',$params['id_role'])->delete();
            self::insert(array_map(function ($id_menu) use ($params){
                return [
                    'id_menu' => $id_menu,
                    'id_role' => $params['id_role'],
                    'created_at' => now()
                ];
            },$params['id_menu']));

            if($is_transaction) DB::commit();
            Cache::flush([self::cast]); //delete cache

        } catch (\Throwable $th) {
            if($is_transaction) DB::rollBack();
            throw $th;
        }
    }

}
