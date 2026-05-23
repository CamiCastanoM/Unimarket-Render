<?php
session_start();

if (!isset($_SESSION['id_usuario'])) {
    header("Location: index.php");
    exit();
}

require_once "../../modelo/VentaFlash.php";

$vfModel = new VentaFlash();

$ventas = $vfModel->listarPorUsuario($_SESSION['id_usuario']);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Mis Ventas Flash</title>

    <style>

        body{
            font-family: Arial;
            background: #f5f5f5;
            margin: 0;
            padding: 20px;
        }

        h1{
            margin-bottom: 30px;
        }

        .contenedor{
            display: grid;
            grid-template-columns: repeat(auto-fill,minmax(300px,1fr));
            gap: 20px;
        }

        .card{
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }

        .card img{
            width: 100%;
            height: 220px;
            object-fit: cover;
        }

        .contenido{
            padding: 15px;
        }

        .precio{
            color: #e63946;
            font-size: 22px;
            font-weight: bold;
        }

        .stock{
            margin-top: 10px;
        }

        .ubicacion{
            color: gray;
            margin-top: 5px;
        }

        .acciones{
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .btn{
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
        }

        .editar{
            background: #457b9d;
            color: white;
        }

        .eliminar{
            background: #e63946;
            color: white;
        }

    </style>
</head>

<body>

<h1>Mis Ventas Flash ⚡</h1>

<div class="contenedor">

<?php foreach($ventas as $v): ?>

    <div class="card">

        <img src="uploads/productos/<?= $v['imagen'] ?>" alt="">

        <div class="contenido">

            <h2><?= $v['nombre'] ?></h2>

            <div class="precio">
                $<?= number_format($v['precio_oferta']) ?>
            </div>

            <div class="stock">
                Stock: <?= $v['stock_flash'] ?>
            </div>

            <div class="ubicacion">
                📍 <?= $v['ubicacion'] ?>
            </div>

            <div class="acciones">

                <a href="editar_venta_flash.php?id=<?= $v['id_flash'] ?>">

                     <button class="btn editar">
                        Editar
                     </button>

                </a>

                <a href="../../controlador/FlashController.php?accion=eliminar&id=<?= $v['id_flash'] ?>"
                    onclick="return confirm('¿Eliminar esta venta flash?')">

                        <button class="btn eliminar">
                            Eliminar
                        </button>

                 </a>

            </div>

        </div>

    </div>

<?php endforeach; ?>

</div>

</body>
</html>