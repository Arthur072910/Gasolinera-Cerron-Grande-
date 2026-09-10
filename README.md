-- ============================================================
-- BASE DE DATOS: Gasolinera El Cerrón Grande
-- Normalización: 3FN (Tercera Forma Normal)
-- Motor: InnoDB (Soporte para Claves Foráneas y Transacciones)
-- ============================================================

CREATE DATABASE IF NOT EXISTS `gasolinera_cerron_grande` 
DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `gasolinera_cerron_grande`;

-- ------------------------------------------------------------
-- MÓDULO 2: ASISTENCIA, GESTIÓN DE TURNOS Y CAJAS (SEGURIDAD)
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
-- MÓDULO 5: CONTROL DE INVENTARIO VOLUMÉTRICO Y MERMAS (IoT)
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
-- MÓDULO 2 (CONTINUACIÓN): INFRAESTRUCTURA DE PISTA
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
-- MÓDULO 4: PRECIOS Y MAPEO DINÁMICO DE BOMBAS
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
-- MÓDULO 1: PUNTO DE VENTA (TIENDA E INVENTARIO)
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
-- MÓDULO 1 (CONTINUACIÓN): VENTAS Y COMPROBANTES
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
-- MÓDULO 3: RECEPCIÓN DE CISTERNAS Y GESTIÓN DE PROVEEDORES
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

-- ============================================================
-- DATOS INICIALES DE PRUEBA (SEMILLAS / SEEDS)
-- ============================================================

-- Roles principales
INSERT INTO `roles` (`id_rol`, `nombre_rol`, `descripcion`) VALUES
(1, 'Administrador', 'Control total del sistema y parametrización'),
(2, 'Despachador', 'Atención en pista de combustible'),
(3, 'Cajero', 'Cobro y ventas en tienda de conveniencia');

-- Usuario Administrador por defecto (PIN: 1234)
INSERT INTO `usuarios` (`id_usuario`, `id_rol`, `nombre`, `pin_hash`, `estado`) VALUES
(1, 1, 'Administrador General', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1z5.T5OqB9j6pE2JkO2R3vU0yO4pSde', 'activo');

-- Tanque principal para la maqueta con Arduino
INSERT INTO `tanques` (`id_tanque`, `nombre_tanque`, `tipo_combustible`, `capacidad_galones`, `nivel_minimo_alerta`) VALUES
(1, 'Tanque Subterráneo 01', 'super', 5000.00, 750.00);

-- Precios de inicio
INSERT INTO `precios_combustible` (`id_precio`, `tipo_combustible`, `precio_por_galon`, `id_usuario_registro`) VALUES
(1, 'super', 4.150, 1),
(2, 'regular', 3.850, 1),
(3, 'diesel', 3.650, 1);

-- Bomba 1 de prueba
INSERT INTO `bombas` (`id_bomba`, `numero_bomba`, `estado`) VALUES
(1, 1, 'activa');

-- Manguera ligada a Bomba 1 y Tanque Subterráneo 01
INSERT INTO `mangueras` (`id_manguera`, `id_bomba`, `id_tanque`, `color_identificador`) VALUES
(1, 1, 1, 'Rojo - Súper');