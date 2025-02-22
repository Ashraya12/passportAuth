<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $role)
    {
        $user = Auth::user();

        if (!($user)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $userRole = $user->role->name;

        $roleOrder = [
            'super_admin' => ['super_admin', 'admin', 'user'],
            'admin' => ['admin', 'user'],
            'user' => ['user']
        ];

        if (!(isset($roleOrder[$userRole])) || !(in_array($role, $roleOrder[$userRole]))) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        return $next($request);
    }
}
