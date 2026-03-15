<?php

namespace Tests\Feature\Api;

use App\Enums\UserRole;
use App\Models\Client;
use App\Models\Gateway;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\ApiFeatureHelpers;
use Tests\TestCase;

class PrivateResourcesApiTest extends TestCase
{
    use RefreshDatabase;
    use ApiFeatureHelpers;

    public function test_finance_can_create_products_but_user_cannot(): void
    {
        $finance = User::factory()->create([
            'role' => UserRole::FINANCE,
            'password' => 'password',
        ]);

        $user = User::factory()->create([
            'role' => UserRole::USER,
            'password' => 'password',
        ]);

        $financeToken = $this->issueTokenFor($finance);
        $userToken = $this->issueTokenFor($user);

        $this->postJson('/api/products', [
            'name' => 'Produto Finance',
            'amount' => 500,
        ], $this->authHeader($financeToken))->assertCreated();

        $this->postJson('/api/products', [
            'name' => 'Produto User',
            'amount' => 500,
        ], $this->authHeader($userToken))->assertForbidden();
    }

    public function test_manager_can_create_users(): void
    {
        $manager = User::factory()->create([
            'role' => UserRole::MANAGER,
            'password' => 'password',
        ]);

        $managerToken = $this->issueTokenFor($manager);

        $this->postJson('/api/users', [
            'name' => 'Usuário Criado Manager',
            'email' => 'manager.criado@teste.com',
            'password' => 'password',
            'role' => 'USER',
        ], $this->authHeader($managerToken))->assertCreated();
    }

    public function test_finance_can_list_transactions_and_show_one(): void
    {
        $finance = User::factory()->create([
            'role' => UserRole::FINANCE,
            'password' => 'password',
        ]);

        $gateway = Gateway::query()->create([
            'name' => 'Gateway 1',
            'driver' => 'gateway_1',
            'is_active' => true,
            'priority' => 1,
        ]);

        $client = Client::query()->create([
            'name' => 'Cliente Teste',
            'email' => 'cliente-transacao@teste.com',
        ]);

        $transaction = Transaction::query()->create([
            'client_id' => $client->id,
            'gateway_id' => $gateway->id,
            'external_id' => 'ext-list-show',
            'status' => 'approved',
            'amount' => 1000,
            'card_last_numbers' => '6063',
        ]);

        $financeToken = $this->issueTokenFor($finance);

        $this->getJson('/api/transactions', $this->authHeader($financeToken))
            ->assertOk()
            ->assertJsonPath('data.0.id', $transaction->id);

        $this->getJson('/api/transactions/'.$transaction->id, $this->authHeader($financeToken))
            ->assertOk()
            ->assertJsonPath('id', $transaction->id);
    }

    public function test_clients_endpoints_require_authentication(): void
    {
        $client = Client::query()->create([
            'name' => 'Cliente Sem Auth',
            'email' => 'sem-auth@client.com',
        ]);

        $this->getJson('/api/clients')->assertStatus(401);
        $this->getJson('/api/clients/'.$client->id)->assertStatus(401);
    }
}
