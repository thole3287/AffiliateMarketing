<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TopProductsExport implements FromArray, WithHeadings
{
    protected $data;
    protected $year1;
    protected $year2;

    public function __construct(array $data, $year1, $year2)
    {
        $this->data = $data;
        $this->year1 = $year1;
        $this->year2 = $year2;
    }

    public function array(): array
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'Product',
            strval($this->year1),
            strval($this->year2),
        ];
    }
}
