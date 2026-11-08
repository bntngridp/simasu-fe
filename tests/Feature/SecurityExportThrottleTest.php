<?php

namespace Tests\Feature;

use Tests\AuthSecurityTestCase;

/**
 * Kontribusi Aryoseto (ARY-03): pembatasan frekuensi ekspor riwayat keamanan.
 */
class SecurityExportThrottleTest extends AuthSecurityTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['security.export_max_attempts' => 2]);
    }

    public function test_request_over_the_limit_is_rejected_with_retry_after(): void
    {
        $session = $this->adminSession();
        $this->withSession($session)->get(route('keamanan.export'))->assertOk();
        $this->withSession($session)->get(route('keamanan.export'))->assertOk();
        $this->withSession($session)->get(route('keamanan.export'))
            ->assertStatus(429)->assertHeader('Retry-After');
    }

    public function test_export_is_allowed_again_after_the_window_passes(): void
    {
        $session = $this->adminSession();
        $this->withSession($session)->get(route('keamanan.export'))->assertOk();
        $this->withSession($session)->get(route('keamanan.export'))->assertOk();
        $this->withSession($session)->get(route('keamanan.export'))->assertStatus(429);
        $this->travel(61)->seconds();
        $this->withSession($session)->get(route('keamanan.export'))->assertOk();
    }

    public function test_limit_follows_configuration(): void
    {
        config(['security.export_max_attempts' => 1]);
        $session = $this->adminSession();
        $this->withSession($session)->get(route('keamanan.export'))->assertOk();
        $this->withSession($session)->get(route('keamanan.export'))->assertStatus(429);
    }

    public function test_non_admin_is_still_forbidden_within_the_limit(): void
    {
        $this->withSession($this->adminSession(['user' => ['role' => 'user']]))
            ->get(route('keamanan.export'))->assertForbidden();
    }

    public function test_each_account_has_an_independent_counter(): void
    {
        $first = $this->adminSession();
        $second = $this->adminSession(['user' => ['role' => 'admin', 'email' => 'lain@example.test']]);
        $this->withSession($first)->get(route('keamanan.export'))->assertOk();
        $this->withSession($first)->get(route('keamanan.export'))->assertOk();
        $this->withSession($first)->get(route('keamanan.export'))->assertStatus(429);
        $this->withSession($second)->get(route('keamanan.export'))->assertOk();
    }
}