# UniMarket - Guía final para subir a Render

## 1. Qué contiene esta versión

Esta versión queda preparada para Render con:

- `Dockerfile` compatible con PHP 8.2 + Apache.
- Extensiones PHP necesarias: `pdo_mysql`, `mysqli` y `curl`.
- `render.yaml` listo para Web Service Docker.
- Redirección desde `/` hacia `/unimarket_app/vista/MAQUETA-CAMILA/index.php`.
- `.env.render.example` con todas las variables necesarias.
- `migracion_render_final.sql` para dejar la BD actualizada sin borrar datos.

## 2. Base de datos

UniMarket usa MySQL. Render tiene Postgres administrado, pero este proyecto NO está hecho en Postgres.

Opciones recomendadas:

1. Usar una base MySQL externa: Railway, Aiven, Clever Cloud, PlanetScale, hosting cPanel, etc.
2. Usar MySQL en Render con Docker y disco persistente. Esto es más complejo.
3. Para entrega/demo, usar un MySQL externo es lo más rápido.

Importa tu SQL base y luego ejecuta:

```sql
migracion_render_final.sql
```

No repitas una base completa si ya tienes datos. La migración es para agregar columnas/tablas faltantes.

## 3. Variables de entorno en Render

En Render > Web Service > Environment agrega:

```env
APP_ENV=production
APP_URL=https://TU_APP.onrender.com/unimarket_app

DB_HOST=TU_HOST_MYSQL
DB_PORT=3306
DB_NAME=unimarket
DB_USER=TU_USUARIO_MYSQL
DB_PASS=TU_PASSWORD_MYSQL

GOOGLE_CLIENT_ID=TU_CLIENT_ID.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=TU_CLIENT_SECRET
GOOGLE_REDIRECT_URI=https://TU_APP.onrender.com/unimarket_app/controlador/GoogleAuthController.php

MAIL_ENABLED=true
MAIL_HOST=smtp.gmail.com
MAIL_PORT=465
MAIL_ENCRYPTION=ssl
MAIL_USERNAME=tu_correo@gmail.com
MAIL_PASSWORD=tu_contrasena_de_aplicacion
MAIL_FROM_EMAIL=tu_correo@gmail.com
MAIL_FROM_NAME=UniMarket Unimagdalena

WOMPI_ENABLED=true
WOMPI_ENV=sandbox
WOMPI_PUBLIC_KEY=pub_test_xxxxxxxxxxxxxxxxx
WOMPI_PRIVATE_KEY=prv_test_xxxxxxxxxxxxxxxxx
WOMPI_INTEGRITY_SECRET=xxxxxxxxxxxxxxxxx
WOMPI_EVENTS_SECRET=xxxxxxxxxxxxxxxxx
```

## 4. Google Login

En Google Cloud agrega el redirect autorizado:

```txt
https://TU_APP.onrender.com/unimarket_app/controlador/GoogleAuthController.php
```

También deja el de XAMPP si seguirás probando localmente:

```txt
http://localhost/unimarket_app/controlador/GoogleAuthController.php
```

## 5. Wompi Sandbox

En Wompi configura como URL de eventos/webhook:

```txt
https://TU_APP.onrender.com/unimarket_app/controlador/PagoController.php?accion=webhook
```

Retorno de pago usado por la app:

```txt
https://TU_APP.onrender.com/unimarket_app/controlador/PagoController.php?accion=retorno&id_venta=ID
```

La app genera el retorno automáticamente. El webhook sí debes configurarlo en Wompi.

## 6. Correo real para recuperación

Para Gmail:

1. Activa verificación en dos pasos.
2. Crea una contraseña de aplicación.
3. Usa esa contraseña en `MAIL_PASSWORD`.

No uses tu contraseña normal de Gmail.

## 7. Archivos subidos por usuarios

Las imágenes se guardan en:

```txt
unimarket_app/vista/MAQUETA-CAMILA/uploads
```

En Render Free, el sistema de archivos del Web Service es efímero. Para una entrega/demo funciona, pero si el servicio se reinicia o redeploya, los archivos subidos después del deploy pueden perderse.

Para producción real usa una de estas opciones:

- Render con plan pago + persistent disk montado en `/var/www/html/unimarket_app/vista/MAQUETA-CAMILA/uploads`.
- Cloudinary / S3 / almacenamiento externo.

## 8. URL final

Cuando Render despliegue, puedes abrir:

```txt
https://TU_APP.onrender.com
```

La raíz redirige automáticamente a:

```txt
https://TU_APP.onrender.com/unimarket_app/vista/MAQUETA-CAMILA/index.php
```

## 9. Checklist final antes de entregar

- [ ] Importar SQL base en MySQL externo.
- [ ] Ejecutar `migracion_render_final.sql`.
- [ ] Crear Web Service Docker en Render.
- [ ] Configurar variables de entorno.
- [ ] Verificar que abre el home.
- [ ] Probar login normal.
- [ ] Probar registro.
- [ ] Probar recuperar contraseña.
- [ ] Probar Google Login.
- [ ] Probar publicar producto.
- [ ] Probar carrito.
- [ ] Probar pago contra entrega.
- [ ] Probar Wompi sandbox.
- [ ] Configurar webhook Wompi.
