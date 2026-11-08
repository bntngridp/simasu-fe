<?php

namespace Tests\Feature;

use App\Models\SecurityEvent;
use Tests\AuthSecurityTestCase;

/**
 * Kontribusi Aryoseto (ARY-02): audit setiap ekspor riwayat keamanan.
 */
class SecurityExportAuditTest extends AuthSecurityTestCase
{
    private function exports()
    {
        return SecurityEvent::where('event', 'security_exported');
    }

    private function csvLines(string $content): array
    {
        $content = ltrim($content, "\xEF\xBB\xBF");

        return array_values(array_filter(preg_split('/\r\n|\n/', $content), fn ($l) => $l !== ''));
    }

    public function test_successful_export_records_one_audit_event(): void
    {
        $this->withSession($this->adminSession())->get(route('keamanan.export'))->assertOk();

        $this->assertSame(1, $this->exports()->count());
        $this->assertSame('127.0.0.1', $this->exports()->first()->ip_address);
    }

    public function test_non_admin_export_is_forbidden_and_not_recorded(): void
    {
        $this->withSession($this->adminSession(['user' => ['role' => 'user']]))
            ->get(route('keamanan.export'))->assertForbidden();

        $this->assertSame(0, $this->exports()->count());
    }

    public function test_invalid_filters_do_not_record_an_export(): void
    {
        $this->withSession($this->adminSession())
            ->getJson(route('keamanan.export', ['date' => 'abc']))
            ->assertUnprocessable();

        $this->assertSame(0, $this->exports()->count());
    }

    public function test_export_does_not_include_its_own_audit_row(): void
    {
        $csv = $this->withSession($this->adminSession())
            ->get(route('keamanan.export'))->assertOk()->streamedContent();

        $this->assertCount(1, $this->csvLines($csv)); // hanya header
        $this->assertSame(1, $this->exports()->count());
    }

    public function test_second_export_lists_the_first_one(): void
    {
        $this->withSession($this->adminSession())->get(route('keamanan.export'))->assertOk();

        $csv = $this->withSession($this->adminSession())
            ->get(route('keamanan.export'))->assertOk()->streamedContent();

        $this->assertStringContainsString(SecurityEvent::LABELS['security_exported'], $csv);
        $this->assertSame(2, $this->exports()->count());
    }

    public function test_export_events_can_be_filtered_on_the_history_page(): void
    {
        $this->withSession($this->adminSession())->get(route('keamanan.export'))->assertOk();

        $this->withSession($this->adminSession())
            ->get(route('keamanan.index', ['event' => 'security_exported']))
            ->assertOk()
            ->assertViewHas('events', fn ($events) => $events->total() === 1
                && $events->first()->event === 'security_exported');
    }
}