<?php

namespace App\Exports;

use App\Models\Order;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OrderReportPdf implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $report;

    public function __construct($report)
    {
        $this->report = $report;
    }

    public function collection()
    {
        return $this->report->items;
    }

    public function headings(): array
    {
        return [
            'Product',
            'UOM',
            'Barcode',
            'Batch Number',
            'Expiry Date',
            'Quantity',
            'Warehouse',
            'Location',
            'Unit Cost',
            'Total Cost',
            'Category',
            'Dosage',
        ];
    }

    public function map($item): array
    {
        return [
            $item->product->name,
            $item->uom,
            $item->barcode,
            $item->batch_number,
            $item->expiry_date,
            $item->quantity,
            $item->warehouse->name,
            $item->location->name,
            $item->unit_cost,
            $item->total_cost,
            $item->product->category->name,
            $item->product->dosage->name,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
