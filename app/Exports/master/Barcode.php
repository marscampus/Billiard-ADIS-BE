<?php

namespace App\Exports\master;

use App\Models\master\Stock;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class Barcode implements FromCollection, withHeadings
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Stock::all();
    }
    public function headings(): array
    {
        return [
            'KODE',
            'NAMA'
        ];
    }
}
