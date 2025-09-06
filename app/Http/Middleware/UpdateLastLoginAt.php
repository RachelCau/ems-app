<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class UpdateLastLoginAt
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();
            
            // Only update the timestamp if it's been more than 10 minutes since last update
            // This prevents updating on every request
            if (!$user->last_login_at || now()->diffInMinutes($user->last_login_at) > 10) {
                $user->update([
                    'last_login_at' => now(),
                ]);
            }
        }
        
        return $next($request);
    }
}
