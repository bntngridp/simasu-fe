<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ProfilController extends Controller
{
    public function index()
    {
        $baseUrl = config('api.base_url');
        $token = session('api_token');

        if (!$token) {
            return redirect()->route('login')->with('error', 'Sesi kadaluarsa, silakan login kembali.');
        }

        $response = Http::withoutVerifying()
            ->withToken($token)
            ->get($baseUrl . '/api/profile');

        if ($response->successful()) {
            $userData = $response->json();
            
            $user = (object) [
                'name'    => $userData['name'] ?? '-',
                'role'    => $userData['role'] ?? 'User',
                'email'   => $userData['email'] ?? '-',
                'phone'   => $userData['phone'] ?? '',
                'address' => $userData['address'] ?? '',
                'profile_photo' => $userData['profile_photo'] ?? null,
                'created_at' => isset($userData['createdAt']) ? $userData['createdAt'] : now(),
            ];
            
            return view('profil.index', compact('user'));
        }

        return redirect()->route('profil')->with('error', 'Gagal mengambil data profil. Pastikan API berjalan.');
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'required|email',
            'phone'   => 'nullable|string',
            'address' => 'nullable|string',
        ]);

        $baseUrl = config('api.base_url');
        $token = session('api_token');

        $response = Http::withoutVerifying()
            ->withToken($token)
            ->put($baseUrl . '/api/profile', $validated);

        if ($response->successful()) {
            $userData = session('user');
            $userData['name'] = $validated['name'];
            $userData['email'] = $validated['email'];
            $userData['phone'] = $validated['phone'] ?? $userData['phone'];
            $userData['address'] = $validated['address'] ?? $userData['address'];
            session(['user' => $userData]);
            return back()->with('success', 'Profil berhasil diperbarui!');
        }

        $errorMessage = $response->json()['message'] ?? 'Terjadi kesalahan saat menyimpan.';
        return back()->with('error', 'Gagal memperbarui profil: ' . $errorMessage);
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required',
            'new_password'     => 'required|min:6|confirmed',
        ]);

        $baseUrl = config('api.base_url');
        $token = session('api_token');

        $response = Http::withoutVerifying()
            ->withToken($token)
            ->put($baseUrl . '/api/profile/password', [
                'current_password' => $validated['current_password'],
                'new_password'     => $validated['new_password'],
            ]);

        if ($response->successful()) {
            return back()->with('success', 'Password berhasil diubah!');
        }

        $errorMessage = $response->json()['message'] ?? 'Password lama salah atau terjadi kesalahan.';
        return back()->with('error', 'Gagal mengubah password: ' . $errorMessage);
    }

    public function updatePhoto(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $baseUrl = config('api.base_url');
        $token = session('api_token');

        $file = $request->file('photo');

        $response = Http::withoutVerifying()
            ->withToken($token)
            ->attach(
                'photo',
                file_get_contents($file),
                $file->getClientOriginalName()
            )
            ->post($baseUrl . '/api/profile/photo');
        if ($response->successful()) {
            $userData = session('user');
            $userData['profile_photo'] = $response->json()['user']['profile_photo']; 
            session(['user' => $userData]);

            return back()->with('success', 'Foto profil berhasil diperbarui!');
        }
        return back()->with('error', 'Gagal upload foto: ' . ($response->json()['message'] ?? 'Server Error'));
    }
}