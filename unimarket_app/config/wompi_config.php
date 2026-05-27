<?php

return [
    'enabled' => getenv('WOMPI_ENABLED') === 'true',

    'environment' => getenv('WOMPI_ENV') ?: 'sandbox',

    'public_key' => getenv('WOMPI_PUBLIC_KEY') ?: '',

    'private_key' => getenv('WOMPI_PRIVATE_KEY') ?: '',

    'integrity_secret' => getenv('WOMPI_INTEGRITY_SECRET') ?: '',

    'currency' => getenv('WOMPI_CURRENCY') ?: 'COP',

    'sandbox_api_url' => 'https://sandbox.wompi.co/v1',

    'production_api_url' => 'https://production.wompi.co/v1',

    'checkout_widget_url' => 'https://checkout.wompi.co/widget.js'
];
