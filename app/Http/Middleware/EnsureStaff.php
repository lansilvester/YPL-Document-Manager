<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaff
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || ! in_array($user->role, ['admin', 'staff'], true)) {
            abort(403, 'Hanya staff dan administrator YPL.');
        }

        return $next($request);
    }
}
