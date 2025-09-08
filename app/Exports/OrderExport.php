<?php

namespace App\Exports;

use App\Models\Order;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OrderExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $filters;

    public function __construct($filters)
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Order::with([
            'facility',
            'user',
            'items.product.category',
            'items.product.dosage',
            'items.warehouse',
            'approvedBy',
            'rejectedBy'
        ])->orderBy('order_date', 'desc');

        if ($this->filters['month_year']) {
            $date = \Carbon\Carbon::parse($this->filters['month_year']);
            $query->whereBetween('order_date', [
                $date->startOfMonth(),
                $date->endOfMonth()
            ]);
        }

        if ($this->filters['status']) {
            $query->where('status', $this->filters['status']);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'Order Number',
            'Order Date',
            'Expected Date',
            'Facility',
            'Order Type',
            'Status',
            'Notes',
            'Product',
            'Category',
            'Dosage',
            'Quantity',
            'Warehouse',
            'Quantity on Order',
            'QER',
            'Quantity to Release',
            'No of Days'
        ];
    }

    public function map($order): array
    {
        $data = [];
        
        foreach ($order->items as $item) {
            $data[] = [
                $order->order_number,
                $order->order_date->format('Y-m-d'),
                $order->expected_date ? $order->expected_date->format('Y-m-d') : '',
                $order->facility->name ?? '',
                $order->order_type,
                $order->status,
                $order->notes,
                $item->product->name,
                $item->product->category->name ?? '',
                $item->product->dosage->name ?? '',
                $item->quantity,
                $item->warehouse->name ?? '',
                $item->quantity_on_order,
                $item->qer,
                $item->quantity_to_release,
                $item->no_of_days
            ];
        }

        return $data;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
