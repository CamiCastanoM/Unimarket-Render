<?php
/**
 * Configuración general compatible con XAMPP y Render.
 * En Render se recomienda definir APP_URL, DB_HOST, DB_NAME, DB_USER, DB_PASS y DB_PORT
 * como variables de entorno.
 */

function env_value($key, $default = null) {
    $value = getenv($key);
    if ($value === false || $value === '') {
        return $default;
    }
    return $value;
}

function app_base_url() {
    $configured = rtrim(env_value('APP_URL', ''), '/');
    if ($configured !== '') {
        return $configured;
    }

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host . '/unimarket_app';
}

return [
    'name' => 'UniMarket',
    'base_url' => app_base_url(),
    'environment' => env_value('APP_ENV', 'local'),
];
