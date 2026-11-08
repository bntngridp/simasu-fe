<?php

return [
    'base_url' => env('API_BASE_URL', 'https://api.indrayuda.my.id'),

    // Set API_VERIFY_SSL=false hanya untuk development lokal bila sertifikat bermasalah.
    'verify_ssl' => (bool) env('API_VERIFY_SSL', true),
];
