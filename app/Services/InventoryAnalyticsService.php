<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class InventoryAnalyticsService
{
    /**
     * Returns a query builder with AMC and reorder level joined to inventories.
     * Optionally accepts a callback to apply filters.
     */
    public static function inventoryWithAmcAndReorderLevel(callable $filterCallback = null)
    {
        $fiveMonthsAgo = now()->subMonths(5)->startOfMonth();
        $leadTime = 5;

        $amcSubquery = DB::table('issued_quantities')
            ->select(
                'product_id',
                'batch_number',
                DB::raw('COALESCE(SUM(quantity), 0) / 5 as amc')
            )
            ->where('issued_date', '>=', $fiveMonthsAgo)
            ->groupBy('product_id', 'batch_number');

        $query = DB::table('inventories')
            ->leftJoinSub($amcSubquery, 'amc_data', function($join) {
                $join->on('inventories.product_id', '=', 'amc_data.product_id')
                     ->on('inventories.batch_number', '=', 'amc_data.batch_number');
            })
            ->select(
                'inventories.*',
                DB::raw('COALESCE(amc_data.amc, 0) as amc'),
                DB::raw('(COALESCE(amc_data.amc, 0) * 6) as reorder_level')
            );

        if ($filterCallback) {
            $query = $filterCallback($query);
        }

        return $query;
    }
}
