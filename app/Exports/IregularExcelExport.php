<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class IregularExcelExport implements FromCollection, WithHeadings
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
        return ["HS Code", "PART NO & PART NAME", "QTY", "TOTAL PACKAGE", "TOTAL PRICE", "MEASUREMENT", "NETT WEIGHT", "GROSS WEIGHT"];
    }
}
