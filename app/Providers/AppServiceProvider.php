<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use App\Observers\AssetObserver;

use App\Models\Asset;
use App\Models\AssetItem;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register Blade directives for permissions
        $this->registerBladeDirectives();
        Vite::prefetch(concurrency: 3);
        Asset::observe(AssetObserver::class);
    }

    /**
     * Register custom Blade directives.
     */
    protected function registerBladeDirectives(): void
    {
        // @can directive for checking permissions
        Blade::directive('can', function ($expression) {
            return "<?php if (Gate::allows({$expression})): ?>";
        });

        // @endcan directive
        Blade::directive('endcan', function () {
            return "<?php endif; ?>";
        });

        // @cannot directive for checking if user cannot do something
        Blade::directive('cannot', function ($expression) {
            return "<?php if (!Gate::allows({$expression})): ?>";
        });

        // @endcannot directive
        Blade::directive('endcannot', function () {
            return "<?php endif; ?>";
        });

        // @canany directive for checking if user has any of the permissions
        Blade::directive('canany', function ($expression) {
            return "<?php if (Gate::any({$expression})): ?>";
        });

        // @endcanany directive
        Blade::directive('endcanany', function () {
            return "<?php endif; ?>";
        });

        // @canall directive for checking if user has all permissions
        Blade::directive('canall', function ($expression) {
            return "<?php if (Gate::all({$expression})): ?>";
        });

        // @endcanall directive
        Blade::directive('endcanall', function () {
            return "<?php endif; ?>";
        });
    }
}
