<?php

namespace App\Http\Controllers;

use App\Support\AdminSession;
use App\Support\SecurityAudit;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Str;
use Illuminate\Support\ViewErrorBag;

class AuthController extends Controller
{
    public function showLoginForm(Request $request)
    {
        if ($request->session()->has('api_token')) {
            if (! AdminSession::expired($request)) {
                return redirect()->route('dashboard');
            }

            SecurityAudit::record($request, 'session_expired', $request->session()->get('user'));
            AdminSession::invalidate($request);

            return redirect()->route('login')
                ->withErrors(['email' => 'Sesi berakhir karena tidak aktif. Silakan login kembali.']);
        }

        return response()->view('auth.login')->header('Cache-Control', 'no-store, private');
    }

    /**
     * Kontribusi Bintang (BNT-01): pembatasan percobaan login per email dan IP,
     * penanganan kegagalan API, serta reset penghitung saat login berhasil.
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:254'],
            'password' => ['required', 'string'],
        ]);

        $email = Str::lower(trim($validated['email']));
        $key = 'admin-login:'.hash('sha256', $email.'|'.$request->ip());
        $limit = max(1, (int) config('security.login_max_attempts', 5));
        $decay = max(1, (int) config('security.login_decay_seconds', 60));

        if (RateLimiter::tooManyAttempts($key, $limit)) {
            $seconds = max(1, RateLimiter::availableIn($key));
            SecurityAudit::record($request, 'login_throttled', email: $email);
            $message = "Terlalu banyak percobaan login. Coba lagi dalam {$seconds} detik.";

            if ($request->expectsJson()) {
                return response()->json(['message' => $message, 'retry_after' => $seconds], 429)
                    ->header('Retry-After', (string) $seconds);
            }

            return response()->view('auth.login', [
                'email' => $email,
                'errors' => (new ViewErrorBag)->put('default', new MessageBag(['email' => $message])),
            ], 429)->header('Retry-After', (string) $seconds)->header('Cache-Control', 'no-store, private');
        }

        try {
            $response = Http::connectTimeout(3)->timeout(10)->post(config('api.base_url').'/api/login', [
                'email' => $email,
                'password' => $validated['password'],
            ]);
        } catch (ConnectionException) {
            return $this->unavailable($request, $email);
        }

        if ($response->status() === 401) {
            RateLimiter::hit($key, $decay);
            SecurityAudit::record($request, 'login_failed', email: $email);

            return back()->withErrors(['email' => 'Email atau password salah.'])
                ->withInput(['email' => $email]);
        }

        $data = $response->json();
        if (! $response->successful() || ! is_array($data)
            || ! is_string($data['token'] ?? null) || trim($data['token']) === ''
            || ! is_array($data['user'] ?? null) || ! isset($data['user']['role'])) {
            return $this->unavailable($request, $email);
        }

        if ($data['user']['role'] !== 'admin') {
            RateLimiter::hit($key, $decay);
            SecurityAudit::record($request, 'access_denied', $data['user'], $email);
            AdminSession::invalidate($request);

            return back()->withErrors(['email' => 'Akun ini bukan admin.'])->withInput(['email' => $email]);
        }

        RateLimiter::clear($key);
        SecurityAudit::record($request, 'login_succeeded', $data['user'], $email);
        $request->session()->regenerate(true);
        $request->session()->put([
            'api_token' => $data['token'],
            'user' => $data['user'],
            'last_activity_at' => now()->timestamp,
        ]);

        return redirect()->route('dashboard')->with('success', 'Login berhasil!');
    }

    public function logout(Request $request)
    {
        if ($request->session()->has('api_token')) {
            SecurityAudit::record($request, 'logout', $request->session()->get('user'));
        }

        AdminSession::invalidate($request);

        return redirect()->route('login')->with('success', 'Berhasil logout.')
            ->header('Cache-Control', 'no-store, private');
    }

    private function unavailable(Request $request, string $email)
    {
        SecurityAudit::record($request, 'api_unavailable', email: $email);

        return back()->withErrors(['email' => 'Layanan login sedang tidak tersedia. Coba lagi nanti.'])
            ->withInput(['email' => $email]);
    }
}
