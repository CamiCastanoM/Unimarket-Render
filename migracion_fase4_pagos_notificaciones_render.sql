USE unimarket;

-- =========================================================
-- FASE 4 - UniMarket
-- Pagos Wompi sandbox/producción, notificaciones completas y despliegue Render
-- No borra datos.
-- =========================================================

-- Ventas: estado general si aún no existe
SET @sql = (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE ventas ADD COLUMN estado VARCHAR(50) DEFAULT "Pendiente" AFTER id_punto_encuentro',
        'SELECT "ventas.estado ya existe"')
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ventas' AND COLUMN_NAME = 'estado'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Ventas: método de pago si aún no existe
SET @sql = (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE ventas ADD COLUMN metodo_pago VARCHAR(80) DEFAULT "Pago contra entrega" AFTER estado',
        'SELECT "ventas.metodo_pago ya existe"')
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ventas' AND COLUMN_NAME = 'metodo_pago'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Pagos
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

-- Notificaciones: campos para bandeja real
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

-- Recuperación de contraseña con correo real
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
