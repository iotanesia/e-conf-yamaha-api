<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\RegularProspectContainerCreation;
use App\Models\RegularDeliveryPlanBox;
use App\Models\RegularProspectContainer;

class ContainerPlan implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $params;

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
        //distributed data algorithm
        $params = $this->params;
        $arrSummaryBox = RegularProspectContainerCreation::where('id_prospect_container', $params['id'])
            ->orderBy('id', 'asc')
            ->get()
            ->map(function ($item){
                return $item->summary_box;
            });
        
        $iteration = $params['type'] == 'set' ? 100 : 1;
        $index = 1;
        $countSummaryBox = count($arrSummaryBox);
        $counter = 0;
        foreach ($params['colis'] as $value) {
            foreach ($value['box'] as $val) {
                $fill = RegularDeliveryPlanBox::find($val['id']);
                if ($fill->id_prospect_container_creation == null) {

                    // if ($fill->refRegularDeliveryPlan->item_no == null) {
                    //     $persentase = max($arrSummaryBox->toArray()) / array_sum($arrSummaryBox->toArray());
                    //     $jml_box_update = (int)($params['box_set_count'] * $persentase);
                    //     $box_update = RegularDeliveryPlanBox::where('id_regular_delivery_plan', $fill->refRegularDeliveryPlan->id)
                    //                                         ->whereNull('id_prospect_container_creation')->get();
                    //     $sisa = count($box_update) - $jml_box_update;
                        
                    //     for ($i=0; $i < $countSummaryBox; $i++) { 
                    //         foreach ($box_update->take($jml_box_update) as $value_box) {
                    //             $id_prop = RegularProspectContainerCreation::where('id_prospect_container', $params['id'])
                    //                                                         ->where('iteration', ($i+100))
                    //                                                         ->orderBy('id', 'asc')
                    //                                                         ->first();
                                
                    //             $value_box->update(['id_prospect_container_creation' => $id_prop->id]);
                    //         }
                    //         $jml_box_update = $sisa;
                    //     }
                    // } else {
                        $id_prop = RegularProspectContainerCreation::where('id_prospect_container', $params['id'])
                                                                    ->where('iteration', $iteration)
                                                                    ->orderBy('id', 'asc')
                                                                    ->first();

                        $fill = RegularDeliveryPlanBox::find($val['id']);
                        $fill->id_prospect_container_creation = $id_prop->id;
                        $fill->is_labeling = 1;
                        $fill->save();

                        if($counter < $countSummaryBox) {
                            if ($index == $arrSummaryBox[$counter]) {
                                $iteration = $iteration + 1;
                                $counter = $counter + 1;
                            }
                        }
                        $index = $index + 1;
                    }
                // }
            }
        }

        $upd = RegularProspectContainer::find($params['id']);
        $upd->is_prospect = 1;
        $upd->save();
    }
}
