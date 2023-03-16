<?php

namespace App\Imports;

use App\Jobs\OrderEntryBox;
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

class OrderEntry implements ToCollection, WithChunkReading, WithStartRow, WithMultipleSheets, WithEvents, ShouldQueue
{

    use RegistersEventListeners;

    private $id_regular_order_entry_upload;

    public function __construct($id_regular_order_entry_upload)
    {
        $this->id_regular_order_entry_upload = $id_regular_order_entry_upload;
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
            foreach ($collection->chunk(1000) as $i => $chunk) {
                foreach ($chunk as $row) {
                    if(in_array($row[7],['940E'])) {

                        $uuid = (String) Str::uuid();

                        OrderEntryBox::dispatch([
                            'code_consignee' => trim($row[1]),
                            'item_no' => trim($row[5]),
                            'qty' => trim($row[15]),
                            'uuid_regular_order_entry_upload_detail' => $uuid
                        ]);

                        $ext[] = [
                            'id_regular_order_entry_upload' => $id_regular_order_entry_upload,
                            'code_consignee' => trim($row[1]),
                            'model' => trim($row[4]),
                            'item_no' => trim($row[5]),
                            'disburse' => trim($row[12]),
                            'delivery' => trim($row[14]),
                            'etd_jkt' => trim($row[14]),
                            'etd_wh' => Carbon::parse(trim($row[14]))->addDays(2)->format('Ymd'),
                            'etd_ypmi' => Carbon::parse(trim($row[14]))->addDays(4)->format('Ymd'),
                            'qty' => trim($row[15]),
                            'status' => trim($row[20]),
                            'order_no' => trim($row[22]),
                            'cust_item_no' => trim($row[23]),
                            'uuid' => $uuid,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }
                QueryRegularOrderEntryUploadDetail::store($ext,false);
            }
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
        return 500;
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function registerEvents(): array
    {
        return [
            AfterImport::class => function (AfterImport $event){
                QueryRegularOrderEntryUpload::updateStatusAfterImport($this->id_regular_order_entry_upload);
            }
        ];
    }
}
