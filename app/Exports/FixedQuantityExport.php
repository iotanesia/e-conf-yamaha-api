<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class FixedQuantityExport implements FromCollection, WithHeadings
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
        return new Collection($this->data);
    }

    public function headings(): array
    {
        return ["NO", "CUST NAME", "ITEM NO", "ITEM NAME", "CUSTOMER ITEM NO", "CUSTOMER ORDER NO", "QUANTITY", "ETD YPMI", "ETD WH", "ETD JKT", "PRODUCTION", "IN DC", "IN WH"];
    }
}
