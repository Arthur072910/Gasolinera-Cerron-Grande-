-- ============================================================
-- BASE DE DATOS: Gasolinera El Cerron Grande
-- Normalizacion: 3FN (Tercera Forma Normal)
-- Motor: InnoDB (soporte de claves foraneas y transacciones)
--
-- Como importarla (phpMyAdmin):
--   1. Crea/abre la pestana SQL de phpMyAdmin (sin seleccionar
--      ninguna base de datos primero).
--   2. Pega el contenido completo de este archivo y ejecuta.
--   3. Esto BORRA y vuelve a crear `gasolinera_cerron_grande` con
--      datos de ejemplo limpios y consistentes.
-- ============================================================

DROP DATABASE IF EXISTS `gasolinera_cerron_grande`;
CREATE DATABASE `gasolinera_cerron_grande`
DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `gasolinera_cerron_grande`;

-- ------------------------------------------------------------
-- MODULO: USUARIOS Y SEGURIDAD
-- ------------------------------------------------------------

CREATE TABLE `roles` (
  `id_rol` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre_rol` VARCHAR(50) NOT NULL UNIQUE,
  `descripcion` TEXT DEFAULT NULL
) ENGINE=InnoDB;

CREATE TABLE `usuarios` (
  `id_usuario` INT AUTO_INCREMENT PRIMARY KEY,
  `id_rol` INT NOT NULL,
  `nombre` VARCHAR(100) NOT NULL,
  `pin_hash` VARCHAR(255) NOT NULL,
  `estado` ENUM('activo', 'inactivo') DEFAULT 'activo',
  FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- MODULO: ASISTENCIA Y TURNOS
-- ------------------------------------------------------------

CREATE TABLE `asistencia` (
  `id_asistencia` INT AUTO_INCREMENT PRIMARY KEY,
  `id_usuario` INT NOT NULL,
  `fecha_hora_entrada` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_hora_salida` DATETIME DEFAULT NULL,
  FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `turnos` (
  `id_turno` INT AUTO_INCREMENT PRIMARY KEY,
  `id_asistencia` INT NOT NULL,
  `tipo_caja` ENUM('pista', 'tienda') NOT NULL,
  `fecha_inicio` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_fin` DATETIME DEFAULT NULL,
  `monto_inicial` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `monto_declarado` DECIMAL(10,2) DEFAULT NULL,
  `estado` ENUM('abierto', 'cerrado') DEFAULT 'abierto',
  FOREIGN KEY (`id_asistencia`) REFERENCES `asistencia` (`id_asistencia`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- MODULO: TANQUES Y MONITOREO IoT (Arduino)
-- ------------------------------------------------------------

CREATE TABLE `tanques` (
  `id_tanque` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre_tanque` VARCHAR(50) NOT NULL,
  `tipo_combustible` ENUM('super', 'regular', 'diesel') NOT NULL,
  `capacidad_galones` DECIMAL(10,2) NOT NULL,
  `nivel_minimo_alerta` DECIMAL(10,2) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE `lecturas_tanque` (
  `id_lectura_tanque` INT AUTO_INCREMENT PRIMARY KEY,
  `id_tanque` INT NOT NULL,
  `nivel_cm` DECIMAL(8,2) NOT NULL,
  `galones_calculados` DECIMAL(10,2) NOT NULL,
  `fecha_hora` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_tanque`) REFERENCES `tanques` (`id_tanque`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- MODULO: PISTA Y DESPACHO
-- ------------------------------------------------------------

CREATE TABLE `bombas` (
  `id_bomba` INT AUTO_INCREMENT PRIMARY KEY,
  `numero_bomba` INT NOT NULL UNIQUE,
  `estado` ENUM('activa', 'mantenimiento', 'inactiva') DEFAULT 'activa'
) ENGINE=InnoDB;

CREATE TABLE `mangueras` (
  `id_manguera` INT AUTO_INCREMENT PRIMARY KEY,
  `id_bomba` INT NOT NULL,
  `id_tanque` INT NOT NULL,
  `color_identificador` VARCHAR(30) NOT NULL,
  `contador_acumulado` DECIMAL(12,3) NOT NULL DEFAULT 0.000,
  FOREIGN KEY (`id_bomba`) REFERENCES `bombas` (`id_bomba`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`id_tanque`) REFERENCES `tanques` (`id_tanque`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `lecturas_turno` (
  `id_lectura` INT AUTO_INCREMENT PRIMARY KEY,
  `id_turno` INT NOT NULL,
  `id_manguera` INT NOT NULL,
  `contador_inicial` DECIMAL(12,2) NOT NULL,
  `contador_final` DECIMAL(12,2) DEFAULT NULL,
  FOREIGN KEY (`id_turno`) REFERENCES `turnos` (`id_turno`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`id_manguera`) REFERENCES `mangueras` (`id_manguera`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- MODULO: PRECIOS
-- ------------------------------------------------------------

CREATE TABLE `precios_combustible` (
  `id_precio` INT AUTO_INCREMENT PRIMARY KEY,
  `tipo_combustible` ENUM('super', 'regular', 'diesel') NOT NULL,
  `precio_por_galon` DECIMAL(6,3) NOT NULL,
  `fecha_inicio_vigencia` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_fin_vigencia` DATETIME DEFAULT NULL,
  `id_usuario_registro` INT NOT NULL,
  FOREIGN KEY (`id_usuario_registro`) REFERENCES `usuarios` (`id_usuario`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- MODULO: TIENDA DE CONVENIENCIA E INVENTARIO
-- ------------------------------------------------------------

CREATE TABLE `categorias` (
  `id_categoria` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre_categoria` VARCHAR(50) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE `productos` (
  `id_producto` INT AUTO_INCREMENT PRIMARY KEY,
  `id_categoria` INT NOT NULL,
  `codigo_barras` VARCHAR(50) UNIQUE DEFAULT NULL,
  `nombre_producto` VARCHAR(100) NOT NULL,
  `precio_venta` DECIMAL(8,2) NOT NULL,
  `stock_actual` INT NOT NULL DEFAULT 0,
  `stock_minimo` INT NOT NULL DEFAULT 5,
  `fecha_vencimiento` DATE DEFAULT NULL,
  FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id_categoria`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- MODULO: VENTAS Y COMPROBANTES
-- ------------------------------------------------------------

CREATE TABLE `ventas` (
  `id_venta` INT AUTO_INCREMENT PRIMARY KEY,
  `id_turno` INT NOT NULL,
  `tipo_comprobante` ENUM('ticket', 'factura', 'ccf') NOT NULL,
  `numero_comprobante` VARCHAR(50) NOT NULL,
  `monto_total` DECIMAL(10,2) NOT NULL,
  `metodo_pago` ENUM('efectivo', 'tarjeta', 'mixto') NOT NULL,
  `fecha_hora` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_turno`) REFERENCES `turnos` (`id_turno`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `detalle_ventas_combustible` (
  `id_detalle_combustible` INT AUTO_INCREMENT PRIMARY KEY,
  `id_venta` INT NOT NULL,
  `id_manguera` INT NOT NULL,
  `galones_despachados` DECIMAL(8,3) NOT NULL,
  `precio_galon_aplicado` DECIMAL(6,3) NOT NULL,
  `subtotal` DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (`id_venta`) REFERENCES `ventas` (`id_venta`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`id_manguera`) REFERENCES `mangueras` (`id_manguera`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `detalle_ventas_tienda` (
  `id_detalle_tienda` INT AUTO_INCREMENT PRIMARY KEY,
  `id_venta` INT NOT NULL,
  `id_producto` INT NOT NULL,
  `cantidad` INT NOT NULL,
  `precio_unitario` DECIMAL(8,2) NOT NULL,
  `subtotal` DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (`id_venta`) REFERENCES `ventas` (`id_venta`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- MODULO: PROVEEDORES Y CISTERNAS
-- ------------------------------------------------------------

CREATE TABLE `proveedores` (
  `id_proveedor` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre_empresa` VARCHAR(100) NOT NULL,
  `registro_fiscal` VARCHAR(30) UNIQUE NOT NULL,
  `telefono` VARCHAR(20) DEFAULT NULL
) ENGINE=InnoDB;

CREATE TABLE `recepcion_cisternas` (
  `id_recepcion` INT AUTO_INCREMENT PRIMARY KEY,
  `id_proveedor` INT NOT NULL,
  `id_tanque` INT NOT NULL,
  `numero_factura` VARCHAR(50) NOT NULL,
  `galones_facturados` DECIMAL(10,2) NOT NULL,
  `galones_medidos_tanque` DECIMAL(10,2) NOT NULL,
  `costo_total` DECIMAL(10,2) NOT NULL,
  `fecha_hora` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_proveedor`) REFERENCES `proveedores` (`id_proveedor`) ON DELETE RESTRICT ON UPDATE CASCADE,
  FOREIGN KEY (`id_tanque`) REFERENCES `tanques` (`id_tanque`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Indices adicionales para las consultas de reportes/kardex mas frecuentes
CREATE INDEX `idx_ventas_fecha` ON `ventas` (`fecha_hora`);
CREATE INDEX `idx_lecturas_tanque_fecha` ON `lecturas_tanque` (`id_tanque`, `fecha_hora`);

-- ============================================================
-- DATOS INICIALES (SEMILLAS)
-- ============================================================

INSERT INTO `roles` (`id_rol`, `nombre_rol`, `descripcion`) VALUES
(1, 'Administrador', 'Control total del sistema y parametrizacion'),
(2, 'Despachador', 'Atencion en pista de combustible'),
(3, 'Cajero', 'Cobro y ventas en tienda de conveniencia');

-- PIN de prueba entre parentesis. Hash real generado con password_hash() (bcrypt).
INSERT INTO `usuarios` (`id_usuario`, `id_rol`, `nombre`, `pin_hash`, `estado`) VALUES
(1, 1, 'Administrador General',  '$2y$10$cjzxeyiXT9/crcn3lGsJseoZyP9WQws7GSWg8ziDRen0w3A4hxCfW', 'activo'), -- PIN 1111
(2, 3, 'Cajero de Turno',        '$2y$10$N2mgZxQ4x1O9cPgXCIZjHO7SAiVXCXg9dHqcR4bNmkWTh3IrOojnC', 'activo'), -- PIN 2222
(3, 2, 'Despachador de Pista',   '$2y$10$VTsrG7Z7RoYV1oFKolMl4ORzXekDKgFcsGMqwRznZ/9Ws9.EBEf/O', 'activo'), -- PIN 3333
(4, 2, 'Despachador Turno 2',    '$2y$10$jSk1yJUrFKIY.7EIfVo.ROE.HtjRZwsZaf3p.ZZSNRB4o7O8xVIO6', 'inactivo'); -- PIN 4444

-- 3 tanques subterraneos (segun descripcion del negocio: 10,000 / 10,000 / 12,000 galones)
INSERT INTO `tanques` (`id_tanque`, `nombre_tanque`, `tipo_combustible`, `capacidad_galones`, `nivel_minimo_alerta`) VALUES
(1, 'Tanque Subterraneo 01', 'super',   10000.00, 1500.00),
(2, 'Tanque Subterraneo 02', 'regular', 10000.00, 1500.00),
(3, 'Tanque Subterraneo 03', 'diesel',  12000.00, 1800.00);

-- Precio vigente de cada combustible (fecha_fin_vigencia NULL = activo)
INSERT INTO `precios_combustible` (`tipo_combustible`, `precio_por_galon`, `id_usuario_registro`) VALUES
('super', 4.150, 1),
('regular', 3.850, 1),
('diesel', 3.650, 1);

-- 2 islas de despacho, 4 bombas multidispensadoras
INSERT INTO `bombas` (`id_bomba`, `numero_bomba`, `estado`) VALUES
(1, 1, 'activa'),
(2, 2, 'activa'),
(3, 3, 'activa'),
(4, 4, 'activa');

-- 2 mangueras por bomba, cada una ligada a su tanque de origen
INSERT INTO `mangueras` (`id_bomba`, `id_tanque`, `color_identificador`, `contador_acumulado`) VALUES
(1, 1, 'Rojo - Super',   0.000),
(1, 2, 'Azul - Regular', 0.000),
(2, 1, 'Rojo - Super',   0.000),
(2, 3, 'Verde - Diesel', 0.000),
(3, 2, 'Azul - Regular', 0.000),
(3, 3, 'Verde - Diesel', 0.000),
(4, 1, 'Rojo - Super',   0.000),
(4, 2, 'Azul - Regular', 0.000);

-- Categorias e inventario de tienda de conveniencia
INSERT INTO `categorias` (`id_categoria`, `nombre_categoria`) VALUES
(1, 'Bebidas'),
(2, 'Snacks'),
(3, 'Lubricantes'),
(4, 'Higiene y varios');

INSERT INTO `productos` (`id_categoria`, `codigo_barras`, `nombre_producto`, `precio_venta`, `stock_actual`, `stock_minimo`, `fecha_vencimiento`) VALUES
(1, '750100001', 'Agua embotellada 600ml',       0.75, 40, 10, '2027-03-01'),
(1, '750100002', 'Gaseosa 500ml',                1.00, 30, 10, '2027-01-15'),
(1, '750100003', 'Cafe para llevar',             1.25, 25, 8,  NULL),
(2, '750100004', 'Snack salado',                 0.85, 12, 15, '2026-12-01'),
(2, '750100005', 'Chocolate barra',              0.90, 18, 10, '2026-11-20'),
(3, '750100006', 'Aceite de motor 1L',           6.50, 8,  10, NULL),
(3, '750100007', 'Aditivo limpiainyectores',     4.25, 10, 6,  NULL),
(4, '750100008', 'Toallas humedas',              1.50, 20, 8,  NULL);

-- Proveedores mayoristas de combustible
INSERT INTO `proveedores` (`nombre_empresa`, `registro_fiscal`, `telefono`) VALUES
('Distribuidora Central S.A.', '0614-020190-101-2', '2345-6789'),
('Petrolera Salvadorena',      '0614-030185-102-3', '2345-1122'),
('Combustibles del Norte',     '0614-040199-103-4', '2345-3344');
