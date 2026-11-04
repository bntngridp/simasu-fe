<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecurityEvent extends Model
{
    public const UPDATED_AT = null;

    public const LABELS = [
        'login_succeeded' => 'Login berhasil',
        'login_failed' => 'Login gagal',
        'login_throttled' => 'Login dibatasi',
        'access_denied' => 'Akses non-admin ditolak',
        'api_unavailable' => 'Layanan login tidak tersedia',
        'logout' => 'Logout',
        'session_expired' => 'Sesi kedaluwarsa',
    ];

    protected $fillable = ['event', 'email', 'api_user_id', 'ip_address'];
}
