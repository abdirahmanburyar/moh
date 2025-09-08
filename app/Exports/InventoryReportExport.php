<?php

namespace App\Exports;

use App\Models\InventoryReport;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class InventoryReportExport implements FromCollection, WithHeadings, WithMapping
{
    protected $monthYear;

    public function __construct($monthYear)
    {
        $this->monthYear = $monthYear;
    }

    public function collection()
    {
        return InventoryReport::with(['items.product'])
            ->where('month_year', $this->monthYear)
            ->firstOrFail()
            ->items;
    }

    public function headings(): array
    {
        return [
            'Product Code',
            'Product Name',
            'Beginning Balance',
            'Received Quantity',
            'Issued Quantity',
            'Positive Adjustment',
            'Negative Adjustment',
            'Closing Balance',
            'Months of Stock',
            'Unit Cost',
            'Total Cost',
        ];
    }

    public function map($item): array
    {
        return [
            $item->product->code,
            $item->product->name,
            $item->beginning_balance,
            $item->received_quantity,
            $item->issued_quantity,
            $item->positive_adjustment,
            $item->negative_adjustment,
            $item->closing_balance,
            $item->months_of_stock,
            $item->unit_cost,
            $item->total_cost,
        ];
    }
}
