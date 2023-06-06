<?php

namespace App\Jobs;

use App\Models\RegularDeliveryPlanBox;
use App\Models\RegularOrderEntryUploadDetail;
use App\Models\RegularOrderEntryUploadDetailBox;
use App\Models\RegularProspectContainer;
use App\Models\RegularProspectContainerCreation;
use App\Query\QueryMstBox;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ContainerPlan implements ShouldQueue
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
            $arrSummaryBox = RegularProspectContainerCreation::where('id_prospect_container', $params->id)
                ->orderBy('id', 'asc')
                ->get()
                ->map(function ($item){
                    return $item->summary_box;
                });
            $iteration = 1;
            $index = 1;
            $countSummaryBox = count($arrSummaryBox);
            $counter = 0;
            foreach ($params->colis as $value) {
                foreach ($value['box'] as $val) {
                    $id_prop = RegularProspectContainerCreation::where('id_prospect_container', $params->id)
                        ->where('iteration', $iteration)
                        ->orderBy('id', 'asc')
                        ->first();

                    $fill = RegularDeliveryPlanBox::find($val['id']);
                    $fill->id_prospect_container_creation = $id_prop->id;
                    $fill->save();

                    if($counter < $countSummaryBox) {
                        if ($index == $arrSummaryBox[$counter]) {
                            $iteration = $iteration + 1;
                            $counter = $counter + 1;
                        }
                    }
                    $index = $index + 1;
                }
            }

            $upd = RegularProspectContainer::find($params->id);
            $upd->is_prospect = 1;
            $upd->save();

        } catch (\Throwable $th) {
            Log::debug('jobs-container-plan'.json_encode($th->getMessage()));
        }
    }
}
