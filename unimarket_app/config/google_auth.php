<?php
require_once __DIR__ . '/app_config.php';

return [
    'client_id' => env_value('GOOGLE_CLIENT_ID', 'TU_GOOGLE_CLIENT_ID'),
    'client_secret' => env_value('GOOGLE_CLIENT_SECRET', 'TU_GOOGLE_CLIENT_SECRET'),
    'redirect_uri' => env_value('GOOGLE_REDIRECT_URI', rtrim(app_base_url(), '/') . '/controlador/GoogleAuthController.php'),
];
