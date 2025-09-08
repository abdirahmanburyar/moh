<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;

class WarehouseAmcExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths, WithEvents
{
    protected $pivotData;
    protected $monthYears;
    protected $isTemplate;

    public function __construct($pivotData, $monthYears, $isTemplate = false)
    {
        $this->pivotData = $pivotData;
        $this->monthYears = $monthYears;
        $this->isTemplate = $isTemplate;
    }

    public function array(): array
    {
        $exportData = [];
        
        foreach ($this->pivotData as $row) {
            $exportRow = [
                $row['name'],
            ];
            
            // Add consumption data for each month
            foreach ($this->monthYears as $monthYear) {
                $exportRow[] = $row[$monthYear] ?? 0;
            }
            
            // Add AMC column only if not a template
            if (!$this->isTemplate) {
                $exportRow[] = $row['AMC'] ?? 0;
            }
            
            $exportData[] = $exportRow;
        }
        
        return $exportData;
    }

    public function headings(): array
    {
        $headers = ['Item'];
        
        // Add month headers
        foreach ($this->monthYears as $monthYear) {
            $headers[] = $this->formatMonthYear($monthYear);
        }
        
        // Add AMC header only if not a template
        if (!$this->isTemplate) {
            $headers[] = 'AMC';
        }
        
        return $headers;
    }

    public function columnWidths(): array
    {
        $widths = [
            'A' => 40, // Item
        ];
        
        // Set width for month columns
        $currentColumn = 'B';
        foreach ($this->monthYears as $monthYear) {
            $widths[$currentColumn] = 15;
            $currentColumn++;
        }
        
        // Set width for AMC column only if not a template
        if (!$this->isTemplate) {
            $amcColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($this->monthYears) + 1);
            $widths[$amcColumn] = 20; // AMC column
        }
        
        return $widths;
    }

    public function styles(Worksheet $sheet)
    {
        $lastColumn = $this->getLastColumn();
        $lastRow = count($this->pivotData) + 1; // +1 for header row
        
        // Style the header row
        $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '6B7280'], // Gray-500
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'D1D5DB'], // Gray-300
                ],
            ],
        ]);
        
        // Style the data rows
        if ($lastRow > 1) {
            $sheet->getStyle("A2:{$lastColumn}{$lastRow}")->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'E5E7EB'], // Gray-200
                    ],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);
            
            // Center align month data columns
            $monthStartColumn = 'B';
            $monthEndColumn = $this->getLastColumn();
            $amcColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($this->monthYears) + 1);
            
            // Center align month columns (excluding AMC column)
            $monthEndColumnBeforeAmc = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($this->monthYears));
            $sheet->getStyle("{$monthStartColumn}2:{$monthEndColumnBeforeAmc}{$lastRow}")->applyFromArray([
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ]);
            
            // Left align first column
            $sheet->getStyle("A2:A{$lastRow}")->applyFromArray([
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                ],
            ]);
            
            // Style AMC column with special formatting only if not a template
            if (!$this->isTemplate) {
                $amcColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($this->monthYears) + 1);
                $sheet->getStyle("{$amcColumn}2:{$amcColumn}{$lastRow}")->applyFromArray([
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                    ],
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => '1E40AF'], // Blue-800
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'DBEAFE'], // Blue-100
                    ],
                ]);
            }
        }
        
        // Add alternating row colors
        for ($row = 2; $row <= $lastRow; $row++) {
            if ($row % 2 == 0) {
                $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F9FAFB'], // Gray-50
                    ],
                ]);
            }
        }
        
        // Freeze the first row and first column
        $sheet->freezePane('B2');
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $lastColumn = $this->getLastColumn();
                $lastRow = count($this->pivotData) + 1;
                
                // Auto-size columns based on content
                foreach (range('A', $lastColumn) as $column) {
                    $event->sheet->getColumnDimension($column)->setAutoSize(true);
                }
                
                // Set row height for header
                $event->sheet->getRowDimension(1)->setRowHeight(30);
                
                // Set row height for data rows
                for ($row = 2; $row <= $lastRow; $row++) {
                    $event->sheet->getRowDimension($row)->setRowHeight(25);
                }
            },
        ];
    }

    private function getLastColumn()
    {
        $baseColumns = 1; // Item
        $monthColumns = count($this->monthYears);
        $amcColumn = $this->isTemplate ? 0 : 1; // AMC column only if not template
        $totalColumns = $baseColumns + $monthColumns + $amcColumn;
        
        return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($totalColumns);
    }

    private function formatMonthYear($monthYear)
    {
        if (!$monthYear) return 'N/A';
        
        $parts = explode('-', $monthYear);
        if (count($parts) !== 2) return $monthYear;
        
        $year = $parts[0];
        $month = $parts[1];
        
        $date = \DateTime::createFromFormat('Y-m', $monthYear);
        if (!$date) return $monthYear;
        
        return $date->format('M Y'); // e.g., "Feb 2025"
    }
}
