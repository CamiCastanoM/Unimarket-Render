USE unimarket;

-- =========================================================
-- FASE 3 - UNIMARKET
-- Pedidos agrupados + correo recuperación + Google Login estable
-- No borra datos.
-- =========================================================

-- 1. Recuperación de contraseña
CREATE TABLE IF NOT EXISTS password_resets (
    id_reset INT(11) NOT NULL AUTO_INCREMENT,
    id_usuario INT(11) NOT NULL,
    token VARCHAR(120) NOT NULL,
    expira_en DATETIME NOT NULL,
    usado TINYINT(1) NOT NULL DEFAULT 0,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_reset),
    UNIQUE KEY ux_password_resets_token (token),
    KEY fk_password_resets_usuario (id_usuario),
    CONSTRAINT fk_password_resets_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2. Campos para login con Google
SET @sql = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE usuarios ADD COLUMN google_id VARCHAR(120) NULL AFTER correo',
        'SELECT "usuarios.google_id ya existe"'
    )
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'usuarios'
      AND COLUMN_NAME = 'google_id'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE usuarios ADD COLUMN auth_provider VARCHAR(30) NOT NULL DEFAULT "local" AFTER google_id',
        'SELECT "usuarios.auth_provider ya existe"'
    )
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'usuarios'
      AND COLUMN_NAME = 'auth_provider'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE usuarios ADD INDEX idx_usuarios_google_id (google_id)',
        'SELECT "idx_usuarios_google_id ya existe"'
    )
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'usuarios'
      AND INDEX_NAME = 'idx_usuarios_google_id'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE usuarios
SET auth_provider = 'local'
WHERE auth_provider IS NULL OR auth_provider = '';

-- 3. Estado y método de pago en ventas
SET @sql = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE ventas ADD COLUMN metodo_pago VARCHAR(80) DEFAULT "Pago contra entrega"',
        'SELECT "ventas.metodo_pago ya existe"'
    )
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'ventas'
      AND COLUMN_NAME = 'metodo_pago'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE ventas ADD COLUMN estado VARCHAR(50) DEFAULT "Pendiente"',
        'SELECT "ventas.estado ya existe"'
    )
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'ventas'
      AND COLUMN_NAME = 'estado'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 4. Stock inicial para barra real de ventas flash
SET @sql = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE ventas_flash ADD COLUMN stock_inicial INT(11) NOT NULL DEFAULT 0 AFTER stock_flash',
        'SELECT "ventas_flash.stock_inicial ya existe"'
    )
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'ventas_flash'
      AND COLUMN_NAME = 'stock_inicial'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE ventas_flash
SET stock_inicial = stock_flash
WHERE stock_inicial = 0
  AND stock_flash IS NOT NULL;

-- 5. Notificaciones mejoradas para compras y estados
SET @sql = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE notificaciones ADD COLUMN tipo VARCHAR(40) DEFAULT "Sistema"',
        'SELECT "notificaciones.tipo ya existe"'
    )
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'notificaciones'
      AND COLUMN_NAME = 'tipo'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE notificaciones ADD COLUMN titulo VARCHAR(120) DEFAULT NULL',
        'SELECT "notificaciones.titulo ya existe"'
    )
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'notificaciones'
      AND COLUMN_NAME = 'titulo'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE notificaciones ADD COLUMN fecha_leida DATETIME DEFAULT NULL',
        'SELECT "notificaciones.fecha_leida ya existe"'
    )
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'notificaciones'
      AND COLUMN_NAME = 'fecha_leida'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
