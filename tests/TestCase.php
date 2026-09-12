<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function seedRolesAndPermissions(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    protected function userWithRole(string $roleCode): User
    {
        $this->seedRolesAndPermissions();

        return User::query()
            ->whereHas('roles', fn ($q) => $q->where('code', $roleCode))
            ->with('roles.permissions')
            ->firstOrFail();
    }
}
