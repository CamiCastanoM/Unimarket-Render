<?php
require_once __DIR__ . '/app_config.php';

/*
  Correo real para recuperación de contraseña.
  En Render define estas variables de entorno:
  MAIL_ENABLED=true
  MAIL_HOST=smtp.gmail.com
  MAIL_PORT=465
  MAIL_ENCRYPTION=ssl
  MAIL_USERNAME=tu_correo@gmail.com
  MAIL_PASSWORD=tu_contrasena_de_aplicacion
  MAIL_FROM_EMAIL=tu_correo@gmail.com
  MAIL_FROM_NAME=UniMarket Unimagdalena
*/
return [
    'enabled' => filter_var(env_value('MAIL_ENABLED', 'false'), FILTER_VALIDATE_BOOLEAN),
    'host' => env_value('MAIL_HOST', 'smtp.gmail.com'),
    'port' => (int)env_value('MAIL_PORT', '465'),
    'encryption' => env_value('MAIL_ENCRYPTION', 'ssl'),
    'username' => env_value('MAIL_USERNAME', 'tu_correo@gmail.com'),
    'password' => env_value('MAIL_PASSWORD', 'TU_CONTRASENA_DE_APLICACION'),
    'from_email' => env_value('MAIL_FROM_EMAIL', env_value('MAIL_USERNAME', 'tu_correo@gmail.com')),
    'from_name' => env_value('MAIL_FROM_NAME', 'UniMarket Unimagdalena'),
];
