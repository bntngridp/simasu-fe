<?php

namespace App\Http\Middleware;

use App\Support\AdminSession;
use App\Support\SecurityAudit;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CekLoginApi
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! session()->has('api_token')) {
            return redirect()->route('login')->withErrors(['email' => 'Silakan login terlebih dahulu.']);
        }

        if (AdminSession::expired($request)) {
            SecurityAudit::record($request, 'session_expired', $request->session()->get('user'));
            AdminSession::invalidate($request);

            return redirect()->route('login')
                ->withErrors(['email' => 'Sesi berakhir karena tidak aktif. Silakan login kembali.'])
                ->header('Cache-Control', 'no-store, private');
        }

        $request->session()->put('last_activity_at', now()->timestamp);

        $response = $next($request);
        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }
}
