<?php

namespace App\Query;

use App\Constants\Constant;
use App\Models\RegularStokConfirmationHistory as Model;
use App\Models\RegularStokConfirmation;
use App\ApiHelper as Helper;
use App\Models\MstBox;
use App\Models\MstContainer;
use App\Models\MstLsp;
use App\Models\MstPart;
use App\Models\MstShipment;
use App\Models\RegularDeliveryPlan;
use App\Models\RegularDeliveryPlanBox;
use App\Models\RegularDeliveryPlanProspectContainerCreation;
use App\Models\RegularDeliveryPlanSet;
use App\Models\RegularFixedQuantityConfirmation;
use App\Models\RegularFixedQuantityConfirmationBox;
use App\Models\RegularOrderEntryUpload;
use App\Models\RegularOrderEntryUploadDetailTemp;
use App\Models\RegularStokConfirmationHistory;
use App\Models\RegularStokConfirmationOutstockNote;
use App\Models\RegularStokConfirmationTemp;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Pagination\Paginator;

class QueryStockConfirmationHistory extends Model
{

    const cast = 'regular-stock-confirmation-history';

    public static function deleteInStock($request, $id, $is_transaction = true)
    {
        if ($is_transaction) DB::beginTransaction();
        try {
            if (count(explode('-', $id)) > 1) {
                $id_box = explode('-', $id)[0];
                $total_item = explode('-', $id)[1];

                if (count(explode(',', $id_box)) > 1) throw new \Exception("Can only delete one data", 400);

                $box = RegularDeliveryPlanBox::find($id_box);
                $update = RegularStokConfirmation::where('id_regular_delivery_plan', $box->id_regular_delivery_plan)->first();
                $stock = Model::where('id_regular_delivery_plan', $box->id_regular_delivery_plan)
                    ->where('type', Constant::INSTOCK)
                    ->orderBy('qty_pcs_perbox', 'desc')
                    ->orderBy('id_regular_delivery_plan_box', 'asc')
                    ->get();

                $stokTemp = RegularStokConfirmationTemp::where('qr_key', $id)->first();
                $stokTemp->update(['is_reject' => 1]);

                foreach ($stock as $key => $val) {
                    if ($val->id_regular_delivery_plan_box === (int)$id_box) {
                        for ($i = 0; $i < $total_item; $i++) {
                            $del = Model::where('id_regular_delivery_plan_box', $stock[$key + $i]->id_regular_delivery_plan_box)->first();
                            $del->delete();
                        }
                    }
                }

                $plan_set = RegularDeliveryPlanSet::where('id_delivery_plan', $box->id_regular_delivery_plan)->get()->pluck('item_no');
                $qtybox = RegularDeliveryPlanBox::where('id_regular_delivery_plan', $box->id_regular_delivery_plan)
                    ->orderBy('qty_pcs_box', 'desc')
                    ->orderBy('id', 'asc')
                    ->get();
                $qty_pcs_box = [];
                foreach ($qtybox as $key => $val) {
                    if ($val->id === (int)$id_box) {
                        for ($i = 0; $i < $total_item; $i++) {
                            $qty_pcs_box[] = $qtybox[$key + $i]->qty_pcs_box;
                        }
                    }
                }
                $qty_pcs_box = array_sum($qty_pcs_box) / count($plan_set->toArray());

                $update->update([
                    'production' => $update->production + $qty_pcs_box,
                    'in_dc' => $update->in_dc - $qty_pcs_box,
                    'status_instock' => $update->in_dc == 0 ? Constant::STS_STOK : 2,
                ]);
            } else {
                if (count(explode(',', $id)) > 1) throw new \Exception("Can only delete one data", 400);
                $qty = RegularDeliveryPlanBox::find($id);
                $stock = Model::where('id_regular_delivery_plan_box', $qty->id)->where('type', Constant::INSTOCK)->first();
                $update = RegularStokConfirmation::where('id_regular_delivery_plan', $qty->id_regular_delivery_plan)->first();

                $stokTemp = RegularStokConfirmationTemp::where('qr_key', $qty->id)->first();
                $stokTemp->update(['is_reject' => 1]);

                $update->update([
                    'production' => $update->production + $qty->qty_pcs_box,
                    'in_dc' => $update->in_dc - $qty->qty_pcs_box,
                    'status_instock' => $update->in_dc == 0 ? Constant::STS_STOK : 2,
                ]);
                $stock->delete();
            }

            if ($is_transaction) DB::commit();
        } catch (\Throwable $th) {
            if ($is_transaction) DB::rollBack();
            throw $th;
        }
    }
    public static function deleteOutStock($request, $id, $is_transaction = true)
    {
        if ($is_transaction) DB::beginTransaction();
        try {

            if (count(explode('-', $id)) > 1) {
                $id_box = explode('-', $id)[0];
                $total_item = explode('-', $id)[1];

                if (count(explode(',', $id_box)) > 1) throw new \Exception("Can only delete one data", 400);

                $box = RegularDeliveryPlanBox::find($id_box);
                $update = RegularStokConfirmation::where('id_regular_delivery_plan', $box->id_regular_delivery_plan)->first();
                $stock = Model::where('id_regular_delivery_plan', $box->id_regular_delivery_plan)
                    ->where('type', Constant::OUTSTOCK)
                    ->orderBy('qty_pcs_perbox', 'desc')
                    ->orderBy('id_regular_delivery_plan_box', 'asc')
                    ->get();

                $stokTemp = RegularStokConfirmationTemp::where('qr_key', $id)->first();
                $stokTemp->update([
                    'is_reject' => 1,
                    'status_outstock' => 1
                ]);

                foreach ($stock as $key => $val) {
                    if ($val->id_regular_delivery_plan_box === (int)$id_box) {
                        for ($i = 0; $i < $total_item; $i++) {
                            $history = Model::query();
                            // $history->where('id_regular_delivery_plan_box',$stock[$key+$i]->id_regular_delivery_plan_box)->where('type',Constant::INSTOCK)->first()->delete();
                            $history->where('id_regular_delivery_plan_box', $stock[$key + $i]->id_regular_delivery_plan_box)->where('type', Constant::OUTSTOCK)->first()->delete();
                        }
                    }
                }

                $fix = RegularFixedQuantityConfirmation::where('id_regular_delivery_plan', $box->id_regular_delivery_plan)->first();
                $fix == null ? null : $fix->delete();

                $plan_set = RegularDeliveryPlanSet::where('id_delivery_plan', $box->id_regular_delivery_plan)->get()->pluck('item_no');
                $qtybox = RegularDeliveryPlanBox::where('id_regular_delivery_plan', $box->id_regular_delivery_plan)
                    ->orderBy('qty_pcs_box', 'desc')
                    ->orderBy('id', 'asc')
                    ->get();
                $qty_pcs_box = [];
                foreach ($qtybox as $key => $val) {
                    if ($val->id === (int)$id_box) {
                        for ($i = 0; $i < $total_item; $i++) {
                            $qty_pcs_box[] = $qtybox[$key + $i]->qty_pcs_box;
                        }
                    }
                }
                $qty_pcs_box = array_sum($qty_pcs_box) / count($plan_set->toArray());

                $update->update([
                    'production' => $update->production + $qty_pcs_box,
                    'in_dc' => $update->in_dc - $qty_pcs_box,
                    // 'in_wh' => $update->in_wh - $qty_pcs_box,
                    'status_outstock' => $update->in_wh == 0 ? Constant::STS_STOK : 2
                ]);
            } else {
                if (count(explode(',', $id)) > 1) throw new \Exception("Can only delete one data", 400);
                $box = RegularDeliveryPlanBox::find($id);
                $stock_out = Model::where('id_regular_delivery_plan_box', $box->id)->where('type', Constant::OUTSTOCK)->first();
                $update = RegularStokConfirmation::where('id_regular_delivery_plan', $box->id_regular_delivery_plan)->first();
                $fix = RegularFixedQuantityConfirmation::where('id_regular_delivery_plan', $box->id_regular_delivery_plan)->first();
                $fix == null ? null : $fix->delete();

                $stokTemp = RegularStokConfirmationTemp::where('qr_key', $box->id)->first();
                $stokTemp->update([
                    'is_reject' => 1,
                    'status_outstock' => 1
                ]);

                $update->update([
                    'production' => $update->production + $box->qty_pcs_box,
                    'in_dc' => $update->in_dc - $box->qty_pcs_box,
                    // 'in_wh' => $update->in_wh - $box->qty_pcs_box,
                    'status_outstock' => $update->in_wh == 0 ? Constant::STS_STOK : 2
                ]);

                // Model::where('id_regular_delivery_plan_box',$box->id)->where('type',Constant::INSTOCK)->delete();
                $stock_out->delete();
            }

            if ($is_transaction) DB::commit();
        } catch (\Throwable $th) {
            if ($is_transaction) DB::rollBack();
            throw $th;
        }
    }


    public static function getInStock($request)
    {
        $data = RegularStokConfirmation::where('status_instock', '=', 2)->where('in_dc', '>', 0)->paginate($request->limit ?? null);
        if (!$data) throw new \Exception("Data not found", 400);

        $result = [];
        foreach ($data as $key => $value) {
            if ($value->refRegularDeliveryPlan->item_no == null) {
                $plan_box = RegularDeliveryPlanBox::where('id_regular_delivery_plan', $value->id_regular_delivery_plan)->orderBy('qty_pcs_box', 'desc')->orderBy('id', 'asc')->get();
                $plan_set = RegularDeliveryPlanSet::where('id_delivery_plan', $value->id_regular_delivery_plan)->get()->pluck('item_no');
                $check_scan = RegularStokConfirmationHistory::where('id_regular_delivery_plan', $value->id_regular_delivery_plan)->where('type', 'INSTOCK')->get()->pluck('id_regular_delivery_plan_box');

                $mst_box = MstBox::where('part_set', 'set')->whereIn('item_no', $plan_set->toArray())->get();
                $sum_qty = [];
                foreach ($mst_box as $key => $value_box) {
                    $sum_qty[] = $value_box->qty;
                }

                $result_qty = [];
                $result_id_planbox = [];
                $result_arr = [];
                $qty = 0;
                $group_qty = [];
                $group_id_planbox = [];
                $group_arr = [];
                foreach ($plan_box as $key => $val) {
                    $qty += $val->qty_pcs_box;
                    if (in_array($val->id, $check_scan->toArray())) {
                        $group_qty[] = $val->qty_pcs_box;
                        $group_id_planbox[] = $val->id;
                        $group_arr[] = [
                            // 'id' => $value->id,
                            'id_regular_delivery_plan' => $val->refRegularDeliveryPlan->id,
                            'id_regular_order_entry' => $val->refRegularDeliveryPlan->id_regular_order_entry,
                            'code_consignee' => $val->refRegularDeliveryPlan->code_consignee,
                            'model' => $val->refRegularDeliveryPlan->model,
                            'item_no' => $plan_set->toArray(),
                            // 'qty' => $val->refRegularDeliveryPlan->qty,
                            'disburse' => $val->refRegularDeliveryPlan->disburse,
                            'delivery' => $val->refRegularDeliveryPlan->delivery,
                            'status_regular_delivery_plan' => $val->refRegularDeliveryPlan->status_regular_delivery_plan,
                            'order_no' => $val->refRegularDeliveryPlan->order_no,
                            'cust_item_no' => $val->refRegularDeliveryPlan->cust_item_no,
                            'created_at' => $val->refRegularDeliveryPlan->created_at,
                            'created_by' => $val->refRegularDeliveryPlan->created_by,
                            'updated_at' => $val->refRegularDeliveryPlan->updated_at,
                            'updated_by' => $val->refRegularDeliveryPlan->updated_by,
                            'deleted_at' => $val->refRegularDeliveryPlan->deleted_at,
                            'uuid' => $val->refRegularDeliveryPlan->uuid,
                            'etd_ypmi' => $val->refRegularDeliveryPlan->etd_ypmi,
                            'etd_wh' => $val->refRegularDeliveryPlan->etd_wh,
                            'etd_jkt' => $val->refRegularDeliveryPlan->etd_jkt,
                            'is_inquiry' => $val->refRegularDeliveryPlan->is_inquiry,
                            'id_prospect_container' => $val->refRegularDeliveryPlan->id_prospect_container,
                            'id_prospect_container_creation' => $val->refRegularDeliveryPlan->id_prospect_container_creation,
                            'status_bml' => $val->refRegularDeliveryPlan->status_bml,
                            'cust_name' => $val->refRegularDeliveryPlan->refConsignee->nick_name,
                            'status_desc' => 'Instock',
                            'box' => array_sum($sum_qty) . ' x 1 '
                        ];
                    }

                    if ($qty >= (array_sum($sum_qty) * count($plan_set->toArray()))) {
                        $result_qty[] = $group_qty;
                        $result_id_planbox[] = $group_id_planbox;
                        $result_arr[] = $group_arr[0] ?? [];
                        $qty = 0;
                        $group_qty = [];
                        $group_id_planbox = [];
                        $group_arr = [];
                    }
                }

                if (!empty($group_qty)) {
                    $result_qty[] = $group_qty;
                }
                if (!empty($group_id_planbox)) {
                    $result_id_planbox[] = $group_id_planbox;
                }
                if (!empty($group_arr)) {
                    $result_arr[] = $group_arr[0];
                }

                $result_merge = [];
                for ($i = 0; $i < count($result_qty); $i++) {
                    if (count($result_qty[$i]) !== 0) {
                        $merge_qty = [
                            'qty' => (array_sum($result_qty[$i]) / count($plan_set->toArray())),
                            'in_dc' => (array_sum($result_qty[$i]) / count($plan_set->toArray())),
                            'id' => $result_id_planbox[$i][0] . '-' . count($result_id_planbox[$i]),
                        ];
                        $result_merge[] = array_merge($merge_qty, $result_arr[$i]);
                    }
                }
            } else {
                $plan_box = RegularDeliveryPlanBox::where('id_regular_delivery_plan', $value->id_regular_delivery_plan)->get();
                $check_scan = RegularStokConfirmationHistory::where('id_regular_delivery_plan', $value->id_regular_delivery_plan)->where('type', 'INSTOCK')->get()->pluck('id_regular_delivery_plan_box');

                $result_qty = [];
                $result_arr = [];
                $qty = 0;
                $group_qty = [];
                $group_arr = [];
                foreach ($plan_box as $key => $val) {
                    $qty += $val->qty_pcs_box;
                    if (in_array($val->id, $check_scan->toArray())) {
                        $group_qty[] = $val->qty_pcs_box;
                        $group_arr[] = [
                            // 'id' => $value->id,
                            'id' => $val->id,
                            'id_regular_delivery_plan' => $val->refRegularDeliveryPlan->id,
                            'id_regular_order_entry' => $val->refRegularDeliveryPlan->id_regular_order_entry,
                            'code_consignee' => $val->refRegularDeliveryPlan->code_consignee,
                            'model' => $val->refRegularDeliveryPlan->model,
                            'item_no' => $val->refRegularDeliveryPlan->item_no,
                            // 'qty' => $val->refRegularDeliveryPlan->qty,
                            'qty' => $val->qty_pcs_box,
                            'disburse' => $val->refRegularDeliveryPlan->disburse,
                            'delivery' => $val->refRegularDeliveryPlan->delivery,
                            'status_regular_delivery_plan' => $val->refRegularDeliveryPlan->status_regular_delivery_plan,
                            'order_no' => $val->refRegularDeliveryPlan->order_no,
                            'cust_item_no' => $val->refRegularDeliveryPlan->cust_item_no,
                            'created_at' => $val->refRegularDeliveryPlan->created_at,
                            'created_by' => $val->refRegularDeliveryPlan->created_by,
                            'updated_at' => $val->refRegularDeliveryPlan->updated_at,
                            'updated_by' => $val->refRegularDeliveryPlan->updated_by,
                            'deleted_at' => $val->refRegularDeliveryPlan->deleted_at,
                            'uuid' => $val->refRegularDeliveryPlan->uuid,
                            'etd_ypmi' => $val->refRegularDeliveryPlan->etd_ypmi,
                            'etd_wh' => $val->refRegularDeliveryPlan->etd_wh,
                            'etd_jkt' => $val->refRegularDeliveryPlan->etd_jkt,
                            'is_inquiry' => $val->refRegularDeliveryPlan->is_inquiry,
                            'id_prospect_container' => $val->refRegularDeliveryPlan->id_prospect_container,
                            'id_prospect_container_creation' => $val->refRegularDeliveryPlan->id_prospect_container_creation,
                            'status_bml' => $val->refRegularDeliveryPlan->status_bml,
                            'cust_name' => $val->refRegularDeliveryPlan->refConsignee->nick_name,
                            'status_desc' => 'Instock',
                            'in_dc' => $val->qty_pcs_box,
                            'box' => $val->qty_pcs_box . ' x 1 '
                        ];
                    }

                    if ($qty >= $val->qty_pcs_box) {
                        $result_qty[] = $group_qty;
                        $result_arr[] = $group_arr[0] ?? [];
                        $qty = 0;
                        $group_qty = [];
                        $group_arr = [];
                    }
                }

                if (!empty($group_qty)) {
                    $result_qty[] = $group_qty;
                }
                if (!empty($group_arr)) {
                    $result_arr[] = $group_arr[0];
                }

                $result_merge = [];
                for ($i = 0; $i < count($result_qty); $i++) {
                    if (count($result_qty[$i]) !== 0) {
                        $result_merge[] = $result_arr[$i];
                    }
                }
            }

            $result[] = $result_merge;
        }

        $collection = new Collection(array_merge(...$result));

        // Paginate the collection
        $perPage = $request->limit;
        $page = Paginator::resolveCurrentPage('page') ?: $request->page;
        $offset = ($page - 1) * $perPage;
        $paginatedData = $collection->slice($offset, $perPage)->all();

        // Create a Paginator instance manually
        $paginator = new Paginator($paginatedData, count($collection), $perPage, [$page], [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => 'page',
        ]);

        $last_page = ceil(count($collection) / $perPage);

        return [
            'items' => array_values($paginator->items()) ?? [],
            'last_page' => $last_page
        ];
    }

    public static function getOutStock($request)
    {
        // $data = RegularStokConfirmation::where('status_outstock','=',2)->where('in_wh','>',0)->paginate($request->limit ?? null);
        $data = RegularStokConfirmationTemp::where(function ($query) use ($request) {
            $category = $request->category ?? null;
            $kueri = $request->kueri ?? null;

            if ($category && $kueri) {
                if ($category == 'cust_name') {
                    $query->whereHas('refRegularDeliveryPlan.refConsignee', function ($q) use ($kueri) {
                        $q->where('nick_name', 'like', '%' . $kueri . '%');
                    });
                } elseif ($category == 'item_name') {
                    $query->whereHas('refRegularDeliveryPlan.refPart', function ($q) use ($kueri) {
                        $q->where('description', 'like', '%' . $kueri . '%');
                    });
                } elseif ($category == 'item_no') {
                    $query->whereHas('refRegularDeliveryPlan', function ($q) use ($kueri) {
                        $q->where('item_no', 'like', '%' . str_replace('-', '', $kueri) . '%');
                    });
                } elseif ($category == 'order_no') {
                    $query->whereHas('refRegularDeliveryPlan', function ($q) use ($kueri) {
                        $q->where('order_no', 'like', '%' . $kueri . '%');
                    });
                } elseif ($category == 'cust_item_no') {
                    $query->whereHas('refRegularDeliveryPlan', function ($q) use ($kueri) {
                        $q->where('cust_item_no', 'like', '%' . $kueri . '%');
                    });
                } else {
                    $query->where('etd_jkt', 'like', '%' . $kueri . '%')
                        ->orWhere('etd_ypmi', 'like', '%' . $kueri . '%')
                        ->orWhere('etd_wh', 'like', '%' . $kueri . '%');
                }
            }
        })->where('status_outstock', '=', 2)->where('in_wh', '>', 0)->where('is_reject', null)->paginate($request->limit ?? null);

        if (!$data) throw new \Exception("Data not found", 400);

        $data->transform(function ($item) {

            $plan_box = RegularDeliveryPlanBox::where('id_regular_delivery_plan', $item->refRegularDeliveryPlan->id)->orderBy('qty_pcs_box', 'desc')->orderBy('id', 'asc')->get();
            if ($item->refRegularDeliveryPlan->item_no == null) {
                $plan_set = RegularDeliveryPlanSet::where('id_delivery_plan', $item->refRegularDeliveryPlan->id)->get()->pluck('item_no');
                $check_scan = RegularStokConfirmationHistory::where('id_regular_delivery_plan', $item->refRegularDeliveryPlan->id)->where('type', 'OUTSTOCK')->get()->pluck('id_regular_delivery_plan_box');

                $mst_box = MstBox::where('part_set', 'set')->whereIn('item_no', $plan_set->toArray())->get();
                $sum_qty = [];
                foreach ($mst_box as $key => $value_box) {
                    $sum_qty[] = $value_box->qty;
                }

                $result_qty = [];
                $result_id_planbox = [];
                $result_arr = [];
                $qty = 0;
                $group_qty = [];
                $group_id_planbox = [];
                $group_arr = [];
                foreach ($plan_box as $key => $val) {
                    $qty += $val->qty_pcs_box;
                    if (in_array($val->id, $check_scan->toArray())) {
                        $group_qty[] = $val->qty_pcs_box;
                        $group_id_planbox[] = $val->id;
                    }

                    if ($qty >= (array_sum($sum_qty) * count($plan_set->toArray()))) {
                        $result_qty[] = $group_qty;
                        $result_id_planbox[] = $group_id_planbox;
                        $result_arr[] = $group_arr[0] ?? [];
                        $qty = 0;
                        $group_qty = [];
                        $group_id_planbox = [];
                        $group_arr = [];
                    }
                }

                if (!empty($group_qty)) {
                    $result_qty[] = $group_qty;
                }
                if (!empty($group_id_planbox)) {
                    $result_id_planbox[] = $group_id_planbox;
                }
                if (!empty($group_arr)) {
                    $result_arr[] = $group_arr[0];
                }

                for ($i = 0; $i < count($result_qty); $i++) {
                    if (count($result_qty[$i]) !== 0) {
                        $in_wh = (array_sum($result_qty[$i]) / count($plan_set->toArray()));
                    }
                }

                $plan_set = RegularDeliveryPlanSet::where('id_delivery_plan', $item->refRegularDeliveryPlan->id)->get()->pluck('item_no');
            }

            $res['id'] = $item->qr_key;
            $res['id_regular_delivery_plan'] = $item->refRegularDeliveryPlan->id;
            $res['id_regular_order_entry'] = $item->refRegularDeliveryPlan->id_regular_order_entry;
            $res['code_consignee'] = $item->refRegularDeliveryPlan->code_consignee;
            $res['model'] = $item->refRegularDeliveryPlan->model;
            $res['item_no'] = $item->refRegularDeliveryPlan->item_no == null ? $plan_set->toArray() : $item->refRegularDeliveryPlan->item_no;
            $res['qty'] = $item->refRegularDeliveryPlan->qty;
            $res['disburse'] = $item->refRegularDeliveryPlan->disburse;
            $res['delivery'] = $item->refRegularDeliveryPlan->delivery;
            $res['status_regular_delivery_plan'] = $item->refRegularDeliveryPlan->status_regular_delivery_plan;
            $res['order_no'] = $item->refRegularDeliveryPlan->order_no;
            $res['cust_item_no'] = $item->refRegularDeliveryPlan->cust_item_no;
            $res['created_at'] = $item->refRegularDeliveryPlan->created_at;
            $res['created_by'] = $item->refRegularDeliveryPlan->created_by;
            $res['updated_at'] = $item->refRegularDeliveryPlan->updated_at;
            $res['updated_by'] = $item->refRegularDeliveryPlan->updated_by;
            $res['deleted_at'] = $item->refRegularDeliveryPlan->deleted_at;
            $res['uuid'] = $item->refRegularDeliveryPlan->uuid;
            $res['etd_ypmi'] = $item->refRegularDeliveryPlan->etd_ypmi;
            $res['etd_wh'] = $item->refRegularDeliveryPlan->etd_wh;
            $res['etd_jkt'] = $item->refRegularDeliveryPlan->etd_jkt;
            $res['is_inquiry'] = $item->refRegularDeliveryPlan->is_inquiry;
            $res['id_prospect_container'] = $item->refRegularDeliveryPlan->id_prospect_container;
            $res['id_prospect_container_creation'] = $item->refRegularDeliveryPlan->id_prospect_container_creation;
            $res['status_bml'] = $item->refRegularDeliveryPlan->status_bml;
            $res['cust_name'] = $item->refRegularDeliveryPlan->refConsignee->nick_name;
            $res['status_desc'] = 'Instock';
            $res['in_wh'] = $item->refRegularDeliveryPlan->item_no == null ? $in_wh : $item->qty;
            $res['box'] = $item->refRegularDeliveryPlan->item_no == null ? $in_wh . ' x 1 ' : $item->qty . ' x 1 ';

            return $res;
        });

        return [
            'items' => $data->items(),
            'last_page' => $data->lastPage()
        ];
    }

    public static function getCountBox($id)
    {
        $data = RegularDeliveryPlanBox::select('id_box', DB::raw('count(*) as jml'))
            ->where('id_regular_delivery_plan', $id)
            ->groupBy('id_box')
            ->get();
        return
            $data->map(function ($item) {
                $set['id'] = 0;
                $set['id_box'] = $item->id_box;
                $set['qty'] =  $item->refBox->qty . " x " . $item->jml . " box";
                $set['length'] =  "";
                $set['width'] =  "";
                $set['height'] =  "";
                return $set;
            });
    }

    public static function tracking($request)
    {
        $data = RegularStokConfirmation::where(function ($query) use ($request) {
            $category = $request->category ?? null;
            $kueri = $request->kueri ?? null;

            if ($category && $kueri) {
                if ($category == 'cust_name') {
                    $query->whereHas('refConsignee', function ($q) use ($kueri) {
                        $q->where('nick_name', 'like', '%' . $kueri . '%');
                    });
                } elseif ($category == 'item_name') {
                    $query->whereHas('refRegularDeliveryPlan.refPart', function ($q) use ($kueri) {
                        $q->where('description', 'like', '%' . $kueri . '%');
                    });
                } elseif ($category == 'item_no') {
                    $query->whereHas('refRegularDeliveryPlan', function ($q) use ($kueri) {
                        $q->where('item_no', 'like', '%' . str_replace('-', '', $kueri) . '%');
                    });
                } elseif ($category == 'order_no') {
                    $query->whereHas('refRegularDeliveryPlan', function ($q) use ($kueri) {
                        $q->where('order_no', 'like', '%' . $kueri . '%');
                    });
                } elseif ($category == 'cust_item_no') {
                    $query->whereHas('refRegularDeliveryPlan', function ($q) use ($kueri) {
                        $q->where('cust_item_no', 'like', '%' . $kueri . '%');
                    });
                } elseif ($category == 'etd_ypmi') {
                    $query->whereHas('refRegularDeliveryPlan', function ($q) use ($kueri) {
                        $q->where('etd_ypmi', 'like', '%' . $kueri . '%');
                    });
                } elseif ($category == 'etd_jkt') {
                    $query->whereHas('refRegularDeliveryPlan', function ($q) use ($kueri) {
                        $q->where('etd_jkt', 'like', '%' . $kueri . '%');
                    });
                } elseif ($category == 'etd_wh') {
                    $query->whereHas('refRegularDeliveryPlan', function ($q) use ($kueri) {
                        $q->where('etd_wh', 'like', '%' . $kueri . '%');
                    });
                }
            }

            // $filterdate = Helper::filterDate($params);
            $date_from = str_replace('-', '', $request->date_from);
            $date_to = str_replace('-', '', $request->date_to);
            if ($request->date_from || $request->date_to) $query->whereBetween('etd_jkt', [$date_from, $date_to]);
        })->paginate($request->limit ?? null);


        if (!$data) throw new \Exception("Data not found", 400);

        return [
            'items' => $data->getCollection()->transform(function ($item) {

                // if (Carbon::now() <= Carbon::parse($item->refRegularDeliveryPlan->etd_ypmi)) {
                //     if ($item->status_instock == 1 || $item->status_instock == 2 && $item->status_outstock == 1 || $item->status_outstock == 2 && $item->in_dc == 0 && $item->in_wh == 0) $status = 'In Process';
                //     if ($item->status_instock == 3 && $item->status_outstock == 3) $status = 'Finish Production';
                // } else {
                //     $status = 'Out Of Date';
                // }

                if (Carbon::now() <= Carbon::parse($item->refRegularDeliveryPlan->etd_ypmi)) {
                    if ($item->refRegularDeliveryPlan->qty !== $item->in_wh) $status = 'In Process';
                    if ($item->refRegularDeliveryPlan->qty == $item->in_wh) $status = 'Finish Production';
                } else {
                    $status = 'Out Of Date';
                }

                $deliv_plan_set = RegularDeliveryPlanSet::where('id_delivery_plan', $item->refRegularDeliveryPlan->id)->get()->pluck('item_no');
                $part_set = MstPart::whereIn('item_no', $deliv_plan_set->toArray())->get();
                $item_serial_set = [];
                $item_name_set = [];
                foreach ($part_set as $key => $value) {
                    $item_serial_set[] = $value->item_serial;
                    $item_name_set[] = $value->description;
                }

                if ($item->refRegularDeliveryPlan->item_no == null) {
                    $item_no_set = RegularDeliveryPlanSet::where('id_delivery_plan', $item->refRegularDeliveryPlan->id)->get()->pluck('item_no');
                    $mst_part = MstPart::select(
                        'mst_part.item_no',
                        DB::raw("string_agg(DISTINCT mst_part.description::character varying, ',') as description")
                    )
                        ->whereIn('mst_part.item_no', $item_no_set->toArray())
                        ->groupBy('mst_part.item_no')->get();
                    $item_name = [];
                    foreach ($mst_part as $value) {
                        $item_name[] = $value->description;
                    }

                    $mst_box = MstBox::whereIn('item_no', $item_no_set->toArray())
                        ->get()->map(function ($item) {
                            $qty = [
                                $item->item_no . '+' => $item->qty
                            ];

                            return array_merge($qty);
                        });

                    $order_entry_upload = RegularOrderEntryUpload::where('id_regular_order_entry', $item->refRegularDeliveryPlan->id_regular_order_entry)->first();
                    $upload_temp = RegularOrderEntryUploadDetailTemp::where('id_regular_order_entry_upload', $order_entry_upload->id)
                        ->whereIn('item_no', $item_no_set->toArray())
                        ->where('etd_jkt', $item->refRegularDeliveryPlan->etd_jkt)
                        ->get()->pluck('qty');
                    $qty_per_item_no = [];
                    foreach ($item_no_set as $key => $value) {
                        $qty_per_item_no[] = [
                            $value . '+' => $upload_temp->toArray()[$key]
                        ];
                    }

                    $qty = [];
                    foreach ($mst_box as $key => $value) {
                        $arary_key = array_keys($value)[0];
                        $qty[] = array_merge(...$qty_per_item_no)[$arary_key] / $value[$arary_key];
                    }

                    $box = [
                        'qty' =>  array_sum(array_merge(...$mst_box->toArray())) . " x " . (int)ceil(max($qty)) . " box",
                        'length' =>  "",
                        'width' =>  "",
                        'height' =>  "",
                    ];
                }

                $item->status_tracking = $status ?? null;
                $item->cust_name = $item->refRegularDeliveryPlan->refConsignee->nick_name;
                $item->item_no = $item->refRegularDeliveryPlan->item_no == null ? $item_serial_set : $item->refRegularDeliveryPlan->refPart->item_serial;
                $item->item_name = $item->refRegularDeliveryPlan->item_no == null ? $item_name_set : $item->refRegularDeliveryPlan->refPart->description;
                $item->cust_item_no = $item->refRegularDeliveryPlan->cust_item_no;
                $item->cust_order_no = $item->refRegularDeliveryPlan->order_no;
                $item->qty = $item->refRegularDeliveryPlan->qty;
                $item->etd_ypmi = $item->refRegularDeliveryPlan->etd_ypmi;
                $item->etd_wh = $item->refRegularDeliveryPlan->etd_wh;
                $item->etd_jkt = $item->refRegularDeliveryPlan->etd_jkt;
                $item->production = $item->production;
                $item->box = $item->refRegularDeliveryPlan->item_no == null ? $box : (self::getCountBox($item->refRegularDeliveryPlan->id)[0] ?? null);

                unset(
                    $item->refRegularDeliveryPlan,
                );

                return $item;
            }),
            'last_page' => $data->lastPage()
        ];
    }

    public static function editTrackingDate($request, $is_transaction = true)
    {
        if ($is_transaction) DB::beginTransaction();
        try {
            Helper::requireParams([
                'id',
                'etd_ypmi',
                'etd_wh',
                'etd_jkt'
            ]);

            $history = RegularStokConfirmationHistory::where('id_stock_confirmation', $request->id)->first();

            if (!$history) {
                throw new \Exception("Data not found in history", 400);
            }

            $confirmation = RegularStokConfirmation::find($history->id_stock_confirmation);
            if (!$confirmation) {
                throw new \Exception("Data not found in stock", 400);
            }

            $requestData = [
                'etd_ypmi' => Carbon::parse($request->etd_ypmi)->format('Ymd'),
                'etd_wh' => Carbon::parse($request->etd_wh)->format('Ymd'),
                'etd_jkt' => Carbon::parse($request->etd_jkt)->format('Ymd'),
            ];

            $confirmation->fill($requestData);
            $confirmation->refRegularDeliveryPlan()->update($requestData);
            $confirmation->save();

            if ($is_transaction) {
                DB::commit();
            }
        } catch (\Throwable $th) {
            if ($is_transaction) {
                DB::rollBack();
            }
            throw $th;
        }
    }


    public static function fixedQuantity($request)
    {
        $data = RegularFixedQuantityConfirmation::where('is_actual', Constant::IS_NOL)->paginate($request->limit ?? null);
        if (!$data) throw new \Exception("Data not found", 400);

        return [
            'items' => $data->items(),
            'last_page' => $data->lastPage()
        ];
    }

    public static function instockScanProcess($params, $is_transaction =  true)
    {
        if ($is_transaction) DB::beginTransaction();
        try {

            Helper::requireParams([
                'qr_code'
            ]);

            if (count(explode('-', $params->qr_code)) > 1) {
                $id = explode('-', $params->qr_code)[0];
                $qr_detail = explode('-', $params->qr_code)[1];
                $total_item = explode(' | ', $qr_detail)[0];

                $delivery_plan_box = RegularDeliveryPlanBox::find($id);
                if (!$delivery_plan_box) throw new \Exception("data not found", 400);
                $stock_confirmation = $delivery_plan_box->refRegularDeliveryPlan->refRegularStockConfirmation;
                if (!$stock_confirmation) throw new \Exception("stock has not arrived", 400);
                $data = RegularDeliveryPlanBox::where('id', $id)->paginate($params->limit ?? null);

                $data->transform(function ($item) use ($total_item, $params) {
                    $no = $item->refBox->no_box ?? null;
                    $qty = $item->refBox->qty ?? null;

                    $datasource = $item->refRegularDeliveryPlan->refRegularOrderEntry->datasource ?? null;
                    $part_no = $item->refRegularDeliveryPlan->manyDeliveryPlanSet->pluck('item_no')->toArray();
                    $qr_name = (string) Str::uuid() . '.png';
                    $qr_key = ($item->id . '-' . $total_item) . " | " . implode(',', $part_no) . " | " . $item->refRegularDeliveryPlan->order_no . " | " . $item->refRegularDeliveryPlan->refConsignee->nick_name . " | " . $item->lot_packing . " | " . date('d/m/Y', strtotime($item->packing_date)) . " | " . $item->qty_pcs_box;
                    QrCode::format('png')->generate($qr_key, storage_path() . '/app/qrcode/label/' . $qr_name);

                    $upd = RegularDeliveryPlanBox::where('id_regular_delivery_plan', $item->refRegularDeliveryPlan->id)
                        ->orderBy('qty_pcs_box', 'desc')
                        ->orderBy('id', 'asc')
                        ->get();

                    $qty_pcs_box = [];
                    foreach ($upd as $key => $val) {
                        if ($val->id === $item->id) {
                            for ($i = 0; $i < $total_item; $i++) {
                                $upd[$key + $i]->update([
                                    'qrcode' => $qr_name
                                ]);

                                $qty_pcs_box[] = $upd[$key + $i]->qty_pcs_box;
                            }
                        }
                    }

                    $deliv_plan_set = RegularDeliveryPlanSet::where('id_delivery_plan', $item->refRegularDeliveryPlan->id)->get()->pluck('item_no');
                    $part_set = MstPart::whereIn('item_no', $deliv_plan_set->toArray())->get();
                    $item_no_set = [];
                    $item_name_set = [];
                    foreach ($part_set as $key => $value) {
                        $item_no_set[] = $value->item_serial;
                        $item_name_set[] = $value->description;
                    }

                    $qty_pcs_box = array_sum($qty_pcs_box) / count(array_unique($item_no_set));

                    return [
                        'id' => $params->qr_code,
                        'item_name' => array_unique($item_name_set),
                        'cust_name' => $item->refRegularDeliveryPlan->refConsignee->nick_name ?? null,
                        'item_no' => array_unique($item_no_set),
                        'order_no' => $item->refRegularDeliveryPlan->order_no ?? null,
                        'qty_pcs_box' => $qty_pcs_box,
                        'namebox' => $no . " " . $qty . " pcs",
                        'qrcode' => route('file.download') . '?filename=' . $qr_name . '&source=qr_labeling',
                        'lot_packing' => $item->lot_packing,
                        'packing_date' => $item->packing_date,
                        'no_box' => $item->refBox->no_box ?? null,
                        'qr_name' => $qr_key
                    ];
                });
            } else {
                $key = explode('|', $params->qr_code);

                $id = str_replace(' ', '', $key[0]);

                $delivery_plan_box = RegularDeliveryPlanBox::find($id);
                if (!$delivery_plan_box) throw new \Exception("data not found", 400);
                $stock_confirmation = $delivery_plan_box->refRegularDeliveryPlan->refRegularStockConfirmation;
                if (!$stock_confirmation) throw new \Exception("stock has not arrived", 400);
                $data = RegularDeliveryPlanBox::where('id', $id)->paginate($params->limit ?? null);
                $data->transform(function ($item) {
                    $no = $item->refBox->no_box ?? null;
                    $qty = $item->refBox->qty ?? null;

                    $datasource = $item->refRegularDeliveryPlan->refRegularOrderEntry->datasource ?? null;

                    $qr_name = (string) Str::uuid() . '.png';
                    $qr_key = $item->id . " | " . $item->refRegularDeliveryPlan->item_no . " | " . $item->refRegularDeliveryPlan->order_no . " | " . $item->refRegularDeliveryPlan->refConsignee->nick_name . " | " . $item->lot_packing . " | " . date('d/m/Y', strtotime($item->packing_date)) . " | " . $item->qty_pcs_box;
                    QrCode::format('png')->generate($qr_key, storage_path() . '/app/qrcode/label/' . $qr_name);

                    $item->qrcode = $qr_name;
                    $item->save();

                    return [
                        'id' => $item->id,
                        'item_name' => $item->refRegularDeliveryPlan->refPart->description ?? null,
                        'cust_name' => $item->refRegularDeliveryPlan->refConsignee->nick_name ?? null,
                        'item_no' => $item->refRegularDeliveryPlan->item_no ?? null,
                        'order_no' => $item->refRegularDeliveryPlan->order_no ?? null,
                        'qty_pcs_box' => $item->qty_pcs_box,
                        'namebox' => $no . " " . $qty . " pcs",
                        'qrcode' => route('file.download') . '?filename=' . $qr_name . '&source=qr_labeling',
                        'lot_packing' => $item->lot_packing,
                        'packing_date' => $item->packing_date,
                        'no_box' => $item->refBox->no_box ?? null,
                        'qr_name' => $qr_key
                    ];
                });
            }

            return [
                'items' => $data[0],
                'last_page' => null
            ];
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public static function instockInquiryProcess($params, $is_transaction = true)
    {
        if ($is_transaction) DB::beginTransaction();
        try {

            Helper::requireParams([
                'id'
            ]);

            if (count(explode('-', $params->id)) > 1) {
                $id = explode('-', $params->id)[0];
                $qr_detail = explode('-', $params->id)[1];
                $total_item = explode(' | ', $qr_detail)[0];

                $delivery_plan_box = RegularDeliveryPlanBox::find($id);
                if (!$delivery_plan_box) throw new \Exception("Data not found", 400);

                $stock_confirmation_history = RegularStokConfirmationHistory::where('id_regular_delivery_plan_box', $delivery_plan_box->id)->whereIn('type', [Constant::INSTOCK, Constant::OUTSTOCK])->first();
                if ($stock_confirmation_history) throw new \Exception("QR Code Done Scan", 400);

                $check_scan = RegularStokConfirmationHistory::where('id_regular_delivery_plan', $delivery_plan_box->refRegularDeliveryPlan->id)->get()->pluck('id_regular_delivery_plan_box');
                $query = RegularDeliveryPlanBox::query();
                if (count($check_scan) > 1) {
                    $box = $query->where('id_regular_delivery_plan', $delivery_plan_box->refRegularDeliveryPlan->id)
                        ->whereNotIn('id', $check_scan->toArray())
                        ->whereNotNull('qrcode')
                        ->orderBy('qty_pcs_box', 'desc')
                        ->orderBy('id', 'asc')
                        ->get();
                } else {
                    $box = $query->where('id_regular_delivery_plan', $delivery_plan_box->refRegularDeliveryPlan->id)
                        ->whereNotNull('qrcode')
                        ->orderBy('qty_pcs_box', 'desc')
                        ->orderBy('id', 'asc')
                        ->get();
                }

                $qty_pcs_box = [];
                $id_plan_box = [];
                $id_box = [];
                foreach ($box as $key => $val) {
                    if ($val->id === $delivery_plan_box->id) {
                        for ($i = 0; $i < $total_item; $i++) {
                            $qty_pcs_box[] = $box[$key + $i]->qty_pcs_box;
                            $id_plan_box[] = $box[$key + $i]->id;
                            $id_box[] = $box[$key + $i]->id_box;
                        }
                    }
                }

                $deliv_plan_set = RegularDeliveryPlanSet::where('id_delivery_plan', $delivery_plan_box->refRegularDeliveryPlan->id)->get()->pluck('item_no');

                $qty_pcs_box_res = array_sum($qty_pcs_box) / count($deliv_plan_set);

                $stock_confirmation = $delivery_plan_box->refRegularDeliveryPlan->refRegularStockConfirmation;
                $qty = $stock_confirmation->qty;
                $status = $stock_confirmation->status;
                $in_stock_dc = $stock_confirmation->in_dc;
                $in_dc_total = $in_stock_dc + $qty_pcs_box_res;

                $stock_confirmation->in_dc = $in_dc_total;
                $stock_confirmation->production = $qty - $in_dc_total - $stock_confirmation->in_wh;
                $stock_confirmation->status_instock = $status == Constant::IS_ACTIVE ? 2 : 2;
                $stock_confirmation->save();

                $stokTemp = RegularStokConfirmationTemp::where('qr_key', $id . '-' . $total_item)->first();
                $stokTemp->update(['status_instock' => 2, 'is_reject' => null]);

                for ($i = 0; $i < $total_item; $i++) {
                    self::create([
                        'id_regular_delivery_plan' => $delivery_plan_box->id_regular_delivery_plan,
                        'id_regular_delivery_plan_box' => $id_plan_box[$i],
                        'id_stock_confirmation' => $stock_confirmation->id,
                        'id_box' => $id_box[$i],
                        'type' => 'INSTOCK',
                        'qty_pcs_perbox' => $qty_pcs_box[$i],
                    ]);
                }
            } else {
                $delivery_plan_box = RegularDeliveryPlanBox::find($params->id);
                if (!$delivery_plan_box) throw new \Exception("Data not found", 400);

                $stock_confirmation_history = RegularStokConfirmationHistory::where('id_regular_delivery_plan_box', $delivery_plan_box->id)->whereIn('type', [Constant::INSTOCK, Constant::OUTSTOCK])->first();
                if ($stock_confirmation_history) throw new \Exception("QR Code Done Scan", 400);

                $stock_confirmation = $delivery_plan_box->refRegularDeliveryPlan->refRegularStockConfirmation;
                $qty = $stock_confirmation->qty;
                $status = $stock_confirmation->status;
                $in_stock_dc = $stock_confirmation->in_dc;
                $in_dc_total = $in_stock_dc + $delivery_plan_box->qty_pcs_box;

                $stock_confirmation->in_dc = $in_dc_total;
                $stock_confirmation->production = $qty - $in_dc_total - $stock_confirmation->in_wh;
                $stock_confirmation->status_instock = $status == Constant::IS_ACTIVE ? 2 : 2;
                $stock_confirmation->save();

                $stokTemp = RegularStokConfirmationTemp::where('qr_key', $delivery_plan_box->id)->first();
                $stokTemp->update(['status_instock' => 2, 'is_reject' => null]);

                self::create([
                    'id_regular_delivery_plan' => $delivery_plan_box->id_regular_delivery_plan,
                    'id_regular_delivery_plan_box' => $delivery_plan_box->id,
                    'id_stock_confirmation' => $stock_confirmation->id,
                    'id_box' => $delivery_plan_box->id_box,
                    'type' => 'INSTOCK',
                    'qty_pcs_perbox' => $delivery_plan_box->qty_pcs_box,
                ]);
            }

            if ($is_transaction) DB::commit();
        } catch (\Throwable $th) {
            if ($is_transaction) DB::rollBack();
            throw $th;
        }
    }

    public static function outstockInquiryProcess($params, $is_transaction = true)
    {
        if ($is_transaction) DB::beginTransaction();
        try {

            Helper::requireParams([
                'id'
            ]);

            if (count(explode('-', $params->id)) > 1) {
                $id = explode('-', $params->id)[0];
                $qr_detail = explode('-', $params->id)[1];
                $total_item = explode(' | ', $qr_detail)[0];

                $delivery_plan_box = RegularDeliveryPlanBox::find($id);
                if (!$delivery_plan_box) throw new \Exception("Data not found", 400);

                $stock_confirmation_history = RegularStokConfirmationHistory::where('id_regular_delivery_plan_box', $delivery_plan_box->id)->where('type', Constant::OUTSTOCK)->first();
                if ($stock_confirmation_history) throw new \Exception("QR Code Done Scan", 400);

                $stock_confirmation_history_instock = RegularStokConfirmationHistory::where('id_regular_delivery_plan_box', $delivery_plan_box->id)->where('type', Constant::INSTOCK)->first();
                if (!$stock_confirmation_history_instock) throw new \Exception("QR Code Not In Instock Yet", 400);

                $check_scan = RegularStokConfirmationHistory::where('id_regular_delivery_plan', $delivery_plan_box->refRegularDeliveryPlan->id)->where('type', Constant::OUTSTOCK)->get()->pluck('id_regular_delivery_plan_box');
                $query = RegularDeliveryPlanBox::query();
                if (count($check_scan) > 1) {
                    $box = $query->where('id_regular_delivery_plan', $delivery_plan_box->refRegularDeliveryPlan->id)
                        ->whereNotIn('id', $check_scan->toArray())
                        ->whereNotNull('qrcode')
                        ->orderBy('qty_pcs_box', 'desc')
                        ->orderBy('id', 'asc')
                        ->get();
                } else {
                    $box = $query->where('id_regular_delivery_plan', $delivery_plan_box->refRegularDeliveryPlan->id)
                        ->whereNotNull('qrcode')
                        ->orderBy('qty_pcs_box', 'desc')
                        ->orderBy('id', 'asc')
                        ->get();
                }

                $qty_pcs_box = [];
                $id_plan_box = [];
                $id_box = [];
                foreach ($box as $key => $val) {
                    if ($val->id === $delivery_plan_box->id) {
                        for ($i = 0; $i < $total_item; $i++) {
                            $qty_pcs_box[] = $box[$key + $i]->qty_pcs_box;
                            $id_plan_box[] = $box[$key + $i]->id;
                            $id_box[] = $box[$key + $i]->id_box;
                        }
                    }
                }

                $deliv_plan_set = RegularDeliveryPlanSet::where('id_delivery_plan', $delivery_plan_box->refRegularDeliveryPlan->id)->get()->pluck('item_no');

                $qty_pcs_box_res = array_sum($qty_pcs_box) / count($deliv_plan_set);

                $stock_confirmation = $delivery_plan_box->refRegularDeliveryPlan->refRegularStockConfirmation;
                $qty = $stock_confirmation->qty;
                $status = $stock_confirmation->status;
                $in_stock_wh = $stock_confirmation->in_wh;
                $in_wh_total = $in_stock_wh + $qty_pcs_box_res;
                $in_dc_total = $stock_confirmation->in_dc - $qty_pcs_box_res;

                // $stock_confirmation->in_wh = $in_wh_total;
                // $stock_confirmation->in_dc = $in_dc_total;
                $stock_confirmation->status_outstock = $status == Constant::IS_ACTIVE ? 2 : 2;
                $stock_confirmation->save();

                $stokTemp = RegularStokConfirmationTemp::where('qr_key', $id . '-' . $total_item)->first();
                $stokTemp->update(['status_outstock' => 2, 'is_reject' => null]);

                for ($i = 0; $i < $total_item; $i++) {
                    self::create([
                        'id_regular_delivery_plan' => $delivery_plan_box->id_regular_delivery_plan,
                        'id_regular_delivery_plan_box' => $id_plan_box[$i],
                        'id_stock_confirmation' => $stock_confirmation->id,
                        'id_box' => $id_box[$i],
                        'type' => 'OUTSTOCK',
                        'qty_pcs_perbox' => $qty_pcs_box[$i],
                    ]);
                    RegularStokConfirmationHistory::where('id_regular_delivery_plan_box', $id_plan_box[$i])->where('type', Constant::INSTOCK)->first()->delete();
                }
            } else {
                $delivery_plan_box = RegularDeliveryPlanBox::find($params->id);
                if (!$delivery_plan_box) throw new \Exception("Data not found", 400);

                $stock_confirmation_history = RegularStokConfirmationHistory::where('id_regular_delivery_plan_box', $delivery_plan_box->id)->where('type', Constant::OUTSTOCK)->first();
                if ($stock_confirmation_history) throw new \Exception("QR Code Done Scan", 400);

                $stock_confirmation_history_instock = RegularStokConfirmationHistory::where('id_regular_delivery_plan_box', $delivery_plan_box->id)->where('type', Constant::INSTOCK)->first();
                if (!$stock_confirmation_history_instock) throw new \Exception("QR Code Not In Instock Yet", 400);

                $stock_confirmation = $delivery_plan_box->refRegularDeliveryPlan->refRegularStockConfirmation;
                $qty = $stock_confirmation->qty;
                $status = $stock_confirmation->status;
                $in_stock_wh = $stock_confirmation->in_wh;
                $in_wh_total = $in_stock_wh + $delivery_plan_box->qty_pcs_box;
                $in_dc_total = $stock_confirmation->in_dc - $delivery_plan_box->qty_pcs_box;
                // $stock_confirmation->in_wh = $in_wh_total;
                // $stock_confirmation->in_dc = $in_dc_total;
                $stock_confirmation->status_outstock = $status == Constant::IS_ACTIVE ? 2 : 2;
                $stock_confirmation->save();

                $stokTemp = RegularStokConfirmationTemp::where('qr_key', $delivery_plan_box->id)->first();
                $stokTemp->update(['status_outstock' => 2, 'is_reject' => null]);

                self::create([
                    'id_regular_delivery_plan' => $delivery_plan_box->id_regular_delivery_plan,
                    'id_regular_delivery_plan_box' => $delivery_plan_box->id,
                    'id_stock_confirmation' => $stock_confirmation->id,
                    'id_box' => $delivery_plan_box->id_box,
                    'type' => 'OUTSTOCK',
                    'qty_pcs_perbox' => $qty,
                ]);
                RegularStokConfirmationHistory::where('id_regular_delivery_plan_box', $delivery_plan_box->id)->where('type', Constant::INSTOCK)->first()->delete();
            }


            if ($is_transaction) DB::commit();
        } catch (\Throwable $th) {
            if ($is_transaction) DB::rollBack();
            throw $th;
        }
    }


    public static function outstockScanProcess($params, $is_transaction =  true)
    {
        if ($is_transaction) DB::beginTransaction();
        try {

            Helper::requireParams([
                'qr_code'
            ]);

            if (count(explode('-', $params->qr_code)) > 1) {
                $id = explode('-', $params->qr_code)[0];
                $total_item = explode('-', $params->qr_code)[1];

                $delivery_plan_box = RegularDeliveryPlanBox::find($id);
                if (!$delivery_plan_box) throw new \Exception("data not found", 400);
                $stock_confirmation = $delivery_plan_box->refRegularDeliveryPlan->refRegularStockConfirmation;
                if (!$stock_confirmation) throw new \Exception("stock has not arrived", 400);
                $data = RegularDeliveryPlanBox::where('id', $id)->paginate($params->limit ?? null);

                $data->transform(function ($item) use ($total_item, $params) {
                    $no = $item->refBox->no_box ?? null;
                    $qty = $item->refBox->qty ?? null;

                    $datasource = $item->refRegularDeliveryPlan->refRegularOrderEntry->datasource ?? null;
                    $part_no = $item->refRegularDeliveryPlan->manyDeliveryPlanSet->pluck('item_no')->toArray();

                    $qr_name = (string) Str::uuid() . '.png';
                    $qr_key = $item->id . " | " . implode(',', $part_no) . " | " . $item->refRegularDeliveryPlan->order_no . " | " . $item->refRegularDeliveryPlan->refConsignee->nick_name . " | " . $item->lot_packing . " | " . date('d/m/Y', strtotime($item->packing_date)) . " | " . $item->qty_pcs_box;
                    QrCode::format('png')->generate($qr_key, storage_path() . '/app/qrcode/label/' . $qr_name);

                    $upd = RegularDeliveryPlanBox::where('id_regular_delivery_plan', $item->refRegularDeliveryPlan->id)
                        ->orderBy('qty_pcs_box', 'desc')
                        ->orderBy('id', 'asc')
                        ->get();

                    $qty_pcs_box = [];
                    foreach ($upd as $key => $val) {
                        if ($val->id === $item->id) {
                            for ($i = 0; $i < $total_item; $i++) {
                                $upd[$key + $i]->update([
                                    'qrcode' => $qr_name
                                ]);

                                $qty_pcs_box[] = $upd[$key + $i]->qty_pcs_box;
                            }
                        }
                    }

                    $deliv_plan_set = RegularDeliveryPlanSet::where('id_delivery_plan', $item->refRegularDeliveryPlan->id)->get()->pluck('item_no');
                    $part_set = MstPart::whereIn('item_no', $deliv_plan_set->toArray())->get();
                    $item_no_set = [];
                    $item_name_set = [];
                    foreach ($part_set as $key => $value) {
                        $item_no_set[] = $value->item_serial;
                        $item_name_set[] = $value->description;
                    }

                    $qty_pcs_box = array_sum($qty_pcs_box) / count(array_unique($item_no_set));

                    return [
                        'id' => $params->qr_code,
                        'item_name' => array_unique($item_name_set),
                        'cust_name' => $item->refRegularDeliveryPlan->refConsignee->nick_name ?? null,
                        'item_no' => array_unique($item_no_set),
                        'order_no' => $item->refRegularDeliveryPlan->order_no ?? null,
                        'qty_pcs_box' => $qty_pcs_box,
                        'namebox' => $no . " " . $qty . " pcs",
                        'qrcode' => route('file.download') . '?filename=' . $qr_name . '&source=qr_labeling',
                        'lot_packing' => $item->lot_packing,
                        'packing_date' => $item->packing_date,
                        'no_box' => $item->refBox->no_box ?? null,
                    ];
                });
            } else {
                $key = explode('|', $params->qr_code);

                $id = str_replace(' ', '', $key[0]);

                $delivery_plan_box = RegularDeliveryPlanBox::find($id);
                if (!$delivery_plan_box) throw new \Exception("data not found", 400);
                $stock_confirmation = $delivery_plan_box->refRegularDeliveryPlan->refRegularStockConfirmation;
                if (!$stock_confirmation) throw new \Exception("stock has not arrived", 400);
                $data = RegularDeliveryPlanBox::where('id', $id)->paginate($params->limit ?? null);
                $data->transform(function ($item) {
                    $no = $item->refBox->no_box ?? null;
                    $qty = $item->refBox->qty ?? null;

                    $datasource = $item->refRegularDeliveryPlan->refRegularOrderEntry->datasource ?? null;

                    $qr_name = (string) Str::uuid() . '.png';
                    $qr_key = $item->id . " | " . $item->refRegularDeliveryPlan->item_no . " | " . $item->refRegularDeliveryPlan->order_no . " | " . $item->refRegularDeliveryPlan->refConsignee->nick_name . " | " . $item->lot_packing . " | " . date('d/m/Y', strtotime($item->packing_date)) . " | " . $item->qty_pcs_box;
                    QrCode::format('png')->generate($qr_key, storage_path() . '/app/qrcode/label/' . $qr_name);

                    $item->qrcode = $qr_name;
                    $item->save();

                    return [
                        'id' => $item->id,
                        'item_name' => $item->refRegularDeliveryPlan->refPart->description ?? null,
                        'cust_name' => $item->refRegularDeliveryPlan->refConsignee->nick_name ?? null,
                        'item_no' => $item->refRegularDeliveryPlan->item_no ?? null,
                        'order_no' => $item->refRegularDeliveryPlan->order_no ?? null,
                        'qty_pcs_box' => $item->qty_pcs_box,
                        'namebox' => $no . " " . $qty . " pcs",
                        'qrcode' => route('file.download') . '?filename=' . $qr_name . '&source=qr_labeling',
                        'lot_packing' => $item->lot_packing,
                        'packing_date' => $item->packing_date,
                        'no_box' => $item->refBox->no_box ?? null,
                    ];
                });
            }

            return [
                'items' => $data[0],
                'last_page' => null
            ];
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public static function instockSubmit($params, $is_transaction = true)
    {
        if ($is_transaction) DB::beginTransaction();
        try {

            Helper::requireParams([
                'id'
            ]);

            $data = RegularStokConfirmation::whereIn('id', $params->id)->get()->map(function ($item) {
                $item->status_instock = 3;
                $item->save();
                return $item;
            });

            if ($is_transaction) DB::commit();
        } catch (\Throwable $th) {
            if ($is_transaction) DB::rollBack();
            throw $th;
        }
    }

    public static function outstockSubmit($params, $is_transaction = true)
    {
        if ($is_transaction) DB::beginTransaction();
        try {

            Helper::requireParams([
                'id'
            ]);

            $stokTemp = RegularStokConfirmationTemp::whereIn('qr_key', $params->id)->get();
            $id_stock_confirmation = [];
            foreach ($stokTemp as $key => $value) {
                $id_stock_confirmation[] = $value->id_stock_confirmation;
                $update = RegularStokConfirmationTemp::where('id', $value->id)->first();
                // $update->update(['status_outstock' => 3]);
            }

            $data = RegularStokConfirmation::whereIn('id', $id_stock_confirmation)->get()->map(function ($item) {
                // $item->status_outstock = 3;
                // $item->save();
                return $item;
            });

            if ($is_transaction) DB::commit();
        } catch (\Throwable $th) {
            if ($is_transaction) DB::rollBack();
            throw $th;
        }
    }

    public static function outstockDeliveryNote($request)
    {
        $stokTemp = RegularStokConfirmationTemp::whereIn('qr_key', $request->id_stock_confirmation)->get();
        $id_stock_confirmation = [];
        foreach ($stokTemp as $key => $value) {
            $id_stock_confirmation[] = $value->id_stock_confirmation;
        }
        $data = RegularStokConfirmation::select(
            DB::raw("string_agg(DISTINCT regular_stock_confirmation.id::character varying, ',') as id_stock_confirmation"),
            DB::raw("string_agg(DISTINCT regular_stock_confirmation.id_regular_delivery_plan::character varying, ',') as id_regular_delivery_plan"),
            DB::raw("string_agg(DISTINCT d.nick_name::character varying, ',') as username"),
        )
            ->whereIn('regular_stock_confirmation.id', $id_stock_confirmation)
            ->join('regular_delivery_plan as a', 'a.id', 'regular_stock_confirmation.id_regular_delivery_plan')
            ->join('mst_consignee as d', 'd.code', 'a.code_consignee')
            ->paginate($request->limit ?? null);

        if (!$data) throw new \Exception("Data not found", 400);

        return [
            'items' => $data->transform(function ($item) {
                $item->shipment = MstShipment::where('is_active', Constant::IS_ACTIVE)->first()->shipment ?? null;
                $item->truck_no = null;
                $item->surat_jalan = Helper::generateCodeLetter(RegularStokConfirmationOutstockNote::latest()->first()) ?? null;
                $item->delivery_date = Carbon::now()->format('Y-m-d');

                return $item;
            })[0],
            'last_page' => $data->lastPage()
        ];
    }

    public static function outstockDeliveryNoteItems($request)
    {
        $items = RegularStokConfirmationTemp::select(
            'id_stock_confirmation',
            'qty',
            DB::raw("string_agg(DISTINCT regular_stock_confirmation_temp.id_regular_delivery_plan::character varying, ',') as id_regular_delivery_plan"),
            DB::raw("string_agg(DISTINCT regular_stock_confirmation_temp.id::character varying, ',') as id_stok_temp"),
        )
            ->whereIn('qr_key', $request->id_stock_confirmation)
            ->groupBy('id_stock_confirmation', 'qty')
            ->get();

        $items->transform(function ($item) use ($items) {

            $plan_box = RegularDeliveryPlanBox::where('id_regular_delivery_plan', $item->id_regular_delivery_plan)->orderBy('qty_pcs_box', 'desc')->orderBy('id', 'asc')->get();

            if ($item->refRegularDeliveryPlan->item_no == null) {
                $plan_set = RegularDeliveryPlanSet::where('id_delivery_plan', $item->id_regular_delivery_plan)->get()->pluck('item_no');
                $check_scan = RegularStokConfirmationHistory::where('id_regular_delivery_plan', $item->id_regular_delivery_plan)->where('type', 'OUTSTOCK')->get()->pluck('id_regular_delivery_plan_box');

                $mst_box = MstBox::where('part_set', 'set')->whereIn('item_no', $plan_set->toArray())->get();
                $sum_qty = [];
                foreach ($mst_box as $key => $value_box) {
                    $sum_qty[] = $value_box->qty;
                }

                $result_qty = [];
                $result_id_planbox = [];
                $result_arr = [];
                $qty = 0;
                $group_qty = [];
                $group_id_planbox = [];
                $group_arr = [];
                foreach ($plan_box as $key => $val) {
                    $qty += $val->qty_pcs_box;
                    if (in_array($val->id, $check_scan->toArray())) {
                        $group_qty[] = $val->qty_pcs_box;
                        $group_id_planbox[] = $val->id;
                    }

                    if ($qty >= (array_sum($sum_qty) * count($plan_set->toArray()))) {
                        $result_qty[] = $group_qty;
                        $result_id_planbox[] = $group_id_planbox;
                        $result_arr[] = $group_arr[0] ?? [];
                        $qty = 0;
                        $group_qty = [];
                        $group_id_planbox = [];
                        $group_arr = [];
                    }
                }

                if (!empty($group_qty)) {
                    $result_qty[] = $group_qty;
                }
                if (!empty($group_id_planbox)) {
                    $result_id_planbox[] = $group_id_planbox;
                }
                if (!empty($group_arr)) {
                    $result_arr[] = $group_arr[0];
                }

                for ($i = 0; $i < count($result_qty); $i++) {
                    if (count($result_qty[$i]) !== 0) {
                        $in_wh = (array_sum($result_qty[$i]) / count($plan_set->toArray()));
                    }
                }

                $mst_part = MstPart::whereIn('item_no', $plan_set->toArray())->get();
                $item_no = [];
                $item_name = [];
                foreach ($mst_part as $key => $value) {
                    $item_no[] = $value->item_no;
                    $item_name[] = $value->description;
                }
            } else {
                $mst_part = MstPart::where('item_no', $item->refRegularDeliveryPlan->item_no)->first();
                $item_no = $mst_part->item_no;
                $item_name = $mst_part->description;
            }

            $item->item_number = $item_no;
            $item->item_name = $item_name;
            $item->qty = $item->refRegularDeliveryPlan->item_no == null ? count(explode(',', $item->id_stok_temp)) . ' x ' . $in_wh : count(explode(',', $item->id_stok_temp)) . ' x ' . $item->qty;
            $item->order_no = $item->refRegularDeliveryPlan->order_no;
            $item->cust_name = $item->refRegularDeliveryPlan->refConsignee->nick_name;

            unset(
                $item->refRegularDeliveryPlan
            );

            return $item;
        });

        return [
            'items' => $items ?? []
        ];
    }
}
