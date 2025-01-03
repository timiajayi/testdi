<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class StaffAccess
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->user()->isStaff() || auth()->user()->isAdmin()) {
            return redirect()->route('gallery');
        }
        return $next($request);
    }
}
