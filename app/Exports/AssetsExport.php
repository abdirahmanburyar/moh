<?php

namespace App\Exports;

use App\Models\Asset;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AssetsExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(
        protected ?array $filters = null
    ) {}

    public function query()
    {
        $q = Asset::query()->with(['category', 'location', 'subLocation']);
        if (!$this->filters) return $q;
        if (!empty($this->filters['status'])) {
            $q->whereIn('status', (array) $this->filters['status']);
        }
        if (!empty($this->filters['location_id'])) {
            $q->where('asset_location_id', $this->filters['location_id']);
        }
        return $q;
    }

    public function headings(): array
    {
        return [
            'ID', 'Tag No', 'Name', 'Category', 'Type', 'Location', 'Sub-Location', 'Assigned To', 'Serial No', 'Purchase Date', 'Cost', 'Supplier', 'Status', 'Warranty Start', 'Warranty Months', 'Maintenance Interval (Months)', 'Last Maintenance', 'Created At'
        ];
    }

    public function map($asset): array
    {
        return [
            $asset->id,
            $asset->tag_no ?? $asset->asset_tag,
            $asset->name ?? $asset->item_description,
            $asset->category?->name,
            $asset->type?->name ?? null,
            $asset->location?->name,
            $asset->subLocation?->name,
            $asset->assigned_to,
            $asset->serial_no ?? $asset->serial_number,
            optional($asset->purchase_date)->format('Y-m-d'),
            $asset->cost ?? $asset->original_value,
            $asset->supplier,
            $asset->status,
            optional($asset->warranty_start ?? $asset->asset_warranty_start)->format('Y-m-d'),
            $asset->warranty_months,
            $asset->maintenance_interval_months,
            optional($asset->last_maintenance_at)->format('Y-m-d'),
            optional($asset->created_at)->format('Y-m-d H:i:s'),
        ];
    }
}

