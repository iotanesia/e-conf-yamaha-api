<?php

namespace App\Jobs;

use App\Models\RegularOrderEntryUploadDetail;
use App\Models\RegularOrderEntryUploadDetailBox;
use App\Models\RegularOrderEntryUploadDetailSet;
use App\Models\RegularOrderEntryUploadDetailTemp;
use App\Query\QueryMstBox;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OrderEntryDetail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

     private $params;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($params)
    {
        $this->params = $params;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
       try {
            $params = $this->params;

            //upload temp ke detail
            $data = RegularOrderEntryUploadDetailTemp::
            select('regular_order_entry_upload_detail_temp.etd_jkt','a.id_box','a.part_set',
            DB::raw("string_agg(DISTINCT regular_order_entry_upload_detail_temp.id::character varying, ',') as id_regular_order_entry_upload_detail_temp"),
            DB::raw("string_agg(DISTINCT regular_order_entry_upload_detail_temp.code_consignee::character varying, ',') as code_consignee"),
            DB::raw("string_agg(DISTINCT regular_order_entry_upload_detail_temp.item_no::character varying, ',') as item_no"),
            DB::raw("string_agg(DISTINCT regular_order_entry_upload_detail_temp.id_regular_order_entry_upload::character varying, ',') as id_regular_order_entry_upload"),
            DB::raw("string_agg(DISTINCT regular_order_entry_upload_detail_temp.model::character varying, ',') as model"),
            DB::raw("string_agg(DISTINCT regular_order_entry_upload_detail_temp.disburse::character varying, ',') as disburse"),
            DB::raw("string_agg(DISTINCT regular_order_entry_upload_detail_temp.delivery::character varying, ',') as delivery"),
            DB::raw("string_agg(DISTINCT regular_order_entry_upload_detail_temp.qty::character varying, ',') as qty"),
            DB::raw("string_agg(DISTINCT regular_order_entry_upload_detail_temp.status::character varying, ',') as status"),
            DB::raw("string_agg(DISTINCT regular_order_entry_upload_detail_temp.order_no::character varying, ',') as order_no"),
            DB::raw("string_agg(DISTINCT regular_order_entry_upload_detail_temp.cust_item_no::character varying, ',') as cust_item_no"),
            DB::raw("string_agg(DISTINCT regular_order_entry_upload_detail_temp.uuid::character varying, ',') as uuid"),
            DB::raw("string_agg(DISTINCT regular_order_entry_upload_detail_temp.etd_wh::character varying, ',') as etd_wh"),
            DB::raw("string_agg(DISTINCT regular_order_entry_upload_detail_temp.etd_ypmi::character varying, ',') as etd_ypmi"),
            DB::raw("string_agg(DISTINCT a.item_no_series::character varying, ',') as item_no_series"),
            DB::raw("SUM(regular_order_entry_upload_detail_temp.qty) as sum_qty")
            )
            ->where('id_regular_order_entry_upload', $params['id_regular_order_entry_upload'])
            ->leftJoin('mst_box as a','regular_order_entry_upload_detail_temp.item_no','a.item_no')
            ->groupBy('a.part_set','a.id_box','regular_order_entry_upload_detail_temp.etd_jkt')
            ->orderBy('id_regular_order_entry_upload_detail_temp','asc')
            ->get();

            $set = [];
            $single = [];
            foreach ($data->toArray() as $value) {
                if ($value['part_set'] == 'set') {
                    $set[] = $value;
                } elseif ($value['part_set'] == 'single') {
                    $single[] = $value;
                }
            }

            $id_set = [];
            foreach ($set as $value) {
                $id_set[] = $value['id_regular_order_entry_upload_detail_temp'];
            }

            $single_upload = [];
            foreach ($single as $value) {
                if (!str_contains(implode(',',$id_set),$value['id_regular_order_entry_upload_detail_temp'])) {
                    $single_upload[] = $value;
                }
            }

            foreach ($set as $value) {
                $upload_detail = RegularOrderEntryUploadDetail::create([
                    'id_regular_order_entry_upload' => $value['id_regular_order_entry_upload'],
                    'code_consignee' => $value['code_consignee'],
                    'delivery' => $value['delivery'],
                    'qty' => $value['sum_qty'],
                    'status' => $value['status'],
                    'order_no' => $value['order_no'],
                    'cust_item_no' => $value['cust_item_no'],
                    'etd_wh' => $value['etd_wh'],
                    'etd_ypmi' => $value['etd_ypmi'],
                    'etd_jkt' => $value['etd_jkt'],
                    'uuid' => $value['uuid'],
                    'jenis' => 'set'
                ]);

                foreach (explode(',',$value['item_no']) as $key => $value) {
                    if (count(explode(',',$value['qty'])) == 1) {
                        $qty = explode(',',$value['qty'])[0];
                    } else {
                        $qty = explode(',',$value['qty'])[$key];
                    }
                    RegularOrderEntryUploadDetailSet::create([
                        'id_detail' => $upload_detail->id,
                        'item_no' => $value,
                        'id_regular_order_entry' => $upload_detail->refRegularOrderEntryUpload->id_regular_order_entry,
                        'qty' => $qty
                    ]);
                }
            }

            foreach ($single_upload as $value) {
                $upload = RegularOrderEntryUploadDetail::create($value);
                $upload->update(['jenis' => 'single']);
            }

       } catch (\Throwable $th) {
            Log::debug('jobs-insert-to-box'.json_encode($th->getMessage()));
       }
    }
}
