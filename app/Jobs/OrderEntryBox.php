<?php

namespace App\Jobs;

use App\Models\RegularOrderEntryUploadDetail;
use App\Models\RegularOrderEntryUploadDetailBox;
use App\Query\QueryMstBox;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
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
            RegularOrderEntryUploadDetail::where([
                'id_regular_order_entry_upload' => $params['id_regular_order_entry_upload']
            ])
            ->each(function ($item){
                $request = $item->toArray();
                $box = QueryMstBox::byItemNoCdConsignee($request['item_no'],$request['code_consignee']);
                if($box) {
                    $box = $box->toArray();
                    $box_capacity = $box['qty'];
                    $qty = $request['qty'];
                    $loops = (int) ceil($qty / $box_capacity);
                    $ext = [];
                    for ($i=0; $i < $loops ; $i++) {
                        $ext[] = [
                            'uuid' => (string) Str::uuid(),
                            'id_regular_order_entry_upload_detail' => $request['id'],
                            'id_regular_order_entry_upload_detail' => $request['uuid'],
                            'id_box' => $box['id'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
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
