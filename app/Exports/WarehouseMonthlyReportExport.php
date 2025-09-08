<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class WarehouseMonthlyReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $reportData;
    protected $monthYear;

    public function __construct($reportData, $monthYear)
    {
        $this->reportData = $reportData;
        $this->monthYear = $monthYear;
    }

    public function collection()
    {
        return collect($this->reportData);
    }

    public function headings(): array
    {
        return [
            'Product',
            'Beginning Balance',
            'Stock Received',
            'Stock Issued',
            'Negative Adjustment',
            'Positive Adjustment',
            'Closing Balance',
        ];
    }

    public function map($item): array
    {
        // Calculate closing balance using the same formula as the frontend
        $closingBalance = $item->beginning_balance + $item->received_quantity - $item->issued_quantity - $item->negative_adjustment + $item->positive_adjustment;
        
        return [
            $item->product->name ?? 'N/A',
            $item->beginning_balance ?? 0,
            $item->received_quantity ?? 0,
            $item->issued_quantity ?? 0,
            $item->negative_adjustment ?? 0,
            $item->positive_adjustment ?? 0,
            $closingBalance,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as header
            1 => ['font' => ['bold' => true]],
            
            // Set auto width for all columns
            'A:G' => ['alignment' => ['horizontal' => 'center']],
        ];
    }

    public function title(): string
    {
        return 'Warehouse Monthly Report - ' . $this->monthYear;
    }
} 