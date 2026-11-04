<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\AuthSecurityTestCase;

class AdminSessionTest extends AuthSecurityTestCase
{
    public function test_login_regenerates_the_session_and_sets_the_activity_timestamp(): void
    {
        $this->fakeSuccessfulLogin();
        $this->get(route('login'))->assertOk();
        $oldId = session()->getId();
        $this->post(route('login.process'), $this->validLogin())
            ->assertRedirect(route('dashboard'))->assertSessionHas('api_token', 'test-api-token')
            ->assertSessionHas('last_activity_at', now()->timestamp);
        $this->assertNotSame($oldId, session()->getId());
    }

    public function test_session_expires_at_the_exact_idle_limit(): void
    {
        $state = $this->adminSession(['last_activity_at' => now()->subMinutes(30)->timestamp, 'private_draft' => 'test-only']);
        $this->get(route('login'))->assertOk();
        $oldCsrf = session()->token();
        $this->withSession($state)->get(route('keamanan.index'))
            ->assertRedirect(route('login'))->assertSessionHasErrors('email')
            ->assertSessionMissing('api_token')->assertSessionMissing('user')
            ->assertSessionMissing('last_activity_at')->assertSessionMissing('private_draft');
        $this->assertNotSame($oldCsrf, session()->token());
        $this->assertDatabaseHas('security_events', ['event' => 'session_expired', 'api_user_id' => 42]);
        Http::assertNothingSent();
    }

    public function test_active_request_before_the_limit_refreshes_activity(): void
    {
        $this->withSession($this->adminSession(['last_activity_at' => now()->subSeconds(1799)->timestamp]))
            ->get(route('keamanan.index'))->assertOk()->assertSessionHas('last_activity_at', now()->timestamp)
            ->assertHeader('Cache-Control', 'no-store, private');
        $this->assertDatabaseCount('security_events', 0);
    }

    public function test_idle_limit_is_configurable(): void
    {
        config(['security.idle_timeout_minutes' => 5]);
        $this->withSession($this->adminSession(['last_activity_at' => now()->subMinutes(5)->timestamp]))
            ->get(route('keamanan.index'))->assertRedirect(route('login'))->assertSessionMissing('api_token');
    }

    public function test_existing_session_without_a_timestamp_is_initialized(): void
    {
        $state = $this->adminSession();
        unset($state['last_activity_at']);
        $this->withSession($state)->get(route('keamanan.index'))->assertOk()
            ->assertSessionHas('last_activity_at', now()->timestamp);
    }

    public function test_guest_cannot_access_protected_pages(): void
    {
        $this->get(route('keamanan.index'))->assertRedirect(route('login'));
        $this->assertDatabaseCount('security_events', 0);
    }

    public function test_logout_clears_all_session_data_and_rotates_csrf(): void
    {
        $this->get(route('login'))->assertOk();
        $this->withSession($this->adminSession(['private_draft' => 'test-only']));
        $oldId = session()->getId();
        $oldCsrf = session()->token();
        $this->post(route('logout'))->assertRedirect(route('login'))
            ->assertSessionMissing('api_token')->assertSessionMissing('user')->assertSessionMissing('private_draft')
            ->assertHeader('Cache-Control', 'no-store, private');
        $this->assertNotSame($oldId, session()->getId());
        $this->assertNotSame($oldCsrf, session()->token());
        $this->get(route('keamanan.index'))->assertRedirect(route('login'));
        $this->assertDatabaseHas('security_events', ['event' => 'logout']);
    }

    public function test_login_page_expires_an_idle_session_without_a_redirect_loop(): void
    {
        $this->withSession($this->adminSession(['last_activity_at' => now()->subMinutes(31)->timestamp]))
            ->get(route('login'))->assertRedirect(route('login'))->assertSessionMissing('api_token');
        $this->get(route('login'))->assertOk()->assertSee('Sesi berakhir karena tidak aktif');
        $this->assertDatabaseCount('security_events', 1);
    }

    public function test_authenticated_login_page_returns_to_dashboard(): void
    {
        $this->withSession($this->adminSession())->get(route('login'))->assertRedirect(route('dashboard'));
    }

    public function test_guest_logout_is_safe_and_does_not_fabricate_an_audit_event(): void
    {
        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertDatabaseCount('security_events', 0);
    }
}
