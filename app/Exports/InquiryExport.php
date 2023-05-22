<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class InquiryExport implements FromCollection, WithHeadings
{
    use Exportable;

    protected $data;

    function __construct($data) {
            $this->data = $data;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $arr = [];
        foreach ($this->data['items'] as $key => $value) {
            $box = '';
            foreach ($value->box->toArray() as $item) {
                $box = $item['qty'];
            }
            $arr[] = [
                'no' => $key +1,
                'cust_name' => $value->cust_name,
                'item_no' => $value->item_no,
                'item_name' => $value->item_name,
                'custemer_item_no' => $value->cust_item_no,
                'custemer_order_no' => $value->order_no,
                'quantity' => $value->qty,
                'etd_ypmi' => date('d F Y', strtotime($value->etd_ypmi)),
                'etd_wh' => date('d F Y', strtotime($value->etd_wh)),
                'etd_jkt' => date('d F Y', strtotime($value->etd_jkt)),
                'box' => $box
            ];
        }

        return new Collection($arr);
    }

    public function headings(): array
    {
        return ["No", "Cust Name", "Item No", "Item Name", "Customer Item No", "Customer Order No", "Quantity", "ETD YPMI", "ETD W/H", "ETD JKT", "Box"];
    }
}
