<?php

namespace Tests\Feature\Api;

use App\Enums\UserRole;
use App\Models\Client;
use App\Models\Gateway;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Concerns\ApiFeatureHelpers;
use Tests\TestCase;

class RefundApiTest extends TestCase
{
    use RefreshDatabase;
    use ApiFeatureHelpers;

    public function test_finance_can_refund_and_user_cannot(): void
    {
        $finance = User::factory()->create([
            'role' => UserRole::FINANCE,
            'password' => 'password',
        ]);

        $user = User::factory()->create([
            'role' => UserRole::USER,
            'password' => 'password',
        ]);

        $gateway = Gateway::query()->create([
            'name' => 'Gateway 2',
            'driver' => 'gateway_2',
            'is_active' => true,
            'priority' => 1,
        ]);

        $client = Client::query()->create([
            'name' => 'Cliente',
            'email' => 'client@client.com',
        ]);

        $transaction = Transaction::query()->create([
            'client_id' => $client->id,
            'gateway_id' => $gateway->id,
            'external_id' => 'ext-abc',
            'status' => 'approved',
            'amount' => 1000,
            'card_last_numbers' => '6063',
        ]);

        $userToken = $this->issueTokenFor($user);

        $this->postJson('/api/transactions/'.$transaction->id.'/refund', [], $this->authHeader($userToken))
            ->assertForbidden();

        Http::fake([
            $this->gatewayUrl('services.gateway_2.base_url', '/transacoes/reembolso') => Http::response([
                'status' => 'refunded',
            ], 200),
        ]);

        $financeToken = $this->issueTokenFor($finance);

        $this->postJson('/api/transactions/'.$transaction->id.'/refund', [], $this->authHeader($financeToken))
            ->assertOk()->assertJsonPath('data.status', 'refunded');

        $this->postJson('/api/transactions/'.$transaction->id.'/refund', [], $this->authHeader($financeToken))
            ->assertStatus(422)->assertJsonPath('message', 'Transação já foi reembolsada.');

        Http::assertSentCount(1);
    }

    public function test_refund_is_blocked_when_transaction_status_is_already_charged_back(): void
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
            'name' => 'Cliente',
            'email' => 'chargedback@client.com',
        ]);

        $transaction = Transaction::query()->create([
            'client_id' => $client->id,
            'gateway_id' => $gateway->id,
            'external_id' => 'ext-charged-back',
            'status' => 'charged_back',
            'amount' => 1000,
            'card_last_numbers' => '6063',
        ]);

        Http::fake();

        $financeToken = $this->issueTokenFor($finance);

        $this->postJson('/api/transactions/'.$transaction->id.'/refund', [], $this->authHeader($financeToken))
            ->assertStatus(422)->assertJsonPath('message', 'Transação já foi reembolsada.');

        Http::assertNothingSent();
    }
}
