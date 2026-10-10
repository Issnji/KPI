<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user()?->loadMissing('role');

        abort_unless($user && $user->hasRole(...$roles), 403, 'Anda tidak memiliki akses.');

        return $next($request);
    }
}