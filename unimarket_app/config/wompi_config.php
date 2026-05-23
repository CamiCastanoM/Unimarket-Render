<?php
require_once __DIR__ . '/app_config.php';

return [
    // Cambia WOMPI_ENABLED=true en Render cuando ya tengas tus llaves sandbox/producción.
    'enabled' => filter_var(env_value('WOMPI_ENABLED', 'false'), FILTER_VALIDATE_BOOLEAN),
    'environment' => env_value('WOMPI_ENV', 'sandbox'), // sandbox | production
    'currency' => 'COP',

    // Llaves de Wompi. No las subas escritas al repositorio; usa variables de entorno en Render.
    'public_key' => env_value('WOMPI_PUBLIC_KEY', 'pub_test_REEMPLAZA_ESTA_LLAVE'),
    'private_key' => env_value('WOMPI_PRIVATE_KEY', ''),
    'events_secret' => env_value('WOMPI_EVENTS_SECRET', ''),
    'integrity_secret' => env_value('WOMPI_INTEGRITY_SECRET', 'test_integrity_REEMPLAZA_ESTA_LLAVE'),

    'sandbox_api_url' => 'https://sandbox.wompi.co/v1',
    'production_api_url' => 'https://production.wompi.co/v1',
    'checkout_widget_url' => 'https://checkout.wompi.co/widget.js',
];
