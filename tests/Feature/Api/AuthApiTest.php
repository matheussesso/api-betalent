<?php

namespace Tests\Feature\Api;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_and_receive_access_token(): void
    {
        User::factory()->create([
            'email' => 'admin@test.com',
            'password' => 'password123',
            'role' => UserRole::ADMIN,
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'admin@test.com',
            'password' => 'password123',
        ]);

        $response
            ->assertOk()
            ->assertJsonStructure([
                'token_type',
                'access_token',
                'expires_at',
                'user' => ['id', 'name', 'email', 'role'],
            ]);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'admin@test.com',
            'password' => 'password123',
            'role' => UserRole::ADMIN,
        ]);

        $this->postJson('/api/login', [
            'email' => 'admin@test.com',
            'password' => 'senha-invalida',
        ])->assertStatus(401)->assertJsonPath('message', 'Credenciais inválidas.');
    }

    public function test_private_route_requires_authentication(): void
    {
        $this->getJson('/api/clients')
            ->assertStatus(401)
            ->assertJsonPath('message', 'Não autenticado.');
    }
}
