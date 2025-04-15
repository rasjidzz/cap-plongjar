<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    // public function handle(Request $request, Closure $next, $role): Response
    // {
    //     $user = $request->user();

    //     // Cek jika user memiliki role tertentu
    //     if (!$user || !$user->hasRole($role)) {
    //         return response()->json(['message' => 'Forbidden: You do not have the required role.'], 403);
    //     }

    //     return $next($request);
    // }
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Ambil semua role user
        $userRoles = $user->roles->pluck('name')->toArray(); // Pastikan relasi roles() sudah ada di User model

        // Cek jika user memiliki salah satu role yang diizinkan
        if (!array_intersect($roles, $userRoles)) {
            return response()->json(['message' => 'Forbidden: You do not have the required role.'], 403);
        }

        return $next($request);
    }
}
