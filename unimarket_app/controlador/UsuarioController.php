<?php
require_once "../modelo/Usuario.php";
require_once "../config/Mailer.php";
require_once "../config/app_config.php";

$usuarioModel = new Usuario();

//registrar o login
$accion = isset($_GET['accion']) ? $_GET['accion'] : '';


//registro
if ($accion == 'registrar') {

    session_start();

    $nombre = $_POST['nombre'];
    $correo = $_POST['correo'];
    $contrasena = $_POST['contrasena'];
    $id_rol = $_POST['id_rol'];

    $existe = $usuarioModel->buscarPorCorreo($correo);

    if ($existe) {
        $_SESSION['flash_type'] = 'error';
        $_SESSION['flash_message'] = 'Ese correo ya está ocupado.';
        header("Location: ../vista/MAQUETA-CAMILA/index.php?view=auth");
        exit();
    }

    // Le pedimos al modelo que lo guarde
    if ($usuarioModel->registrar($nombre, $correo, $contrasena, $id_rol)) {

        $_SESSION['flash_type'] = 'success';
        $_SESSION['flash_message'] = '¡Registro exitoso! Ya puedes iniciar sesión.';

    } else {

        $_SESSION['flash_type'] = 'error';
        $_SESSION['flash_message'] = 'Hubo un error al registrar.';
    }

    header("Location: ../vista/MAQUETA-CAMILA/index.php?view=auth");
    exit();
}

// login
if ($accion == 'login') {
    session_start();

    $correo = $_POST['correo'];
    $contrasena = $_POST['contrasena'];

    $usuario = $usuarioModel->buscarPorCorreo($correo);

    if ($usuario && password_verify($contrasena, $usuario['contraseña'])) {
        $_SESSION['id_usuario'] = $usuario['id_usuario'];
        $_SESSION['nombre'] = $usuario['nombre'];
        $_SESSION['id_rol'] = $usuario['id_rol'];
        $_SESSION['correo'] = $usuario['correo'];
        $_SESSION['flash_message'] = '¡Bienvenida/o a UniMarket, ' . $usuario['nombre'] . '!';

        header("Location: ../vista/MAQUETA-CAMILA/index.php?view=home");
        exit();
    }

    $_SESSION['flash_message'] = 'Correo o contraseña incorrectos.';
    header("Location: ../vista/MAQUETA-CAMILA/index.php?view=auth");
    exit();
}


// solicitar recuperación de contraseña
if ($accion == 'solicitar_recuperacion') {
    session_start();

    $correo = trim($_POST['correo'] ?? '');
    $usuario = $usuarioModel->buscarPorCorreo($correo);

    // Respuesta segura: no revelamos si el correo existe o no.
    if (!$usuario) {
        $_SESSION['flash_type'] = 'info';
        $_SESSION['flash_message'] = 'Si el correo está registrado, recibirás un enlace de recuperación.';
        header("Location: ../vista/MAQUETA-CAMILA/index.php?view=forgot");
        exit();
    }

    try {
        $db = Conexion::conectar();
        $token = bin2hex(random_bytes(24));
        $expira = date('Y-m-d H:i:s', strtotime('+30 minutes'));

        $stmt = $db->prepare("INSERT INTO password_resets (id_usuario, token, expira_en, usado) VALUES (?, ?, ?, 0)");
        $stmt->execute([$usuario['id_usuario'], $token, $expira]);

        $baseUrl = rtrim(app_base_url(), '/') . '/vista/MAQUETA-CAMILA/index.php?token=' . urlencode($token);

        $html = '
            <div style="font-family:Arial,sans-serif; color:#111827; line-height:1.6;">
                <h2 style="color:#0b4a6f;">Recupera tu contraseña de UniMarket</h2>
                <p>Hola ' . htmlspecialchars($usuario['nombre']) . ',</p>
                <p>Recibimos una solicitud para restablecer tu contraseña.</p>
                <p><a href="' . htmlspecialchars($baseUrl) . '" style="background:#0b4a6f;color:white;padding:12px 18px;border-radius:10px;text-decoration:none;display:inline-block;">Cambiar contraseña</a></p>
                <p>Este enlace vence en 30 minutos.</p>
                <p>Si no solicitaste este cambio, ignora este correo.</p>
            </div>';

        $mailError = null;
        $enviado = Mailer::enviar($correo, 'Recuperar contraseña - UniMarket', $html, $mailError);

        if ($enviado) {
            $_SESSION['flash_type'] = 'success';
            $_SESSION['flash_message'] = 'Te enviamos un enlace de recuperación al correo registrado.';
            unset($_SESSION['reset_link_local']);
        } else {
            $_SESSION['flash_type'] = 'warning';
            $_SESSION['flash_message'] = 'Modo local: el correo real no está activo. Usa este enlace temporal para probar la recuperación.';
            $_SESSION['reset_link_local'] = $baseUrl;
        }
    } catch (PDOException $e) {
        $_SESSION['flash_type'] = 'error';
        $_SESSION['flash_message'] = 'Primero debes crear la tabla password_resets con el SQL de migración.';
    }

    header("Location: ../vista/MAQUETA-CAMILA/index.php?view=forgot");
    exit();
}

// restablecer contraseña con token
if ($accion == 'restablecer_contrasena') {
    session_start();

    $token = $_POST['token'] ?? '';
    $contrasena = $_POST['contrasena'] ?? '';
    $confirmar = $_POST['confirmar_contrasena'] ?? '';

    if ($contrasena === '' || $contrasena !== $confirmar) {
        $_SESSION['flash_message'] = 'Las contraseñas no coinciden.';
        header("Location: ../vista/MAQUETA-CAMILA/index.php?token=" . urlencode($token));
        exit();
    }

    try {
        $db = Conexion::conectar();
        $stmt = $db->prepare("SELECT * FROM password_resets WHERE token = ? AND usado = 0 AND expira_en >= NOW() ORDER BY id_reset DESC LIMIT 1");
        $stmt->execute([$token]);
        $reset = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$reset) {
            $_SESSION['flash_message'] = 'El token no existe o ya expiró.';
            header("Location: ../vista/MAQUETA-CAMILA/index.php?view=forgot");
            exit();
        }

        $stmtUpdate = $db->prepare("UPDATE usuarios SET contraseña = ? WHERE id_usuario = ?");
        $stmtUpdate->execute([password_hash($contrasena, PASSWORD_DEFAULT), $reset['id_usuario']]);

        $stmtUsed = $db->prepare("UPDATE password_resets SET usado = 1 WHERE id_reset = ?");
        $stmtUsed->execute([$reset['id_reset']]);

        $_SESSION['flash_message'] = 'Contraseña actualizada. Ya puedes iniciar sesión.';
        header("Location: ../vista/MAQUETA-CAMILA/index.php?view=auth");
        exit();
    } catch (PDOException $e) {
        $_SESSION['flash_message'] = 'Error restableciendo contraseña. Verifica la tabla password_resets.';
        header("Location: ../vista/MAQUETA-CAMILA/index.php?view=forgot");
        exit();
    }
}

// Juntar acciones en un switch/vaina similar para mayor orden si sigue creciendo
switch($accion) {
    case 'logout':
        session_start();
        session_destroy();
        header("Location: ../vista/MAQUETA-CAMILA/index.php");
    exit();
    case 'obtener_info':
        $id = $_GET['id'] ?? null;
        if($id) {
            $u = $usuarioModel->buscarPorId($id);
            echo json_encode(['status' => 'success', 'data' => $u]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'ID no proporcionado']);
        }
    exit();
    case 'actualizar':

        session_start();

        $id_usuario =
            $_SESSION['id_usuario'] ?? null;

        if(!$id_usuario){

            header("Location: ../vista/MAQUETA-CAMILA/index.php");

            exit();
        }

        $usuarioActual =
            $usuarioModel->obtenerPorId($id_usuario);

        $id_rol =
            $_SESSION['id_rol'];

        $nombre =
            trim($_POST['nombre']);

        $telefono =
            $_POST['telefono'] ?? null;

        $descripcion_tienda =
            $_POST['descripcion_tienda'] ?? null;

        $nuevaPassword =
            $_POST['contrasena'] ?? '';

        $confirmarPassword =
            $_POST['confirmar_contrasena'] ?? '';

        $passwordActual =
            $_POST['contrasena_actual'] ?? '';

        /*
        ======================================
        VALIDAR CAMBIO CONTRASEÑA
        ======================================
        */

        $passwordFinal = null;

        if(!empty($nuevaPassword)){

            // verificar contraseña actual
            if(
                !password_verify(
                    $passwordActual,
                    $usuarioActual['contraseña']
                )
            ){

                $_SESSION['flash_message'] =
                    'La contraseña actual es incorrecta';

                header("Location: ../vista/MAQUETA-CAMILA/index.php?view=edit-profile");

                exit();
            }

            // confirmar nueva
            if($nuevaPassword !== $confirmarPassword){

                $_SESSION['flash_message'] =
                    'Las nuevas contraseñas no coinciden';

                header("Location: ../vista/MAQUETA-CAMILA/index.php?view=edit-profile");

                exit();
            }

            $passwordFinal =
                $nuevaPassword;
        }

        /*
        ======================================
        FOTO PERFIL SOLO COMPRADOR
        ======================================
        */

        $foto_perfil =
            $usuarioActual['foto_perfil'] ?? null;

        if(
            $id_rol != 1 &&
            isset($_FILES['foto_perfil']) &&
            $_FILES['foto_perfil']['error'] == 0
        ){

            $nombreFoto =
                time() . '_perfil_' .
                $_FILES['foto_perfil']['name'];

            move_uploaded_file(
                $_FILES['foto_perfil']['tmp_name'],
                "../vista/MAQUETA-CAMILA/uploads/perfiles/" .
                $nombreFoto
            );

            $foto_perfil = $nombreFoto;
        }

        /*
        ======================================
        LOGO Y BANNER SOLO VENDEDOR
        ======================================
        */

        $logo =
            $usuarioActual['logo'] ?? null;

        $banner =
            $usuarioActual['banner'] ?? null;

        if($id_rol == 1){

            // LOGO

            if(
                isset($_FILES['logo']) &&
                $_FILES['logo']['error'] == 0
            ){

                $nombreLogo =
                    time() . '_logo_' .
                    $_FILES['logo']['name'];

                move_uploaded_file(
                    $_FILES['logo']['tmp_name'],
                    "../vista/MAQUETA-CAMILA/uploads/tiendas/" .
                    $nombreLogo
                );

                $logo = $nombreLogo;
            }

            // BANNER

            if(
                isset($_FILES['banner']) &&
                $_FILES['banner']['error'] == 0
            ){

                $nombreBanner =
                    time() . '_banner_' .
                    $_FILES['banner']['name'];

                move_uploaded_file(
                    $_FILES['banner']['tmp_name'],
                    "../vista/MAQUETA-CAMILA/uploads/tiendas/" .
                    $nombreBanner
                );

                $banner = $nombreBanner;
            }

        }else{

            // comprador NO usa logo/banner

            $logo = null;

            $banner = null;
        }

        /*
        ======================================
        ACTUALIZAR USUARIO
        ======================================
        */

        $sql = "UPDATE usuarios SET
                    nombre = ?,
                    telefono = ?,
                    foto_perfil = ?,
                    logo = ?,
                    banner = ?,
                    descripcion_tienda = ?";

        $params = [
            $nombre,
            $telefono,
            $foto_perfil,
            $logo,
            $banner,
            $descripcion_tienda
        ];

        /*
        ======================================
        CONTRASEÑA OPCIONAL
        ======================================
        */

        if($passwordFinal){

            $sql .= ", contraseña = ?";

            $params[] =
                password_hash(
                    $passwordFinal,
                    PASSWORD_DEFAULT
                );
        }

        $sql .= " WHERE id_usuario = ?";

        $params[] = $id_usuario;

        $db = Conexion::conectar();

        $stmt = $db->prepare($sql);

        /*
        ======================================
        EJECUTAR
        ======================================
        */

        if($stmt->execute($params)){

            // actualizar sesión

            $_SESSION['nombre'] =
                $nombre;

            $_SESSION['telefono'] =
                $telefono;

            $_SESSION['foto_perfil'] =
                $foto_perfil;

            $_SESSION['logo'] =
                $logo;

            $_SESSION['banner'] =
                $banner;

            $_SESSION['descripcion_tienda'] =
                $descripcion_tienda;

            $_SESSION['flash_message'] =
                'Perfil actualizado correctamente';

        } else {

            $_SESSION['flash_message'] =
                'Error al actualizar el perfil';
        }

        header("Location: ../vista/MAQUETA-CAMILA/index.php?view=profile");

    exit();
    case 'obtenerTelefono':
        require_once "../modelo/Usuario.php";
require_once "../config/Mailer.php";
require_once "../config/app_config.php";
        $usuarioModel = new Usuario();
        $usuario =
            $usuarioModel->obtenerPorId($_GET['id']);
        echo json_encode([
            'telefono' => $usuario['telefono'] ?? ''
        ]);
    break;
}
?>
