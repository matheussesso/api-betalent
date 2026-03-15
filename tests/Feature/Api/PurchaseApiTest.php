<?php

namespace Tests\Feature\Api;

use App\Models\Gateway;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Concerns\ApiFeatureHelpers;
use Tests\TestCase;

class PurchaseApiTest extends TestCase
{
    use RefreshDatabase;
    use ApiFeatureHelpers;

    public function test_purchase_uses_gateway_fallback_and_persists_transaction(): void
    {
        $product = Product::query()->create(['name' => 'Produto A', 'amount' => 1000]);

        Gateway::query()->create([
            'name' => 'Gateway 1',
            'driver' => 'gateway_1',
            'is_active' => true,
            'priority' => 1,
        ]);

        Gateway::query()->create([
            'name' => 'Gateway 2',
            'driver' => 'gateway_2',
            'is_active' => true,
            'priority' => 2,
        ]);

        Http::fake([
            $this->gatewayUrl('services.gateway_1.base_url', '/login') => Http::response([
                'token' => 'mock-token',
            ], 200),
            $this->gatewayUrl('services.gateway_1.base_url', '/transactions') => Http::response([
                'error' => 'gateway 1 failed',
            ], 422),
            $this->gatewayUrl('services.gateway_2.base_url', '/transacoes') => Http::response([
                'id' => 'ext-123',
                'status' => 'approved',
            ], 201),
        ]);

        $response = $this->postJson('/api/purchase', [
            'client_name' => 'Cliente Teste',
            'client_email' => 'cliente@test.com',
            'card_number' => '5569000000006063',
            'cvv' => '010',
            'products' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.external_id', 'ext-123')
            ->assertJsonPath('data.amount', 2000);

        $this->assertDatabaseHas('transactions', [
            'external_id' => 'ext-123',
            'amount' => 2000,
            'status' => 'approved',
        ]);
    }

    public function test_purchase_fails_when_cvv_is_invalid_for_all_active_gateways(): void
    {
        $product = Product::query()->create(['name' => 'Produto A', 'amount' => 1000]);

        Gateway::query()->create([
            'name' => 'Gateway 1',
            'driver' => 'gateway_1',
            'is_active' => true,
            'priority' => 1,
        ]);

        Gateway::query()->create([
            'name' => 'Gateway 2',
            'driver' => 'gateway_2',
            'is_active' => true,
            'priority' => 2,
        ]);

        Http::fake();

        $response = $this->postJson('/api/purchase', [
            'client_name' => 'Cliente Teste',
            'client_email' => 'cliente-cvv@test.com',
            'card_number' => '5569000000006063',
            'cvv' => '200',
            'products' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('message', 'Nenhum gateway conseguiu processar o pagamento.')
            ->assertJsonFragment([
                'gateway' => 'gateway_1',
                'error' => 'CVV inválido para este gateway.',
            ])
            ->assertJsonFragment([
                'gateway' => 'gateway_2',
                'error' => 'CVV inválido para este gateway.',
            ]);

        Http::assertNothingSent();
    }

    public function test_purchase_fails_when_payload_is_invalid(): void
    {
        $response = $this->postJson('/api/purchase', [
            'client_name' => '',
            'client_email' => 'email-invalido',
            'card_number' => '123',
            'cvv' => '12',
            'products' => [],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'client_name',
                'client_email',
                'card_number',
                'cvv',
                'products',
            ]);
    }

    public function test_purchase_fails_when_product_does_not_exist(): void
    {
        Gateway::query()->create([
            'name' => 'Gateway 1',
            'driver' => 'gateway_1',
            'is_active' => true,
            'priority' => 1,
        ]);

        $response = $this->postJson('/api/purchase', [
            'client_name' => 'Cliente Teste',
            'client_email' => 'cliente-sem-produto@test.com',
            'card_number' => '5569000000006063',
            'cvv' => '010',
            'products' => [
                ['product_id' => 9999, 'quantity' => 1],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Produto informado não existe.');
    }

    public function test_purchase_fails_when_no_gateway_is_active(): void
    {
        $product = Product::query()->create(['name' => 'Produto A', 'amount' => 1000]);

        $response = $this->postJson('/api/purchase', [
            'client_name' => 'Cliente Teste',
            'client_email' => 'cliente-sem-gateway@test.com',
            'card_number' => '5569000000006063',
            'cvv' => '010',
            'products' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Nenhum gateway ativo disponível.');
    }
}
