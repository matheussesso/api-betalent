<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'message' => 'Não autenticado.',
            ], 401);
        }

        if (empty($roles) || in_array($user->role->value, $roles, true)) {
            return $next($request);
        }

        return response()->json([
            'message' => 'Você não tem permissão para esta ação.',
        ], 403);
    }
}
