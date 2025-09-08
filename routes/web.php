<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\PermissionMiddleware;
use App\Http\Controllers\TwoFactorController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\AssetDocumentController;
use App\Http\Controllers\AssetMaintenanceController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;
use Inertia\Inertia;
use App\Http\Controllers\DashboardController;

// Welcome route - accessible without authentication

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return Inertia::render('Welcome');
})
    ->name('welcome')
    ->middleware('guest');


// Broadcast routes
Broadcast::routes(['middleware' => ['web', 'auth']]);

// Two-Factor Authentication Routes - These must be accessible without 2FA
Route::middleware('auth')->group(function () {
    Route::get('/two-factor', [TwoFactorController::class, 'show'])->name('two-factor.show');
    Route::post('/two-factor', [TwoFactorController::class, 'verify'])->name('two-factor.verify');
    Route::post('/two-factor/resend', [TwoFactorController::class, 'resend'])->name('two-factor.resend');
});

// All routes that require authentication and 2FA
Route::middleware(['auth', \App\Http\Middleware\TwoFactorAuth::class])->group(function () {
    
    // Default route - redirect to login or dashboard
    Route::controller(DashboardController::class)
    ->group(function () {
        Route::get('/dashboard', 'index')->name('dashboard');
    });

    // Unauthorized access page
    Route::get('/unauthorized', function () {
        return Inertia::render('Unauthorized');
    })->name('unauthorized');

    // Profile routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    




    // Asset Management Routes
    Route::controller(AssetController::class)
        ->prefix('assets-management')
        ->middleware(['auth', 'verified'])
        ->group(function () {
            // View routes - require asset-view permission
            Route::get('/', 'index')->name('assets.index')->middleware('can:asset-view');
            Route::get('/{asset}', 'show')->name('assets.show')->middleware('can:asset-view');
            Route::get('/approvals', 'approvalsIndex')->name('assets.approvals.index')->middleware('can:asset-view');
            Route::get('/workflow', 'approvalsIndex')->name('assets.workflow.index')->middleware('can:asset-view');
            Route::get('/asset-items/{assetItem}/history', 'showHistory')->name('assets.history.index')->middleware('can:asset-view');
            Route::get('/asset-items/{assetItem}/detailed-history', 'showAssetItemHistory')->name('assets.items.history.index')->middleware('can:asset-view');
            
            // Create routes - require asset-create permission
            Route::get('/create', 'create')->name('assets.create')->middleware('can:asset-create');
            Route::post('/store', 'store')->name('assets.store')->middleware('can:asset-create');
            
            // Edit routes - require asset-edit permission
            Route::get('/{asset}/edit', 'edit')->name('assets.edit')->middleware('can:asset-edit');
            Route::put('/{asset}', 'update')->name('assets.update')->middleware('can:asset-edit');
            
            // Delete routes - require asset-delete permission
            Route::delete('/{asset}', 'destroy')->name('assets.destroy')->middleware('can:asset-delete');
            
            // Approval routes - require asset-approve permission
            Route::post('/{asset}/approve', 'approve')->name('assets.approve')->middleware('can:asset-approve');
            Route::post('/{asset}/reject', 'reject')->name('assets.reject')->middleware('can:asset-approve');
            Route::post('/{asset}/review', 'review')->name('assets.review')->middleware('can:asset-review');
            Route::post('/{asset}/restore', 'restore')->name('assets.restore')->middleware('can:asset-approve');
            Route::post('/bulk-approve', 'bulkApprove')->name('assets.bulk-approve')->middleware('can:asset-approve');
            
            // Transfer routes - require asset-manage permission
            Route::post('/{asset}/transfer', 'transferAsset')->name('assets.transfer')->middleware('can:asset-manage');
            
            // Bulk operations - require asset-bulk-import permission
            Route::get('/template/download', 'downloadTemplate')->name('assets.template.download')->middleware('can:asset-export');
            Route::post('/import', 'import')->name('assets.import')->middleware('can:asset-bulk-import');
            
            // Asset locations routes - require asset-manage permission
            Route::get('/locations', 'locationIndex')->name('assets.locations.index')->middleware('can:asset-manage');
            Route::get('/sub-locations', 'subLocationIndex')->name('assets.sub-locations.index')->middleware('can:asset-manage');
            Route::get('/locations/{location}/sub-locations', 'getSubLocations')->name('assets.locations.sub-locations')->middleware('can:asset-manage');
            Route::post('/locations/sub-locations', 'storeSubLocation')->name('assets.locations.sub-locations.store')->middleware('can:asset-manage');
            Route::post('/categories/store', 'storeCategory')->name('assets.categories.store')->middleware('can:asset-manage');
            Route::post('/locations/store', 'storeAssetLocation')->name('assets.locations.store')->middleware('can:asset-manage');
            Route::post('/fund-sources/store', 'storeFundSource')->name('assets.fund-sources.store')->middleware('can:asset-manage');
            Route::post('/regions/store', 'storeRegion')->name('assets.regions.store')->middleware('can:asset-manage');

            // Asset Assignee Routes - require asset-manage permission
            Route::post('/assignees/store', 'storeAssignee')->name('assets.assignees.store')->middleware('can:asset-manage');

            // Asset Document Routes - require asset-edit permission
            Route::post('/{asset}/documents', [AssetDocumentController::class, 'store'])->name('asset.documents.store')->middleware('can:asset-edit');
            Route::delete('/documents/{document}', [AssetDocumentController::class, 'destroy'])->name('asset.documents.destroy')->middleware('can:asset-edit');
            Route::get('/documents/{document}/download', [AssetDocumentController::class, 'download'])->name('asset.documents.download')->middleware('can:asset-view');
            Route::get('/documents/{document}/preview', [AssetDocumentController::class, 'preview'])->name('asset.documents.preview')->middleware('can:asset-view');

            // Asset Maintenance Routes - require asset-edit permission
            Route::get('/{asset}/maintenance', [AssetMaintenanceController::class, 'index'])->name('asset.maintenance.index')->middleware('can:asset-edit');
            Route::post('/{asset}/maintenance', [AssetMaintenanceController::class, 'store'])->name('asset.maintenance.store')->middleware('can:asset-edit');
            Route::get('/maintenance/{maintenance}/edit', [AssetMaintenanceController::class, 'edit'])->name('asset.maintenance.edit')->middleware('can:asset-edit');
            Route::put('/maintenance/{maintenance}', [AssetMaintenanceController::class, 'update'])->name('asset.maintenance.update')->middleware('can:asset-edit');
            Route::delete('/maintenance/{maintenance}', [AssetMaintenanceController::class, 'destroy'])->name('asset.maintenance.destroy')->middleware('can:asset-edit');
            Route::post('/maintenance/{maintenance}/mark-completed', [AssetMaintenanceController::class, 'markCompleted'])->name('asset.maintenance.mark-completed')->middleware('can:asset-edit');
            Route::get('/{asset}/maintenance/list', [AssetMaintenanceController::class, 'getAssetMaintenance'])->name('asset.maintenance.list')->middleware('can:asset-edit');
        });









    // Settings Management Routes
    Route::prefix('settings')->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('settings.index');
        
        // Users routes
        Route::get('/users', [UserController::class, 'index'])->name('settings.users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('settings.users.create');
        Route::post('/users', [UserController::class, 'store'])->name('settings.users.store');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('settings.users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('settings.users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('settings.users.destroy');
        Route::post('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('settings.users.toggle-status');
        Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('settings.users.reset-password');
        Route::post('/users/{user}/assign-permissions', [UserController::class, 'assignPermissions'])->name('settings.users.assign-permissions');
        Route::get('/users/{user}/permissions', [UserController::class, 'getUserPermissions'])->name('settings.users.permissions');
        

    });

});

require __DIR__.'/auth.php';