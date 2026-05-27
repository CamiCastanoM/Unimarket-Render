<?php
return [
    'enabled' => true,
    'host' => getenv('SMTP_HOST') ?: 'smtp.gmail.com',
    'port' => getenv('SMTP_PORT') ?: 465,
    'username' => getenv('SMTP_USER') ?: '',
    'password' => getenv('SMTP_PASS') ?: '',
    'from_email' => getenv('SMTP_USER') ?: '',
    'from_name' => getenv('SMTP_FROM') ?: 'UniMarket',
    'encryption' => 'ssl',
];
