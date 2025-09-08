<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (!Gate::allows($permission)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Unauthorized. Insufficient permissions.',
                    'required_permission' => $permission
                ], 403);
            }

            abort(403, 'Access denied. You do not have permission to perform this action.');
        }

        return $next($request);
    }
}
