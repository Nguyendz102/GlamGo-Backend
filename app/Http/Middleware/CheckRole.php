<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();
        $isAllowed = match ($role) {
            'admin' => $user?->isAdmin(),
            'customer' => $user?->isCustomer(),
            default => false,
        };

        if (! $isAllowed) {
            return response()->json([
                'status' => 403,
                'message' => 'Ban khong co quyen truy cap.',
            ], 403);
        }

        return $next($request);
    }
}
