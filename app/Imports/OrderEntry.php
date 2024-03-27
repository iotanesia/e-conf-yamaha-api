<?php

namespace App\Imports;

use App\Jobs\OrderEntryBox;
use App\Jobs\OrderEntryBoxSet;
use App\Jobs\OrderEntryDetail;
use App\Models\MstBox;
use App\Models\MstConsignee;
use App\Models\MstPart;
use App\Query\QueryMstBox;
use App\Query\QueryRegularOrderEntryUpload;
use App\Query\QueryRegularOrderEntryUploadDetail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterImport;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Validation\Rule;

class OrderEntry implements ToCollection, WithChunkReading, WithStartRow, WithMultipleSheets, WithEvents, ShouldQueue
{

    use RegistersEventListeners;

    private $id_regular_order_entry_upload;
    private $params;

    public function __construct($id_regular_order_entry_upload,$params)
    {
        $this->id_regular_order_entry_upload = $id_regular_order_entry_upload;
        $this->params = $params;
    }

    public function sheets(): array
    {
        return [
            0 => $this,
        ];
    }
      /**
     * @param array $row
     *
     * @return User|null
     */
    public function collection(Collection $collection)
    {
        try {
            $id_regular_order_entry_upload = $this->id_regular_order_entry_upload;
            $ext = [];
            foreach ($collection->chunk(10000) as $i => $chunk) {
                $filteredData = collect($chunk)->filter(function ($row){
                    $fillter_yearmonth = $this->params['year'].$this->params['month'];
                    $deliver_yearmonth = Carbon::parse(trim($row[16]))->format('Ym'); // etd_jkt
                    // return in_array($row[7],['940E']) && $fillter_yearmonth == $deliver_yearmonth;
                    return $fillter_yearmonth == $deliver_yearmonth && trim($row[16]) !== "";
                });

                //check mst part
                $mst_part_false =  $filteredData->map(function ($row) use ($id_regular_order_entry_upload) {
                    // $cust_item_no = trim(substr_replace($row[5],'',12)) == trim($row[23])
                    //     ? '999999-9999'
                    //     : trim(substr_replace($row[23],'-',6).substr($row[23],6));
                    $consignee = MstConsignee::where('nick_name', trim($row[1]))->first();
                    $check = MstPart::where('item_no',(trim($row[10]).trim($row[11])))->first() ? null : [
                        'id_regular_order_entry_upload' => $id_regular_order_entry_upload,
                        'code_consignee' => $consignee == null ? null : $consignee->code,
                        // 'model' => trim($row[4]),
                        'item_no' => (trim($row[10]).trim($row[11])),
                        // 'disburse' => trim($row[12]),
                        'delivery' => trim($row[16]),
                        'etd_jkt' => trim($row[16]),
                        'etd_wh' => Carbon::parse(trim($row[16]))->subDays(2)->format('Ymd'),
                        'etd_ypmi' => Carbon::parse(trim($row[16]))->subDays(4)->format('Ymd'),
                        'qty' => (int)trim($row[17]),
                        'status' => 'fixed',
                        'order_no' => substr(trim($row[0]),0,-2),
                        'cust_item_no' => (trim($row[7])."-".trim($row[8])),
                        'uuid' => (string) Str::uuid(),
                        'created_at' => now(),
                        'updated_at' => now(),
                        'keterangan' => 'Part tidak terdaftar pada Master Part'
                    ]; // check mst part
                    return $check;
                })->toArray();

                $filter_mst_part_false = array_filter($mst_part_false);
                if(count($filter_mst_part_false) > 0) {
                    array_map(function ($item){
                        DB::table('regular_order_entry_upload_detail_revision')->insert($item);
                    },$filter_mst_part_false);
                }

                //check mst box
                $mst_box_false =  $filteredData->map(function ($row) use ($id_regular_order_entry_upload) {
                    // $cust_item_no = trim(substr_replace($row[5],'',12)) == trim($row[23])
                    //     ? '999999-9999'
                    //     : trim(substr_replace($row[23],'-',6).substr($row[23],6));
                    
                    $consignee = MstConsignee::where('nick_name', trim($row[1]))->first();
                    $check_consignee = $consignee == null ? null : $consignee->code;
                    $check = MstBox::where('item_no',(trim($row[10]).trim($row[11])))->where('code_consignee', $check_consignee)->first() ? null : [
                        'id_regular_order_entry_upload' => $id_regular_order_entry_upload,
                        'code_consignee' => $consignee == null ? null : $consignee->code,
                        // 'model' => trim($row[4]),
                        'item_no' => (trim($row[10]).trim($row[11])),
                        // 'disburse' => trim($row[12]),
                        'delivery' => trim($row[16]),
                        'etd_jkt' => trim($row[16]),
                        'etd_wh' => Carbon::parse(trim($row[16]))->subDays(2)->format('Ymd'),
                        'etd_ypmi' => Carbon::parse(trim($row[16]))->subDays(4)->format('Ymd'),
                        'qty' => (int)trim($row[17]),
                        'status' => 'fixed',
                        'order_no' => substr(trim($row[0]),0,-2),
                        'cust_item_no' => (trim($row[7])."-".trim($row[8])),
                        'uuid' => (string) Str::uuid(),
                        'created_at' => now(),
                        'updated_at' => now(),
                        'keterangan' => 'Part tidak terdaftar pada Master Box'
                    ]; // check mst box
                    return $check;
                })->toArray();

                $filter_mst_box_false = array_filter($mst_box_false);
                if(count($filter_mst_box_false) > 0) {
                    array_map(function ($item){
                        DB::table('regular_order_entry_upload_detail_revision')->insert($item);
                    },$filter_mst_box_false);
                }

                if(count($filter_mst_part_false) == 0 && count($filter_mst_box_false) == 0) {
                    $filteredData->each(function ($row) use ($id_regular_order_entry_upload) {
                        // $cust_item_no = trim(substr_replace($row[5],'',12)) == trim($row[23])
                        //     ? '999999-9999'
                        //     : trim(substr_replace($row[23],'-',6).substr($row[23],6));
                        
                        $consignee = MstConsignee::where('nick_name', trim($row[1]))->first();
                        QueryRegularOrderEntryUploadDetail::created([
                            'id_regular_order_entry_upload' => $id_regular_order_entry_upload,
                            'code_consignee' => $consignee == null ? null : $consignee->code,
                            // 'model' => trim($row[4]),
                            'item_no' => (trim($row[10]).trim($row[11])),
                            // 'disburse' => trim($row[12]),
                            'delivery' => trim($row[16]),
                            'etd_jkt' => trim($row[16]),
                            'etd_wh' => Carbon::parse(trim($row[16]))->subDays(2)->format('Ymd'),
                            'etd_ypmi' => Carbon::parse(trim($row[16]))->subDays(4)->format('Ymd'),
                            'qty' => (int)trim($row[17]),
                            'status' => 'fixed',
                            'order_no' => substr(trim($row[0]),0,-2),
                            'cust_item_no' => (trim($row[7])."-".trim($row[8])),
                            'uuid' => (string) Str::uuid(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    });
                }
            }

            // dd($ext);

            Log::info('Process finish');
        } catch (\Throwable $th) {
            Log::debug($th->getMessage());
        }
    }


    public static function afterImport(AfterImport $event)
    {
        Log::info('--- after import ----- ');

    }

    public function startRow(): int
    {
         return 2;
    }

    public function batchSize(): int
    {
        return 10000;
    }

    public function chunkSize(): int
    {
        return 10000;
    }

    public function registerEvents(): array
    {
        return [
            AfterImport::class => function (AfterImport $event){
                QueryRegularOrderEntryUpload::updateStatusAfterImport($this->id_regular_order_entry_upload);
                OrderEntryDetail::dispatch([
                    'id_regular_order_entry_upload' => $this->id_regular_order_entry_upload
                ]);
                OrderEntryBox::dispatch([
                    'id_regular_order_entry_upload' => $this->id_regular_order_entry_upload
                ]);
            }
        ];
    }

}
