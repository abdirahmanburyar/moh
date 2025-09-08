<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User;
use App\Models\Asset;
use App\Models\AssetItem;
use App\Models\AssetCategory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Get basic counts
        $userCount = User::count();
        $assetCount = Asset::count();
        $assetItemCount = AssetItem::count();
        $categoryCount = AssetCategory::count();

        // Get asset statistics by category
        $assetStats = $this->getAssetStatistics();

        // Get recent assets
        $recentAssets = Asset::with(['assetItems.category'])
            ->latest()
            ->limit(5)
            ->get();

        // Get recent users
        $recentUsers = User::with('permissions')
            ->latest()
            ->limit(5)
            ->get();

        // Generate dashboard tasks
        $tasks = $this->generateDashboardTasks();

        $responseData = [
            'dashboardData' => [
                'summary' => [
                    [
                        'label' => 'Users',
                        'fullName' => 'Total Users',
                        'value' => $userCount,
                        'color' => 'blue',
                    ],
                    [
                        'label' => 'Assets',
                        'fullName' => 'Total Assets',
                        'value' => $assetCount,
                        'color' => 'green',
                    ],
                    [
                        'label' => 'Items',
                        'fullName' => 'Asset Items',
                        'value' => $assetItemCount,
                        'color' => 'purple',
                    ],
                    [
                        'label' => 'Categories',
                        'fullName' => 'Asset Categories',
                        'value' => $categoryCount,
                        'color' => 'orange',
                    ],
                ],
                'tasks' => $tasks,
                'recommended_actions' => [],
            ],
            'userCountCard' => $userCount,
            'assetCountCard' => $assetCount,
            'assetStats' => $assetStats['categories'],
            'assetStatusStats' => $assetStats['statuses'],
            'recentAssets' => $recentAssets,
            'recentUsers' => $recentUsers,
        ];

        return Inertia::render('Dashboard', $responseData);
    }

    private function getAssetStatistics()
    {
        // Get all assets with their asset items and categories
        $assets = Asset::with(['assetItems.category'])->get();
        
        // Initialize counts
        $categoryCounts = [];
        $statusCounts = [
            'In Use' => 0,
            'Maintenance' => 0,
            'Disposed' => 0,
            'Pending Approval' => 0
        ];
        
        // Count assets by category and status
        foreach ($assets as $asset) {
            // Count by asset items categories
            foreach ($asset->assetItems as $assetItem) {
                $categoryName = $assetItem->category ? $assetItem->category->name : 'Uncategorized';
                
                if (!isset($categoryCounts[$categoryName])) {
                    $categoryCounts[$categoryName] = 0;
                }
                $categoryCounts[$categoryName]++;
            }
            
            // Count by status
            foreach ($asset->assetItems as $assetItem) {
                switch ($assetItem->status) {
                    case 'in_use':
                    case 'Good':
                        $statusCounts['In Use']++;
                        break;
                    case 'maintenance':
                        $statusCounts['Maintenance']++;
                        break;
                    case 'disposed':
                    case 'retired':
                    case 'Non-functional':
                        $statusCounts['Disposed']++;
                        break;
                    case 'pending_approval':
                        $statusCounts['Pending Approval']++;
                        break;
                }
            }
        }
        
        return [
            'categories' => $categoryCounts,
            'statuses' => $statusCounts
        ];
    }

    private function generateDashboardTasks()
    {
        $tasks = [];
        
        // Asset workflow tasks
        $assetsPendingApproval = Asset::where('status', 'pending_approval')->count();
        if ($assetsPendingApproval > 0) {
            $tasks[] = [
                'id' => 'assets_pending_approval',
                'type' => 'workflow',
                'title' => 'Assets Awaiting Approval',
                'description' => "{$assetsPendingApproval} assets waiting for approval",
                'count' => $assetsPendingApproval,
                'priority' => 'high',
                'icon' => 'cube',
                'color' => 'yellow',
                'route' => route('assets.approvals.index'),
                'category' => 'Assets',
                'current_stage' => 'Pending Approval',
                'next_stage' => 'Review'
            ];
        }

        // Assets in maintenance
        $assetsInMaintenance = AssetItem::where('status', 'maintenance')->count();
        if ($assetsInMaintenance > 0) {
            $tasks[] = [
                'id' => 'assets_maintenance',
                'type' => 'maintenance',
                'title' => 'Assets in Maintenance',
                'description' => "{$assetsInMaintenance} asset items require maintenance attention",
                'count' => $assetsInMaintenance,
                'priority' => 'medium',
                'icon' => 'wrench',
                'color' => 'orange',
                'route' => route('assets.index', ['status' => 'maintenance']),
                'category' => 'Assets',
                'current_stage' => 'Maintenance',
                'next_stage' => 'Complete'
            ];
        }

        // New users (created in last 7 days)
        $newUsers = User::where('created_at', '>=', Carbon::now()->subDays(7))->count();
        if ($newUsers > 0) {
            $tasks[] = [
                'id' => 'new_users',
                'type' => 'info',
                'title' => 'New Users This Week',
                'description' => "{$newUsers} new users registered in the last 7 days",
                'count' => $newUsers,
                'priority' => 'low',
                'icon' => 'user-plus',
                'color' => 'blue',
                'route' => route('settings.users.index'),
                'category' => 'Users',
                'current_stage' => 'New',
                'next_stage' => 'Active'
            ];
        }

        // Sort tasks by priority (high, medium, low) and then by count
        usort($tasks, function($a, $b) {
            $priorityOrder = ['high' => 3, 'medium' => 2, 'low' => 1];
            if ($priorityOrder[$a['priority']] !== $priorityOrder[$b['priority']]) {
                return $priorityOrder[$b['priority']] - $priorityOrder[$a['priority']];
            }
            return $b['count'] - $a['count'];
        });

        return $tasks;
    }
}