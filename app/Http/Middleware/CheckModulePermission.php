<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class CheckModulePermission
{
    /**
     * Map of routes to required permissions
     */
    protected $routePermissions = [
        'orders.*' => 'order.view',
        'transfers.*' => 'transfer.view',
        'products.*' => 'product.view',
        'inventories.*' => 'inventory.view',
        'expired.*' => 'inventory.view',
        'liquidate-disposal.*' => 'liquidate.view',
        'supplies.*' => 'supply.view',
        'reports.*' => 'report.view',
        'facilities.*' => 'facility.view',
        'assets.*' => 'asset.view',
        'settings.*' => 'settings.view',
        'assets.*' => 'settings.view',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip check for dashboard and auth routes
        if ($request->routeIs('dashboard') || $request->routeIs('login') || $request->routeIs('logout') || $request->routeIs('unauthorized')) {
            return $next($request);
        }

        // Check if the user is authenticated
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        
        // Check if the user's permissions have been updated since they logged in
        if ($user->permission_updated_at && $user->permission_updated_at->gt(session('login_time', now()->subYears(10)))) {
            // Log the session invalidation
            
            // Log the user out
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            
            return redirect()->route('login')
                ->with('error', 'Your permissions have been changed. Please log in again.');
        }
        
        // admin bypass - admins have access to everything
        if ($user->isAdmin()) {
            return $next($request);
        }

        // TEMPORARY: Allow access to assets for testing
        if ($request->routeIs('assets.*')) {
            return $next($request);
        }

        // Get the current route name
        $routeName = $request->route()->getName();
        
        // Check if the current route matches any of our protected routes
        $requiredPermission = null;
        
        foreach ($this->routePermissions as $routePattern => $permission) {
            // Convert route pattern to regex
            $pattern = '/^' . str_replace('*', '.*', $routePattern) . '$/i';
            
            if (preg_match($pattern, $routeName)) {
                $requiredPermission = $permission;
                break;
            }
        }
        
        // If we found a required permission, check if the user has it
        if ($requiredPermission) {
            // Check if user has the permission directly
            $hasPermission = $user->hasPermissionTo($requiredPermission);
            
            if (!$hasPermission) {
                
                // Store the attempted route in the session for reference
                session(['attempted_route' => $routeName]);
                
                // Redirect to unauthorized page
                return redirect()->route('unauthorized');
            }
        }

        return $next($request);
    }
}
