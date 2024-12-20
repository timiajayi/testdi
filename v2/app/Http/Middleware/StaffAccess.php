<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;

class StaffAccess
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->user()->isStaff()) {
            return redirect()->route('gallery');
        }
        return $next($request);
    }
}
