<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('app.url')) {
            URL::forceRootUrl(config('app.url'));
        }

        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        // Kontribusi Aryoseto (ARY-03): batas frekuensi ekspor riwayat keamanan.
        RateLimiter::for('security-export', function (Request $request) {
            $identity = $request->hasSession()
                ? Str::lower((string) $request->session()->get('user.email', 'guest'))
                : 'guest';

            return Limit::perMinute(max(1, (int) config('security.export_max_attempts', 5)))
                ->by($identity.'|'.$request->ip());
        });
    }
}