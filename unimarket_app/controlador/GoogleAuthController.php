<?php
session_start();
require_once __DIR__ . '/../config/conexion.php';

$config = require __DIR__ . '/../config/google_auth.php';
$accion = $_GET['accion'] ?? (isset($_GET['code']) ? 'callback' : 'iniciar');

function googleConfigValida($config) {
    return !empty($config['client_id'])
        && !empty($config['client_secret'])
        && !empty($config['redirect_uri'])
        && $config['client_id'] !== 'TU_GOOGLE_CLIENT_ID'
        && $config['client_secret'] !== 'TU_GOOGLE_CLIENT_SECRET';
}

function redirectAuth($mensaje, $tipo = 'warning') {
    $_SESSION['flash_type'] = $tipo;
    $_SESSION['flash_message'] = $mensaje;
    header('Location: ../vista/MAQUETA-CAMILA/index.php?view=auth');
    exit();
}

if (!googleConfigValida($config)) {
    redirectAuth('Google Login está agregado, pero falta configurar Client ID y Client Secret en config/google_auth.php.');
}

if ($accion === 'iniciar') {
    $state = bin2hex(random_bytes(16));
    if (isset($_GET['rol'])) {
        $_SESSION['google_rol'] = (int)$_GET['rol'];
    }
    $_SESSION['google_oauth_state'] = $state;

    $params = [
        'client_id' => $config['client_id'],
        'redirect_uri' => $config['redirect_uri'],
        'response_type' => 'code',
        'scope' => 'openid email profile',
        'state' => $state,
        'access_type' => 'offline',
        'prompt' => 'select_account'
    ];

    header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params));
    exit();
}

if ($accion === 'callback') {
    if (empty($_GET['state']) || empty($_SESSION['google_oauth_state']) || $_GET['state'] !== $_SESSION['google_oauth_state']) {
        redirectAuth('No se pudo validar la sesión de Google. Intenta de nuevo.', 'error');
    }

    unset($_SESSION['google_oauth_state']);

    if (empty($_GET['code'])) {
        redirectAuth('Google no devolvió un código válido.', 'error');
    }

    if (!function_exists('curl_init')) {
        redirectAuth('Tu PHP no tiene cURL habilitado. Activa extension=curl en php.ini para usar Google Login.', 'error');
    }

    $tokenData = [
        'code' => $_GET['code'],
        'client_id' => $config['client_id'],
        'client_secret' => $config['client_secret'],
        'redirect_uri' => $config['redirect_uri'],
        'grant_type' => 'authorization_code'
    ];

    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($tokenData),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_TIMEOUT => 20
    ]);
    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false || $curlError) {
        redirectAuth('No se pudo conectar con Google: ' . $curlError, 'error');
    }

    $tokens = json_decode($response, true);
    if (empty($tokens['access_token'])) {
        redirectAuth('No se pudo iniciar sesión con Google. Revisa el Client ID, Client Secret y Redirect URI.', 'error');
    }

    $ch = curl_init('https://www.googleapis.com/oauth2/v3/userinfo');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $tokens['access_token']],
        CURLOPT_TIMEOUT => 20
    ]);
    $userInfoResponse = curl_exec($ch);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($userInfoResponse === false || $curlError) {
        redirectAuth('No se pudo leer el perfil de Google: ' . $curlError, 'error');
    }

    $googleUser = json_decode($userInfoResponse, true);
    if (empty($googleUser['email']) || empty($googleUser['sub'])) {
        redirectAuth('Google no devolvió la información del usuario.', 'error');
    }

    try {
        $db = Conexion::conectar();
        $stmt = $db->prepare('SELECT * FROM usuarios WHERE google_id = ? OR correo = ? LIMIT 1');
        $stmt->execute([$googleUser['sub'], $googleUser['email']]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario) {
            $up = $db->prepare("UPDATE usuarios SET google_id = ?, auth_provider = 'google' WHERE id_usuario = ?");
            $up->execute([$googleUser['sub'], $usuario['id_usuario']]);
        } else {
            $nombre = $googleUser['name'] ?? explode('@', $googleUser['email'])[0];
            $passwordTemporal = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
            
            // Rol por defecto al registrarse con Google.
            // 2 = comprador, 1 = vendedor.
            $idRolGoogle = isset($_SESSION['google_rol']) ? (int)$_SESSION['google_rol'] : 2;
            if (!in_array($idRolGoogle, [1, 2])) {
                $idRolGoogle = 2;
            }
            unset($_SESSION['google_rol']);
            
            $ins = $db->prepare("INSERT INTO usuarios (nombre, correo, contraseña, id_rol, google_id, auth_provider) VALUES (?, ?, ?, ?, ?, 'google')");
            $ins->execute([$nombre, $googleUser['email'], $passwordTemporal, $idRolGoogle, $googleUser['sub']]);
            $idNuevo = (int)$db->lastInsertId();
            $stmt = $db->prepare('SELECT * FROM usuarios WHERE id_usuario = ? LIMIT 1');
            $stmt->execute([$idNuevo]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        redirectAuth('Google Login necesita las columnas usuarios.google_id y usuarios.auth_provider. Ejecuta el SQL de migración.', 'error');
    }

    $_SESSION['id_usuario'] = $usuario['id_usuario'];
    $_SESSION['nombre'] = $usuario['nombre'];
    $_SESSION['id_rol'] = $usuario['id_rol'];
    $_SESSION['correo'] = $usuario['correo'];
    $_SESSION['flash_type'] = 'success';
    $_SESSION['flash_message'] = 'Sesión iniciada con Google.';

    header('Location: ../vista/MAQUETA-CAMILA/index.php?view=home');
    exit();
}

redirectAuth('Acción de Google Login no válida.', 'error');
?>
