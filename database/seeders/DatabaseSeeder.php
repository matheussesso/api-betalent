<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Gateway;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->updateOrCreate([
            'email' => 'admin@betalent.local',
        ], [
            'name' => 'Administrador',
            'password' => 'password',
            'role' => UserRole::ADMIN,
        ]);

        User::query()->updateOrCreate([
            'email' => 'manager@betalent.local',
        ], [
            'name' => 'Manager',
            'password' => 'password',
            'role' => UserRole::MANAGER,
        ]);

        User::query()->updateOrCreate([
            'email' => 'finance@betalent.local',
        ], [
            'name' => 'Finance',
            'password' => 'password',
            'role' => UserRole::FINANCE,
        ]);

        Gateway::query()->updateOrCreate([
            'driver' => 'gateway_1',
        ], [
            'name' => 'Gateway 1',
            'is_active' => true,
            'priority' => 1,
        ]);

        Gateway::query()->updateOrCreate([
            'driver' => 'gateway_2',
        ], [
            'name' => 'Gateway 2',
            'is_active' => true,
            'priority' => 2,
        ]);
    }
}
