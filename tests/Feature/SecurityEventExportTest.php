<?php

namespace Tests\Feature;

use App\Models\SecurityEvent;
use Tests\AuthSecurityTestCase;

/**
 * Pengujian ekspor CSV riwayat keamanan: akses admin, filter,
 * validasi input, hasil kosong, dan perlindungan CSV injection.
 */
class SecurityEventExportTest extends AuthSecurityTestCase
{
    private function event(array $values = []): SecurityEvent
    {
        return SecurityEvent::create(array_merge([
            'event' => 'login_failed', 'email' => 'admin@example.test', 'ip_address' => '127.0.0.1',
        ], $values));
    }

    private function csvLines(string $content): array
    {
        $content = ltrim($content, "\xEF\xBB\xBF");

        return array_values(array_filter(preg_split('/\r\n|\n/', $content), fn ($l) => $l !== ''));
    }

    public function test_admin_downloads_csv_with_header_and_data_rows(): void
    {
        $this->event(['event' => 'logout', 'email' => 'budi@example.test', 'ip_address' => '10.0.0.5']);

        $response = $this->withSession($this->adminSession())->get(route('keamanan.export'));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));

        $csv = $response->streamedContent();
        $this->assertTrue(str_starts_with($csv, "\xEF\xBB\xBF"), 'CSV harus diawali BOM UTF-8');

        $lines = $this->csvLines($csv);
        $this->assertCount(2, $lines);
        $this->assertStringContainsString('Aktivitas', $lines[0]);
        $this->assertStringContainsString('budi@example.test', $lines[1]);
        $this->assertStringContainsString('10.0.0.5', $lines[1]);
        $this->assertStringContainsString(SecurityEvent::LABELS['logout'], $lines[1]);
    }

    public function test_non_admin_and_missing_role_cannot_export(): void
    {
        $this->withSession($this->adminSession(['user' => ['role' => 'user']]))
            ->get(route('keamanan.export'))->assertForbidden();

        $this->withSession($this->adminSession(['user' => []]))
            ->get(route('keamanan.export'))->assertForbidden();
    }

    public function test_active_filters_are_applied_to_the_exported_rows(): void
    {
        $this->event(['event' => 'logout', 'email' => 'keep@example.test']);
        $this->event(['event' => 'login_failed', 'email' => 'drop@example.test']);
        $this->event(['event' => 'logout', 'email' => 'other@example.test']);

        $csv = $this->withSession($this->adminSession())
            ->get(route('keamanan.export', ['event' => 'logout', 'email' => 'KEEP@example.test']))
            ->assertOk()->streamedContent();

        $this->assertStringContainsString('keep@example.test', $csv);
        $this->assertStringNotContainsString('drop@example.test', $csv);
        $this->assertStringNotContainsString('other@example.test', $csv);
    }

    public function test_date_filter_excludes_events_from_other_days(): void
    {
        $old = $this->event(['email' => 'old@example.test']);
        $old->created_at = now()->subDays(3);
        $old->save();
        $this->event(['email' => 'today@example.test']);

        $csv = $this->withSession($this->adminSession())
            ->get(route('keamanan.export', ['date' => now()->format('Y-m-d')]))
            ->assertOk()->streamedContent();

        $this->assertStringContainsString('today@example.test', $csv);
        $this->assertStringNotContainsString('old@example.test', $csv);
    }

    public function test_invalid_filters_are_rejected_before_exporting(): void
    {
        $this->withSession($this->adminSession())
            ->getJson(route('keamanan.export', ['event' => 'unknown', 'date' => 'abc', 'email' => 'bukan-email']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['event', 'date', 'email']);
    }

    public function test_empty_result_exports_header_only(): void
    {
        $csv = $this->withSession($this->adminSession())
            ->get(route('keamanan.export', ['event' => 'logout']))
            ->assertOk()->streamedContent();

        $this->assertCount(1, $this->csvLines($csv));
    }

    public function test_formula_like_values_are_neutralized_against_csv_injection(): void
    {
        $this->event(['email' => '=cmd|calc', 'ip_address' => '@SUM(1+1)']);

        $csv = $this->withSession($this->adminSession())
            ->get(route('keamanan.export'))->assertOk()->streamedContent();

        $this->assertStringContainsString("'=cmd|calc", $csv);
        $this->assertStringContainsString("'@SUM(1+1)", $csv);
        $this->assertStringNotContainsString(',=cmd|calc', $csv);
    }
}