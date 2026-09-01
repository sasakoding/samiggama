<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // If user is nonaktif
        if ($user->status === 'nonaktif') {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login')->with('feedbackMessage', 'Akun Anda sedang dinonaktifkan. Silakan hubungi Administrator.');
        }

        // Check each specified allowed role
        foreach ($roles as $role) {
            $role = strtolower(trim($role));
            if ($role === 'admin' && $user->isAdmin()) {
                return $next($request);
            }
            if ($role === 'bendahara' && $user->isBendahara()) {
                return $next($request);
            }
        }

        // If Bendahara tries to access an Admin-only page, redirect gracefully to dashboard
        if ($user->isBendahara()) {
            return redirect()->route('admin.dashboard')->with('feedbackMessage', 'Akses terbatas untuk menu tersebut. Anda masuk dengan hak akses Bendahara.');
        }

        abort(403, 'Akses tidak diizinkan untuk peran Anda.');
    }
}
