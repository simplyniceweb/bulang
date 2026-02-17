<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class GameMasterMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            // Not logged in → redirect to login page
            return redirect()->route('login');
        }

        // Role-based redirect if not authorized
        $user = Auth::user();

        if ($user->role !== 'game_master') {
            switch ($user->role) {
                case 'teller':
                    return redirect()->route('teller.dashboard');
                case 'admin':
                    return redirect()->route('admin.dashboard');
                default:
                    return redirect()->route('home')->with('error', 'You do not have permission to access this page.');
            }
        }
        
        return $next($request);
    }
}
