<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        // Check if user is authenticated
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Check if user has any of the required roles
        if (!in_array($request->user()->role, $roles)) {
            return response()->json([
                'message' => 'Forbidden. Required role: '.implode(', ', $roles),
                'your_role' => $request->user()->role
            ], 403);
        }

        return $next($request);
    }
}
