<?php

namespace Tests\Feature;

use App\Models\SecurityEvent;
use Illuminate\Support\Facades\Http;
use Tests\AuthSecurityTestCase;

/**
 * Kontribusi Bintang (BNT-03): pengujian pencatatan audit tanpa data rahasia,
 * akses admin, filter, pagination, dan escaping nilai pada halaman riwayat.
 */
class SecurityAuditTest extends AuthSecurityTestCase
{
    private function event(array $values = []): SecurityEvent
    {
        return SecurityEvent::create(array_merge([
            'event' => 'login_failed', 'email' => 'admin@example.test', 'ip_address' => '127.0.0.1',
        ], $values));
    }

    public function test_successful_login_records_identity_without_password_or_token(): void
    {
        $this->fakeSuccessfulLogin();
        $this->post(route('login.process'), $this->validLogin())->assertRedirect(route('dashboard'));
        $entry = SecurityEvent::sole();
        $this->assertSame('login_succeeded', $entry->event);
        $this->assertSame('admin@example.test', $entry->email);
        $this->assertSame(42, $entry->api_user_id);
        $this->assertSame('127.0.0.1', $entry->ip_address);
        $this->assertStringNotContainsString('test-only-password', $entry->toJson());
        $this->assertStringNotContainsString('test-api-token', $entry->toJson());
    }

    public function test_failed_login_and_lockout_are_persisted_as_separate_events(): void
    {
        config(['security.login_max_attempts' => 1]);
        Http::fake(['api.simasu.test/api/login' => Http::response([], 401)]);
        $this->post(route('login.process'), $this->validLogin())->assertRedirect();
        $this->post(route('login.process'), $this->validLogin())->assertStatus(429);
        $this->assertDatabaseHas('security_events', ['event' => 'login_failed', 'email' => 'admin@example.test']);
        $this->assertDatabaseHas('security_events', ['event' => 'login_throttled', 'email' => 'admin@example.test']);
        $this->assertDatabaseCount('security_events', 2);
    }

    public function test_admin_can_view_the_audit_page_and_empty_state(): void
    {
        $this->withSession($this->adminSession())->get(route('keamanan.index'))
            ->assertOk()->assertSee('Riwayat Keamanan')->assertSee('Belum ada aktivitas keamanan')
            ->assertSee('keamanan riwayat login logout audit sesi');
    }

    public function test_non_admin_and_missing_role_cannot_read_the_audit_log(): void
    {
        $this->withSession($this->adminSession(['user' => ['role' => 'user']]))
            ->get(route('keamanan.index'))->assertForbidden();
        $this->withSession($this->adminSession(['user' => []]))
            ->get(route('keamanan.index'))->assertForbidden();
    }

    public function test_event_and_email_filters_are_combined(): void
    {
        $wanted = $this->event(['event' => 'logout']);
        $this->event(['email' => 'other@example.test', 'event' => 'logout']);
        $this->event();
        $this->withSession($this->adminSession())
            ->get(route('keamanan.index', ['event' => 'logout', 'email' => 'ADMIN@EXAMPLE.TEST']))
            ->assertOk()->assertViewHas('events', fn ($events) => $events->total() === 1 && $events->first()->id === $wanted->id);
    }

    public function test_date_filter_excludes_other_days(): void
    {
        $old = $this->event();
        $old->created_at = now()->subDays(2);
        $old->save();
        $wanted = $this->event();
        $this->withSession($this->adminSession())->get(route('keamanan.index', ['date' => now()->format('Y-m-d')]))
            ->assertOk()->assertViewHas('events', fn ($events) => $events->total() === 1 && $events->first()->id === $wanted->id);
    }

    public function test_invalid_filters_are_rejected(): void
    {
        $this->withSession($this->adminSession())
            ->getJson(route('keamanan.index', ['event' => 'unknown', 'date' => 'invalid', 'email' => 'invalid']))
            ->assertUnprocessable()->assertJsonValidationErrors(['event', 'date', 'email']);
    }

    public function test_results_are_paginated_newest_first_and_preserve_filters(): void
    {
        for ($i = 0; $i < 21; $i++) {
            $latest = $this->event();
        }
        $this->withSession($this->adminSession())->get(route('keamanan.index', ['event' => 'login_failed']))
            ->assertOk()->assertViewHas('events', fn ($events) => $events->total() === 21
                && $events->count() === 20 && $events->first()->id === $latest->id
                && str_contains($events->nextPageUrl(), 'event=login_failed'));
        $this->get(route('keamanan.index', ['page' => 2]))
            ->assertOk()->assertViewHas('events', fn ($events) => $events->count() === 1);
    }

    public function test_audit_values_are_escaped_in_the_page(): void
    {
        $this->event(['email' => '<script>test-only-payload</script>']);
        $this->withSession($this->adminSession())->get(route('keamanan.index'))
            ->assertOk()->assertSee('<script>test-only-payload</script>')
            ->assertDontSee('<script>test-only-payload</script>', false);
    }
}
