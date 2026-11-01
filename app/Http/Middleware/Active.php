<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class Active
{
    public function handle(Request $r, Closure $next)
    {
        if ($r->user() && ! $r->user()->is_active) {
            Auth::logout();
            $r->session()->invalidate();
            $r->session()->regenerateToken();

            return redirect('/login')->withErrors(['email' => 'Tài khoản đã khóa.']);
        }$response = $next($r);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        if ($r->user()) {
            $response->headers->set('Cache-Control', 'no-store, private');
        }

        return $response;
    }
}
