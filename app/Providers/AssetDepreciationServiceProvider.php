<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\AssetItem;
use App\Models\AssetDepreciation;
use App\Observers\AssetItemObserver;

class AssetDepreciationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register observer for AssetItem to automatically create depreciation records
        AssetItem::observe(AssetItemObserver::class);
        
        // Publish configuration if needed
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/asset-depreciation.php' => config_path('asset-depreciation.php'),
            ], 'asset-depreciation-config');
        }
    }
}
