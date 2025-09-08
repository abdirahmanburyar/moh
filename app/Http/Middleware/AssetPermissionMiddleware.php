<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class AssetPermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        // Check if user is authenticated
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        // Check if user has the required permission
        if (!Gate::allows($permission)) {
            // If it's an AJAX request, return JSON response
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Access denied',
                    'message' => 'You do not have permission to perform this action.',
                    'required_permission' => $permission
                ], 403);
            }

            // For web requests, redirect with error message
            return redirect()->back()->withErrors([
                'permission_error' => "Access denied. You need '{$permission}' permission to perform this action."
            ]);
        }

        return $next($request);
    }
}
