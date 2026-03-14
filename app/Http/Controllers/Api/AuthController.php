<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Realiza o login do usuário e gera um token de acesso temporário
     *
     * @param Request $request Requisição contendo e-mail e senha do usuário
     * @return JsonResponse Resposta com token de autenticação e dados básicos do usuário autenticado
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()->where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Credenciais inválidas.',
            ], 401);
        }

        $plainTextToken = Str::random(64);

        ApiToken::query()->create([
            'user_id' => $user->id,
            'name' => 'login-token',
            'token' => hash('sha256', $plainTextToken),
            'expires_at' => now()->addDays(7),
        ]);

        return response()->json([
            'token_type' => 'Bearer',
            'access_token' => $plainTextToken,
            'expires_at' => now()->addDays(7)->toISOString(),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
            ],
        ]);
    }
}
