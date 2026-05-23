-- =========================================================
-- UNIMARKET - MIGRACIÓN FINAL PARA RENDER / PRODUCCIÓN
-- Ejecutar dentro de la base de datos UniMarket ya importada.
-- No borra datos. Es idempotente: si algo ya existe, no lo duplica.
-- =========================================================

-- =========================
-- Usuarios: Google Login
-- =========================
SET @sql = (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE usuarios ADD COLUMN google_id VARCHAR(120) NULL AFTER correo',
        'SELECT "usuarios.google_id ya existe"')
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'google_id'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE usuarios ADD COLUMN auth_provider VARCHAR(30) NOT NULL DEFAULT "local" AFTER google_id',
        'SELECT "usuarios.auth_provider ya existe"')
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'auth_provider'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE usuarios ADD INDEX idx_usuarios_google_id (google_id)',
        'SELECT "idx_usuarios_google_id ya existe"')
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND INDEX_NAME = 'idx_usuarios_google_id'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

UPDATE usuarios SET auth_provider = 'local' WHERE auth_provider IS NULL OR auth_provider = '';

-- =========================
-- Ventas: pedidos + pagos
-- =========================
SET @sql = (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE ventas ADD COLUMN estado VARCHAR(50) DEFAULT "Pendiente" AFTER id_punto_encuentro',
        'SELECT "ventas.estado ya existe"')
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ventas' AND COLUMN_NAME = 'estado'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE ventas ADD COLUMN metodo_pago VARCHAR(80) DEFAULT "Pago contra entrega" AFTER estado',
        'SELECT "ventas.metodo_pago ya existe"')
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ventas' AND COLUMN_NAME = 'metodo_pago'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE ventas ADD COLUMN pasarela VARCHAR(40) DEFAULT "local" AFTER metodo_pago',
        'SELECT "ventas.pasarela ya existe"')
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ventas' AND COLUMN_NAME = 'pasarela'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE ventas ADD COLUMN estado_pago VARCHAR(50) DEFAULT "No aplica" AFTER pasarela',
        'SELECT "ventas.estado_pago ya existe"')
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ventas' AND COLUMN_NAME = 'estado_pago'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE ventas ADD COLUMN referencia_pago VARCHAR(120) NULL AFTER estado_pago',
        'SELECT "ventas.referencia_pago ya existe"')
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ventas' AND COLUMN_NAME = 'referencia_pago'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE ventas ADD COLUMN wompi_transaction_id VARCHAR(120) NULL AFTER referencia_pago',
        'SELECT "ventas.wompi_transaction_id ya existe"')
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ventas' AND COLUMN_NAME = 'wompi_transaction_id'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE ventas ADD COLUMN fecha_pago DATETIME NULL AFTER wompi_transaction_id',
        'SELECT "ventas.fecha_pago ya existe"')
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ventas' AND COLUMN_NAME = 'fecha_pago'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE ventas ADD COLUMN payload_pago LONGTEXT NULL AFTER fecha_pago',
        'SELECT "ventas.payload_pago ya existe"')
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ventas' AND COLUMN_NAME = 'payload_pago'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE ventas ADD INDEX idx_ventas_referencia_pago (referencia_pago)',
        'SELECT "idx_ventas_referencia_pago ya existe"')
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ventas' AND INDEX_NAME = 'idx_ventas_referencia_pago'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

UPDATE ventas
SET estado = COALESCE(NULLIF(estado, ''), 'Pendiente'),
    metodo_pago = COALESCE(NULLIF(metodo_pago, ''), 'Pago contra entrega'),
    pasarela = COALESCE(NULLIF(pasarela, ''), 'local'),
    estado_pago = COALESCE(NULLIF(estado_pago, ''), 'No aplica')
WHERE id_venta IS NOT NULL;

-- =========================
-- Ventas flash: barra real
-- =========================
SET @sql = (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE ventas_flash ADD COLUMN stock_inicial INT(11) NOT NULL DEFAULT 0 AFTER stock_flash',
        'SELECT "ventas_flash.stock_inicial ya existe"')
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ventas_flash' AND COLUMN_NAME = 'stock_inicial'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

UPDATE ventas_flash
SET stock_inicial = stock_flash
WHERE stock_inicial = 0 AND stock_flash IS NOT NULL;

-- =========================
-- Notificaciones completas
-- =========================
SET @sql = (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE notificaciones ADD COLUMN tipo VARCHAR(40) DEFAULT "Sistema" AFTER url',
        'SELECT "notificaciones.tipo ya existe"')
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notificaciones' AND COLUMN_NAME = 'tipo'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE notificaciones ADD COLUMN titulo VARCHAR(120) NULL AFTER id_usuario',
        'SELECT "notificaciones.titulo ya existe"')
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notificaciones' AND COLUMN_NAME = 'titulo'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE notificaciones ADD COLUMN fecha_leida DATETIME NULL AFTER fecha',
        'SELECT "notificaciones.fecha_leida ya existe"')
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notificaciones' AND COLUMN_NAME = 'fecha_leida'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

UPDATE notificaciones
SET tipo = CASE
    WHEN url LIKE 'chat/%' THEN 'Mensajes'
    WHEN url LIKE 'pedido/%' THEN 'Pedidos'
    ELSE COALESCE(NULLIF(tipo, ''), 'Sistema')
END,
    titulo = COALESCE(titulo, 'Aviso UniMarket')
WHERE id_notificacion IS NOT NULL;

-- =========================
-- Recuperación de contraseña
-- =========================
CREATE TABLE IF NOT EXISTS password_resets (
    id_reset INT(11) NOT NULL AUTO_INCREMENT,
    id_usuario INT(11) NOT NULL,
    token VARCHAR(120) NOT NULL,
    expira_en DATETIME NOT NULL,
    usado TINYINT(1) NOT NULL DEFAULT 0,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_reset),
    UNIQUE KEY ux_password_resets_token (token),
    KEY fk_password_resets_usuario (id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Fin de migración final.
