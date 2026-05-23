<?php
session_start();
require_once __DIR__ . "/../modelo/Favorito.php";

$favModel = new Favorito();
$accion = isset($_GET['accion']) ? $_GET['accion'] : '';

if ($accion == 'toggle') {
    header('Content-Type: application/json; charset=utf-8');

    if (!isset($_SESSION['id_usuario'])) {
        echo json_encode(['status' => 'error', 'message' => 'Debes iniciar sesión']);
        exit();
    }

    $id_producto = $_POST['id_producto'] ?? null;
    $id_usuario = $_SESSION['id_usuario'];

    if (!$id_producto) {
        echo json_encode(['status' => 'error', 'message' => 'Producto inválido']);
        exit();
    }

    if ($favModel->toggle($id_usuario, $id_producto)) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error al procesar favorito']);
    }
    exit();
}

if ($accion == 'listar_html') {
    if (!isset($_SESSION['id_usuario'])) exit('');

    $favoritos = $favModel->listarPorUsuario($_SESSION['id_usuario']);
    $catMap = [1 => 'papeleria', 2 => 'tecnologia', 3 => 'comida', 4 => 'ropa', 5 => 'otros'];

    if (!empty($favoritos)) {
        foreach ($favoritos as $f) {
            $slug = $catMap[$f['id_categoria']] ?? 'otros';
            $imgPath = !empty($f['imagen']) ? 'uploads/productos/' . $f['imagen'] : 'uploads/productos/default.png';
            $nombre = htmlspecialchars($f['nombre'], ENT_QUOTES, 'UTF-8');
            $vendedor = htmlspecialchars($f['vendedor'] ?? 'Vendedor', ENT_QUOTES, 'UTF-8');
            $ubicacion = htmlspecialchars($f['ubicacion'] ?? 'Campus Unimagdalena', ENT_QUOTES, 'UTF-8');
            $descripcion = htmlspecialchars($f['descripcion'] ?? 'Producto guardado en favoritos.', ENT_QUOTES, 'UTF-8');

            echo '<div class="product-card" style="position:relative;" onclick="verProducto(event, this)" data-id="'.$f['id_producto'].'" data-id-vendedor="'.$f['id_usuario'].'" data-cat="'.$slug.'" data-ubicacion="'.$ubicacion.'" data-descripcion="'.$descripcion.'">
                    <div class="fav-btn active" onclick="event.stopPropagation(); toggleFavorito('.$f['id_producto'].', this)">❤️</div>
                    <img class="card-img" src="'.$imgPath.'" alt="'.$nombre.'">
                    <div class="card-body">
                      <div class="card-title">'.$nombre.'</div>
                      <div class="card-price">$'.number_format($f['precio'],0,',','.').'</div>
                      <div class="card-seller">🌟 '.$vendedor.'</div>
                      <button class="btn-aprovechar" onclick="event.stopPropagation(); verProducto(event, this.closest(\'.product-card\'))">Aprovechar</button>
                    </div>
                  </div>';
        }
    } else {
        echo '<div style="padding: 60px 20px; text-align: center; color: var(--gray-400); width:100%;">
                <div style="font-size: 3rem; margin-bottom: 15px;">❤️</div>
                <p>Tu lista de productos guardados está vacía.</p>
              </div>';
    }
    exit();
}

?>
