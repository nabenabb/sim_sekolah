<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (! in_array($request->user()->role->name, $roles)) {
            abort(403, 'Akses ditolak');
        }

        return $next($request);
    }
}