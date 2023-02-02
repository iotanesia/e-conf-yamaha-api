<?php

namespace App\Query;

use App\Constants\Constant;
use App\Models\Permission AS Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\ApiHelper as Helper;
use Illuminate\Support\Facades\Cache;

class QueryPermission extends Model {

    public static function getParentMenu($id_role)
    {
        return self::where('id_role',$id_role)
        ->whereHas('refMenu',function ($query){
            $query->whereNull('parent');
        })
        ->get()->map(function ($item) use ($id_role){
            $id_menu = $item->refMenu->id  ?? null;
            return [
                'icon'=>'Home',
                'id_menu'=> $id_menu,
                'title'=> $item->refMenu->name  ?? null,
                'subMenu'=> self::getChildMenu($id_role,$id_menu),
                'pathname'=> $item->refMenu->url  ?? null
            ];
        });
    }

    public static function getChildMenu($id_role,$id_menu)
    {
        return self::where('id_role',$id_role)
        ->whereHas('refMenu',function ($query) use ($id_menu){
            $query->where('parent',$id_menu);
        })
        ->get()->map(function ($item) use ($id_role){
            $id_menu = $item->refMenu->id  ?? null;
            return [
                'icon'=>'Home',
                'id_menu'=> $id_menu,
                'title'=> $item->refMenu->name  ?? null,
                'subMenu'=> self::getChildMenu($id_role,$id_menu),
                'pathname'=> $item->refMenu->url  ?? null
            ];
        });
    }
}
