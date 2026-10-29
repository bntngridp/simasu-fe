<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

class AuthController extends Controller
{
    // Menampilkan halaman login
    public function showLoginForm()
    {
        if (Session::has('api_token')) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        try {
            $baseUrl = config('api.base_url');
            $response = Http::withoutVerifying()->post("{$baseUrl}/api/login", [
                'email' => $request->email,
                'password' => $request->password,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                Session::put('api_token', $data['token'] ?? null);
                Session::put('user', $data['user'] ?? []);

                if (($data['user']['role'] ?? '') !== 'admin') {
                    Session::flush();
                    return back()->withErrors(['email' => 'Akun ini bukan admin.']);
                }

                return redirect()->route('dashboard')->with('success', 'Login berhasil!');
            }
            return back()->withErrors([
                'email' => 'Email atau password salah.',
            ])->withInput($request->only('email'));
        } catch (\Exception $e) {
            return back()->withErrors([
                'email' => 'Gagal terhubung ke server API. Coba lagi nanti.',
            ]);
        }
    }
    public function logout()
    {
        Session::forget(['api_token', 'user']);
        return redirect()->route('login')->with('success', 'Berhasil logout.');
    }
}