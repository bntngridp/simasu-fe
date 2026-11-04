<?php

namespace App\Support;

use App\Models\SecurityEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SecurityAudit
{
    public static function record(Request $request, string $event, ?array $user = null, ?string $email = null): void
    {
        SecurityEvent::create([
            'event' => $event,
            'email' => ($value = $email ?? ($user['email'] ?? null))
                ? Str::limit(Str::lower(trim($value)), 254, '') : null,
            'api_user_id' => isset($user['id']) && is_numeric($user['id']) ? (int) $user['id'] : null,
            'ip_address' => $request->ip(),
        ]);
    }
}
