<?php

namespace Tests\Feature;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\AuthSecurityTestCase;

class LoginThrottlingTest extends AuthSecurityTestCase
{
    private function failLogin(array $input = []): void
    {
        $this->from(route('login'))->post(route('login.process'), array_merge($this->validLogin(), $input))
            ->assertRedirect(route('login'))->assertSessionHasErrors('email');
    }

    public function test_sixth_failed_attempt_is_blocked_without_calling_the_api(): void
    {
        Http::fake(['api.simasu.test/api/login' => Http::response([], 401)]);
        for ($i = 0; $i < 5; $i++) {
            $this->failLogin();
        }

        $this->post(route('login.process'), $this->validLogin())
            ->assertStatus(429)->assertHeader('Retry-After', '60')
            ->assertSee('Terlalu banyak percobaan login')->assertDontSee('test-only-password');

        Http::assertSentCount(5);
    }

    public function test_login_is_allowed_after_the_lockout_window_expires(): void
    {
        Http::fake(['api.simasu.test/api/login' => Http::response([], 401)]);
        for ($i = 0; $i < 5; $i++) {
            $this->failLogin();
        }
        $this->travel(61)->seconds();

        $this->failLogin();
        Http::assertSentCount(6);
    }

    public function test_successful_login_resets_the_failure_counter(): void
    {
        Http::fake(['api.simasu.test/api/login' => Http::sequence()
            ->push([], 401)->push([], 401)->push([], 401)->push([], 401)
            ->push(['token' => 'test-api-token', 'user' => ['id' => 42, 'role' => 'admin']])
            ->push([], 401)->push([], 401)]);
        for ($i = 0; $i < 4; $i++) {
            $this->failLogin();
        }
        $this->post(route('login.process'), $this->validLogin())->assertRedirect(route('dashboard'));
        $this->failLogin();
        $this->failLogin();
        Http::assertSentCount(7);
    }

    public function test_email_case_does_not_bypass_the_limit(): void
    {
        Http::fake(['api.simasu.test/api/login' => Http::response([], 401)]);
        for ($i = 0; $i < 5; $i++) {
            $this->failLogin();
        }
        $this->postJson(route('login.process'), $this->validLogin())
            ->assertStatus(429)->assertJsonPath('retry_after', 60);
        $this->post(route('login.process'), array_merge($this->validLogin(), ['email' => 'ADMIN@EXAMPLE.TEST']))
            ->assertStatus(429);
        Http::assertSentCount(5);
    }

    public function test_other_accounts_have_independent_attempt_counters(): void
    {
        Http::fake(['api.simasu.test/api/login' => Http::response([], 401)]);
        for ($i = 0; $i < 5; $i++) {
            $this->failLogin();
        }
        $this->failLogin(['email' => 'other@example.test']);
        Http::assertSentCount(6);
    }

    public function test_other_client_ips_have_independent_attempt_counters(): void
    {
        Http::fake(['api.simasu.test/api/login' => Http::response([], 401)]);
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.10']);
        for ($i = 0; $i < 5; $i++) {
            $this->failLogin();
        }
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.11']);
        $this->failLogin();
        Http::assertSentCount(6);
    }

    public function test_api_failure_does_not_consume_bad_password_attempts(): void
    {
        Http::fake(['api.simasu.test/api/login' => Http::response([], 503)]);
        for ($i = 0; $i < 6; $i++) {
            $this->failLogin();
        }
        Http::assertSentCount(6);
        $this->assertDatabaseCount('security_events', 6);
        $this->assertDatabaseHas('security_events', ['event' => 'api_unavailable']);
    }

    public function test_connection_timeout_shows_a_safe_error_without_filling_the_password(): void
    {
        Http::fake(fn () => throw new ConnectionException('test-only connection failure'));
        $this->from(route('login'))->post(route('login.process'), $this->validLogin())
            ->assertRedirect(route('login'))->assertSessionHasErrors('email')
            ->assertSessionMissing('_old_input.password')->assertSessionMissing('api_token');
        $this->assertDatabaseHas('security_events', ['event' => 'api_unavailable']);
    }

    public function test_invalid_form_data_never_calls_the_api(): void
    {
        Http::fake();
        $this->postJson(route('login.process'), ['email' => 'invalid', 'password' => []])
            ->assertUnprocessable()->assertJsonValidationErrors(['email', 'password']);
        Http::assertNothingSent();
        $this->assertDatabaseCount('security_events', 0);
    }

    public function test_non_admin_login_never_keeps_the_api_token(): void
    {
        $this->fakeSuccessfulLogin('user');
        $this->from(route('login'))->post(route('login.process'), $this->validLogin())
            ->assertRedirect(route('login'))->assertSessionHasErrors('email')
            ->assertSessionMissing('api_token')->assertSessionMissing('user');
        $this->assertDatabaseHas('security_events', ['event' => 'access_denied']);
    }

    public function test_malformed_success_response_does_not_create_an_authenticated_session(): void
    {
        Http::fake(['api.simasu.test/api/login' => Http::response(['token' => '', 'user' => ['role' => 'admin']])]);
        $this->failLogin();
        $this->assertFalse(session()->has('api_token'));
        $this->assertDatabaseHas('security_events', ['event' => 'api_unavailable']);
    }
}
