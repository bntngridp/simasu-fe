<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Kontribusi Bintang (BNT-02): batas sesi admin tidak aktif dan pembersihan
 * sesi beserta rotasi token CSRF saat logout atau sesi kedaluwarsa.
 */
class AdminSession
{
    public static function expired(Request $request): bool
    {
        $lastActivity = $request->session()->get('last_activity_at');

        return is_numeric($lastActivity)
            && now()->timestamp - (int) $lastActivity >= self::idleSeconds();
    }

    public static function idleSeconds(): int
    {
        return max(1, (int) config('security.idle_timeout_minutes', 30)) * 60;
    }

    public static function invalidate(Request $request): void
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
