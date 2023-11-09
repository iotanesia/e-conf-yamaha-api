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
        return new Collection($this->data);
    }

    public function headings(): array
    {
        return ["No", "Cust Name", "Item No", "Item Name", "Customer Item No", "Customer Order No", "Quantity", "ETD YPMI", "ETD W/H", "ETD JKT", "Box"];
    }
}
