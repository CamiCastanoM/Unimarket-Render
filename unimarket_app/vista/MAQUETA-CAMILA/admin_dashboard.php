<?php
if (!isset($usuarios) || !isset($productos)) {
    header("Location: ../../controlador/AdminController.php?accion=ver_dashboard");
    exit();
}
$isLoggedIn = isset($_SESSION['id_usuario']);
$nombreUsuario = $_SESSION['nombre'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard - UniMarket</title>
  <link rel="stylesheet" href="style/styles.css">
</head>
<body style="background: var(--gray-100); padding: 20px;">
<?php if (isset($_SESSION['flash_message'])): ?>
    <script>
        alert("<?php echo addslashes($_SESSION['flash_message']); ?>");
    </script>
    <?php unset($_SESSION['flash_message']); ?>
<?php endif; ?>
  
  <div style="background: white; border-radius: 8px; padding: 20px; max-width: 1200px; margin: 0 auto; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
      <div style="display: flex; justify-content: space-between; align-items: center;">
          <h1 style="color: var(--primary);">Panel de Administración (UniMarket)</h1>
          <div>
            <span>Bienvenido, <?php echo $nombreUsuario; ?></span>
            <a href="../../controlador/UsuarioController.php?accion=logout" class="btn-cancel" style="margin-left: 15px; text-decoration: none;">Cerrar sesión</a>
          </div>
      </div>
      <hr style="margin: 20px 0; border: 0; border-top: 1px solid #ccc;">

      <div style="display: flex; gap: 20px;">
          <!-- Bloque Usuarios -->
          <div style="flex: 1; background: #fafafa; border: 1px solid #ddd; padding: 15px; border-radius: 8px;">
              <h2>Gestión de Usuarios</h2>
              <table style="width: 100%; border-collapse: collapse; margin-top: 15px;">
                  <tr style="background: var(--primary); color: white;">
                      <th style="padding: 10px;">ID</th>
                      <th style="padding: 10px;">Nombre</th>
                      <th style="padding: 10px;">Correo</th>
                      <th style="padding: 10px;">Acción</th>
                  </tr>
                  <?php foreach ($usuarios as $u): ?>
                  <tr style="border-bottom: 1px solid #ccc; text-align: center;">
                      <td style="padding: 10px;"><?php echo $u['id_usuario']; ?></td>
                      <td style="padding: 10px;"><?php echo $u['nombre']; ?></td>
                      <td style="padding: 10px;"><?php echo $u['correo']; ?></td>
                      <td style="padding: 10px;">
                          <?php if ($u['id_rol'] != 3): ?>
                          <form action="../../controlador/AdminController.php?accion=eliminar_usuario" method="POST" style="display:inline;">
                              <input type="hidden" name="id_usuario" value="<?php echo $u['id_usuario']; ?>">
                              <button type="submit" style="background: var(--red); color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer;">Eliminar</button>
                          </form>
                          <?php else: ?>
                              <em>Admin</em>
                          <?php endif; ?>
                      </td>
                  </tr>
                  <?php endforeach; ?>
              </table>
          </div>

          <!-- Bloque Productos -->
          <div style="flex: 1; background: #fafafa; border: 1px solid #ddd; padding: 15px; border-radius: 8px;">
              <h2>Gestión de Productos Recientes</h2>
              <table style="width: 100%; border-collapse: collapse; margin-top: 15px;">
                  <tr style="background: var(--primary); color: white;">
                      <th style="padding: 10px;">ID</th>
                      <th style="padding: 10px;">Producto</th>
                      <th style="padding: 10px;">Precio</th>
                      <th style="padding: 10px;">Acción</th>
                  </tr>
                  <?php if (!empty($productos)): ?>
                      <?php foreach (array_slice($productos, 0, 5) as $p): ?>
                      <tr style="border-bottom: 1px solid #ccc; text-align: center;">
                          <td style="padding: 10px;"><?php echo $p['id_producto']; ?></td>
                          <td style="padding: 10px;"><?php echo $p['nombre']; ?></td>
                          <td style="padding: 10px;">$<?php echo constant('number_format')($p['precio'], 0, ',', '.'); ?></td>
                          <td style="padding: 10px;">
                              <form action="../../controlador/AdminController.php?accion=eliminar_producto" method="POST" style="display:inline;">
                                  <input type="hidden" name="id_producto" value="<?php echo $p['id_producto']; ?>">
                                  <button type="submit" style="background: var(--red); color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer;">Eliminar</button>
                              </form>
                          </td>
                      </tr>
                      <?php endforeach; ?>
                  <?php else: ?>
                      <tr><td colspan="3" style="text-align:center; padding: 10px;">No hay productos</td></tr>
                  <?php endif; ?>
              </table>
          </div>
      </div>
      <div style="margin-top: 20px; text-align: center;">
          <a href="index.php" style="color: var(--primary); font-weight: bold; text-decoration: none;">← Ir al Market</a>
      </div>
  </div>

</body>
</html>
