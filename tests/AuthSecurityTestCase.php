<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

abstract class AuthSecurityTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->freezeTime();
        config(['api.base_url' => 'http://api.simasu.test']);
        Cache::flush();
        Http::preventStrayRequests();
    }

    protected function adminSession(array $extra = []): array
    {
        return array_merge([
            'api_token' => 'test-api-token',
            'user' => ['id' => 42, 'name' => 'Admin Test', 'email' => 'admin@example.test', 'role' => 'admin'],
            'last_activity_at' => now()->timestamp,
        ], $extra);
    }

    protected function validLogin(): array
    {
        return ['email' => 'admin@example.test', 'password' => 'test-only-password'];
    }

    protected function fakeSuccessfulLogin(string $role = 'admin'): void
    {
        Http::fake([
            'api.simasu.test/api/login' => Http::response([
                'token' => 'test-api-token',
                'user' => ['id' => 42, 'email' => 'admin@example.test', 'name' => 'Admin Test', 'role' => $role],
            ]),
        ]);
    }
}
