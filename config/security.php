<?php

return [
    'login_max_attempts' => (int) env('LOGIN_MAX_ATTEMPTS', 5),
    'login_decay_seconds' => (int) env('LOGIN_DECAY_SECONDS', 60),
    'idle_timeout_minutes' => (int) env('ADMIN_IDLE_TIMEOUT_MINUTES', 30),
];
