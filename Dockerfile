FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libcurl4-openssl-dev \
    && docker-php-ext-install pdo pdo_mysql mysqli curl \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

COPY unimarket_app /var/www/html/unimarket_app

# Página raíz para que https://tu-app.onrender.com redirija al frontend real del proyecto.
RUN printf '%s\n' '<?php header("Location: /unimarket_app/vista/MAQUETA-CAMILA/index.php"); exit; ?>' > /var/www/html/index.php

RUN chown -R www-data:www-data /var/www/html/unimarket_app \
    && find /var/www/html/unimarket_app -type d -exec chmod 755 {} \; \
    && find /var/www/html/unimarket_app -type f -exec chmod 644 {} \; \
    && chmod -R 775 /var/www/html/unimarket_app/vista/MAQUETA-CAMILA/uploads || true

EXPOSE 80
