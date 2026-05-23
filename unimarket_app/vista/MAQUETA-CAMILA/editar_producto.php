<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if(!isset($producto)){
    die('Producto no existe');
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <title>Editar producto</title>

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Sora:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style/styles.css">

</head>

<body class="um-standalone-body">
<main class="um-form-shell">
  <header class="um-form-navbar">
    <a class="um-form-brand" href="index.php?view=profile">
      <img src="assets/img/unimagdalena-logo.png" alt="Universidad del Magdalena">
      <div>UniMarket<span>Universidad del Magdalena</span></div>
    </a>
    <a class="btn-cancel" href="index.php?view=profile">Volver al perfil</a>
  </header>

  <div class="breadcrumb" style="margin-bottom:18px;">
    <a href="index.php?view=home">Inicio</a><span>›</span>
    <a href="index.php?view=profile">Mis productos</a><span>›</span><span>Editar producto</span>
  </div>

<div class="um-form-card" style="max-width:880px;margin:0 auto;">

    <h1>Editar producto</h1>
    <p>Actualiza la información del producto para mantener tu tienda clara y atractiva.</p>

    <form action="/unimarket_app/controlador/ProductoController.php"
      method="POST"
      enctype="multipart/form-data">

    <input type="hidden"
           name="accion"
           value="actualizar">

    <input type="hidden"
           name="id_producto"
           value="<?php echo $producto['id_producto']; ?>">

    <div class="form-group">

        <label class="form-label">Foto del producto</label>

        <div class="upload-drop"
             onclick="document.getElementById('input-imagen').click()"
             style="cursor:pointer">

            <input type="file"
                   name="imagen"
                   id="input-imagen"
                   style="display:none"
                   accept="image/*"
                   onchange="handleImagePreview(this)">

            <div id="preview-img-cont" style="margin-bottom:10px">

                <img src="uploads/productos/<?php echo $producto['imagen']; ?>"
                     style="max-width:180px;border-radius:12px">

            </div>

            <span id="file-name">
                Cambiar imagen
            </span>

        </div>

    </div>

    <div class="form-group">

            <label class="form-label">
                Título del producto
            </label>

            <input type="text"
                id="pub-title"
                name="nombre"
                class="form-input"
                value="<?php echo $producto['nombre']; ?>"
                required>

        </div>

        <div class="form-group">

            <label class="form-label">
                Descripción
            </label>

            <textarea name="descripcion"
                    class="form-input"
                    required><?php echo $producto['descripcion']; ?></textarea>

        </div>

        <div class="form-group">

            <label class="form-label">
                Categoría
            </label>

            <select name="id_categoria"
                    class="form-input"
                    required>

                <option value="1"
                    <?php if($producto['id_categoria']==1) echo 'selected'; ?>>
                    Papelería
                </option>

                <option value="2"
                    <?php if($producto['id_categoria']==2) echo 'selected'; ?>>
                    Tecnología
                </option>

                <option value="3"
                    <?php if($producto['id_categoria']==3) echo 'selected'; ?>>
                    Comida
                </option>

                <option value="4"
                    <?php if($producto['id_categoria']==4) echo 'selected'; ?>>
                    Ropa
                </option>

                <option value="5"
                    <?php if($producto['id_categoria']==5) echo 'selected'; ?>>
                    Otros
                </option>

            </select>

        </div>

        <div class="form-group">

            <label class="form-label">
                Precio (COP)
            </label>

            <input type="number"
                id="pub-price"
                name="precio"
                class="form-input"
                value="<?php echo $producto['precio']; ?>"
                required>

        </div>

        <div class="form-group">

            <label class="form-label">
                Punto de entrega en campus
            </label>

            <select name="ubicacion"
                    class="form-input"
                    required>

                <option value="Cafetería Central"
                    <?php if($producto['ubicacion']=='Cafetería Central') echo 'selected'; ?>>
                    ☕ Cafetería Central
                </option>

                <option value="Bloque VIII"
                    <?php if($producto['ubicacion']=='Bloque VIII') echo 'selected'; ?>>
                    🏢 Edificio Bloque VIII
                </option>

                <option value="Zona de Hamacas"
                    <?php if($producto['ubicacion']=='Zona de Hamacas') echo 'selected'; ?>>
                    🌴 Zona de Hamacas
                </option>

                <option value="Biblioteca"
                    <?php if($producto['ubicacion']=='Biblioteca') echo 'selected'; ?>>
                    📚 Biblioteca
                </option>

                <option value="Hemiciclo"
                    <?php if($producto['ubicacion']=='Hemiciclo') echo 'selected'; ?>>
                    🏛️ Hemiciclo Cultural
                </option>

                <option value="Canchas Deportivas"
                    <?php if($producto['ubicacion']=='Canchas Deportivas') echo 'selected'; ?>>
                    🎾 Canchas deportivas
                </option>

            </select>

        </div>

        <div class="form-actions">

            <button type="button"
                    class="btn-cancel"
                    onclick="window.history.back()">

                Cancelar

            </button>

            <button type="submit"
                    class="btn-siguiente">

                Guardar cambios →

            </button>

        </div>

    </form>

</div>
</main>
<script>function handleImagePreview(input){const cont=document.getElementById("preview-img-cont");const fileName=document.getElementById("file-name");if(input.files&&input.files[0]){const r=new FileReader();r.onload=e=>{cont.innerHTML=`<img src="${e.target.result}" style="max-width:180px;border-radius:18px">`;};r.readAsDataURL(input.files[0]);fileName.textContent=input.files[0].name;}}</script>
</body>
</html>