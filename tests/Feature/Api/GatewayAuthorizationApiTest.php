<?php

namespace Tests\Feature\Api;

use App\Enums\UserRole;
use App\Models\Gateway;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\ApiFeatureHelpers;
use Tests\TestCase;

class GatewayAuthorizationApiTest extends TestCase
{
    use RefreshDatabase;
    use ApiFeatureHelpers;

    public function test_only_admin_can_list_gateways(): void
    {
        Gateway::query()->create([
            'name' => 'Gateway 1',
            'driver' => 'gateway_1',
            'is_active' => true,
            'priority' => 1,
        ]);

        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'password' => 'password',
        ]);

        $finance = User::factory()->create([
            'role' => UserRole::FINANCE,
            'password' => 'password',
        ]);

        $user = User::factory()->create([
            'role' => UserRole::USER,
            'password' => 'password',
        ]);

        $adminToken = $this->issueTokenFor($admin);
        $financeToken = $this->issueTokenFor($finance);
        $userToken = $this->issueTokenFor($user);

        $this->getJson('/api/gateways', $this->authHeader($adminToken))->assertOk();
        $this->getJson('/api/gateways', $this->authHeader($financeToken))->assertForbidden();
        $this->getJson('/api/gateways', $this->authHeader($userToken))->assertForbidden();
    }

    public function test_admin_can_update_gateway_active_and_priority(): void
    {
        $gateway = Gateway::query()->create([
            'name' => 'Gateway 1',
            'driver' => 'gateway_1',
            'is_active' => true,
            'priority' => 2,
        ]);

        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'password' => 'password',
        ]);

        $adminToken = $this->issueTokenFor($admin);

        $this->patchJson('/api/gateways/'.$gateway->id.'/active', [
            'is_active' => false,
        ], $this->authHeader($adminToken))->assertOk()->assertJsonPath('is_active', false);

        $this->patchJson('/api/gateways/'.$gateway->id.'/priority', [
            'priority' => 1,
        ], $this->authHeader($adminToken))->assertOk()->assertJsonPath('priority', 1);
    }

    public function test_non_admin_cannot_update_gateway_active_or_priority(): void
    {
        $gateway = Gateway::query()->create([
            'name' => 'Gateway 1',
            'driver' => 'gateway_1',
            'is_active' => true,
            'priority' => 2,
        ]);

        $finance = User::factory()->create([
            'role' => UserRole::FINANCE,
            'password' => 'password',
        ]);

        $financeToken = $this->issueTokenFor($finance);

        $this->patchJson('/api/gateways/'.$gateway->id.'/active', [
            'is_active' => false,
        ], $this->authHeader($financeToken))->assertForbidden();

        $this->patchJson('/api/gateways/'.$gateway->id.'/priority', [
            'priority' => 1,
        ], $this->authHeader($financeToken))->assertForbidden();
    }

    public function test_admin_can_list_gateways_without_changes(): void
    {
        Gateway::query()->create([
            'name' => 'Gateway A',
            'driver' => 'gateway_1',
            'is_active' => true,
            'priority' => 1,
        ]);

        Gateway::query()->create([
            'name' => 'Gateway B',
            'driver' => 'gateway_2',
            'is_active' => true,
            'priority' => 2,
        ]);

        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'password' => 'password',
        ]);

        $adminToken = $this->issueTokenFor($admin);

        $this->getJson('/api/gateways', $this->authHeader($adminToken))
            ->assertOk()
            ->assertJsonCount(2);
    }
}
