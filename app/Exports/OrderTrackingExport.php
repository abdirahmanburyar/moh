<?php

namespace App\Exports;

use App\Models\Order;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class OrderTrackingExport implements FromCollection, WithHeadings, WithMapping
{
    protected $filters;

    public function __construct($filters)
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Order::query();
        $query->with(['facility.handledby']);

        if (!empty($this->filters['facility'])) {
            $query->whereHas('facility', function ($q) {
                $q->where('name', $this->filters['facility']);
            });
        }
        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }
        if (!empty($this->filters['date_from']) && empty($this->filters['date_to'])) {
            $query->whereDate('order_date', $this->filters['date_from']);
        }
        if (!empty($this->filters['date_from']) && !empty($this->filters['date_to'])) {
            $query->whereBetween('order_date', [$this->filters['date_from'], $this->filters['date_to']]);
        }

        $orders = $query->get();

        // Attach tracking data as in the report
        foreach ($orders as $order) {
            $orderStats = \DB::table('order_items')
                ->where('order_items.order_id', $order->id)
                ->selectRaw('
                    SUM(order_items.quantity_to_release) as total_allocated,
                    SUM(order_items.received_quantity) as total_received
                ')
                ->first();

            $totalAllocated = $orderStats->total_allocated ?? 0;
            $totalReceived = $orderStats->total_received ?? 0;
            $fulfillmentPercentage = $totalAllocated > 0 ? round(($totalReceived / $totalAllocated) * 100) : 0;

            $order->tracking_data = [
                'total_allocated' => $totalAllocated,
                'total_received' => $totalReceived,
                'fulfillment_percentage' => $fulfillmentPercentage,
            ];
        }

        return $orders;
    }

    public function headings(): array
    {
        return [
            'Order Number',
            'Facility',
            'Handled By',
            'Order Date',
            'Expected Date',
            'Status',
            'Allocated QTY',
            'Received QTY',
            'Fulfillment (%)',
        ];
    }

    public function map($order): array
    {
        return [
            $order->order_number,
            $order->facility->name ?? '',
            $order->facility->handledby->name ?? '',
            $order->order_date,
            $order->expected_date,
            $order->status,
            $order->tracking_data['total_allocated'],
            $order->tracking_data['total_received'],
            $order->tracking_data['fulfillment_percentage'],
        ];
    }
} 