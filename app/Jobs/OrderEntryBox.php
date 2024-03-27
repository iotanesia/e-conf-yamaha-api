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

class OrderEntryBox implements ShouldQueue
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

            //upload detail ke box
            RegularOrderEntryUploadDetail::where([
                'id_regular_order_entry_upload' => $params['id_regular_order_entry_upload']
            ])
            ->each(function ($item){
                $datasource = $item->refRegularOrderEntryUploadDetail->refRegularOrderEntryUpload->refRegularOrderEntry->datasource;
                $request = $item->toArray();

                $detail_set = RegularOrderEntryUploadDetailSet::where('id_detail', $request['id'])->get();
                if (count($detail_set) > 0) {
                    foreach ($detail_set as $key => $value) {
                        $box = QueryMstBox::byItemNoCdConsigneeDatasourceSet($value->item_no,$request['code_consignee'],$datasource);
                        if($box) {
                            $box = $box->toArray();
                            $box_capacity = $box['qty'];
                            $qty = $request['qty'];
                            $loops = (int) ceil($qty / $box_capacity);
                            $ext = [];
                            for ($i=0; $i < $loops ; $i++) {
                                if($qty > $box_capacity)
                                    $qty_pcs_box = $box_capacity;
                                else
                                    $qty_pcs_box = $qty;
                                $ext[] = [
                                    'uuid' => (string) Str::uuid(),
                                    'id_regular_order_entry_upload_detail' => $request['id'],
                                    'uuid_regular_order_entry_upload_detail' => $request['uuid'],
                                    'id_box' => $box['id'],
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                    'qty_pcs_box' => $qty_pcs_box
                                ];
                                $sum = $qty - $box_capacity;
                                $qty = $sum;
                            }

                            foreach (array_chunk($ext,10000) as $chunk) {
                                RegularOrderEntryUploadDetailBox::insert($chunk);
                            }
                        }
                    }
                } 

                $box = QueryMstBox::byItemNoCdConsigneeDatasourceSet($request['item_no'],$request['code_consignee'],$datasource);
                if($box) {
                    $box = $box->toArray();
                    $box_capacity = $box['qty'];
                    $qty = $request['qty'];
                    $loops = (int) ceil($qty / $box_capacity);
                    $ext = [];
                    for ($i=0; $i < $loops ; $i++) {
                        if($qty > $box_capacity)
                            $qty_pcs_box = $box_capacity;
                        else
                            $qty_pcs_box = $qty;
                        $ext[] = [
                            'uuid' => (string) Str::uuid(),
                            'id_regular_order_entry_upload_detail' => $request['id'],
                            'uuid_regular_order_entry_upload_detail' => $request['uuid'],
                            'id_box' => $box['id'],
                            'created_at' => now(),
                            'updated_at' => now(),
                            'qty_pcs_box' => $qty_pcs_box
                        ];
                        $sum = $qty - $box_capacity;
                        $qty = $sum;
                    }

                    foreach (array_chunk($ext,10000) as $chunk) {
                        RegularOrderEntryUploadDetailBox::insert($chunk);
                    }
                }
                 
            });
       } catch (\Throwable $th) {
            Log::debug('jobs-insert-to-box'.json_encode($th->getMessage()));
       }
    }
}
