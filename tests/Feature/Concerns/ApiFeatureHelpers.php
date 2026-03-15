<?php

namespace Tests\Feature\Concerns;

use App\Models\User;

trait ApiFeatureHelpers
{
    private function gatewayUrl(string $configKey, string $path): string
    {
        return rtrim((string) config($configKey), '/').'/'.ltrim($path, '/');
    }

    private function issueTokenFor(User $user, string $password = 'password'): string
    {
        return (string) $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => $password,
        ])->json('access_token');
    }

    private function authHeader(string $token): array
    {
        return [
            'Authorization' => 'Bearer '.$token,
        ];
    }
}
