# Sistema POS · Gasolinera El Cerron Grande

Sistema funcional (etapa 2): conectado a MySQL con los 7 modulos
normalizados en 3FN (usuarios/seguridad, asistencia/turnos, pista y
despacho, precios, tanques/IoT, tienda e inventario, ventas, y
proveedores/cisternas). Pendiente unicamente el hardware Arduino real
(por ahora las lecturas de tanque se registran a mano desde el panel
de administrador, simulando al sensor).

## Como probarlo (XAMPP / WAMP)

1. Copia la carpeta del proyecto dentro de `htdocs/` (o usala directo
   si ya esta en `www/` de WAMP).
2. Importa `database/gasolinera_cerron_grande.sql` desde phpMyAdmin
   (pestana SQL, sin seleccionar ninguna base de datos antes de pegarlo:
   el script crea la base `gasolinera_cerron_grande` desde cero, con
   `DROP DATABASE IF EXISTS` incluido).
3. Revisa `config/database.php` si tu MySQL no usa `root` sin
   contrasena en el puerto 3306.
4. Instala las dependencias de PHP con Composer (solo se usa para
   generar el PDF de Reportes, ver `composer.json`):
   ```
   composer install
   ```
5. Abre el proyecto en el navegador e ingresa con un PIN de prueba
   (usuarios ya cargados en la base de datos):
   - `1111` → Administrador
   - `2222` → Cajero
   - `3333` → Despachador

## Como opera el sistema

- **Login y asistencia**: el PIN se valida contra `usuarios.pin_hash`
  (bcrypt). Cada inicio de sesion exitoso crea una fila en `asistencia`
  (entrada); al cerrar sesion se registra la salida.
- **Turnos**: la primera vez que un Cajero o Despachador entra a su
  pantalla de POS en el dia, se abre automaticamente un turno
  (`turnos`). Se cierra explicitamente desde `cierre_caja.php` /
  `cierre_turno.php`, conciliando contra las ventas reales de ese turno.
- **Despacho en pista**: asistente de 4 pasos → Bomba → Manguera
  (filtrada por la bomba elegida) → Modalidad de venta (Monto fijo /
  Litros fijos / Tanque lleno) → Teclado numerico con ticket en vivo.
  El precio se lee siempre del servidor (`precios_combustible`
  vigente), nunca del formulario. Cada despacho inserta en `ventas` +
  `detalle_ventas_combustible` y suma el galonaje al contador
  acumulado de la manguera (`mangueras.contador_acumulado`, que simula
  el totalizador fisico). Incluye un panel que simula la pantalla LCD
  y los LEDs del Arduino durante el despacho.
- **Venta en tienda**: pestanas por categoria, buscador por
  nombre/codigo de barras, carrito con contador +/-. Al cobrar, el
  precio se relee de `productos` en el servidor, se inserta la venta
  (`ventas` + `detalle_ventas_tienda`) y se descuenta el stock real.
- **Precios**: registrar un precio nuevo cierra automaticamente
  (`fecha_fin_vigencia`) el que estaba vigente, dejando el historial
  completo para auditoria.
- **Inventario**: catalogo de productos con alerta de stock bajo, alta
  de producto/categoria, y kardex de salidas real (a partir de
  `detalle_ventas_tienda`).
- **Proveedores y cisternas**: alta de proveedor y registro de
  recepcion de cisterna, con calculo en vivo de la diferencia entre
  galones facturados y medidos.
- **Usuarios**: alta de usuario (rol + PIN, hasheado con bcrypt) y
  activar/desactivar (un usuario inactivo no puede iniciar sesion).
- **Tanques**: mientras no este conectado el Arduino, el panel de
  administracion permite registrar una lectura manual en
  `lecturas_tanque` que alimenta el mismo panel de monitoreo (nivel,
  barra y estado por forma segun el minimo de alerta).
- **Reportes**: resumen del dia, ventas por tipo de combustible y top
  productos de tienda, calculados con consultas agregadas reales.

## Estructura

```
database/  script SQL completo (esquema 3FN + datos de ejemplo)
assets/    css, imagenes, js (incluye la logica de los asistentes/carrito)
config/    config.php, session.php (clase Sesion) y database.php (conexion PDO)
controller/  un controlador por modulo, con logica real contra la BDD
model/     una clase por tabla, con metodos PDO (consultas e inserciones)
view/      vistas agrupadas por rol: auth/, admin/, cajero/, despachador/
index.php  front controller: rutas GET por vista y acciones POST por modulo
```

## Identidad visual

En blanco y negro (paleta de marca pendiente), inspirada en el propio
hardware de una gasolinera: numeros en monospace alineados como un
ticket termico, pantallas oscuras tipo visor LCD para datos criticos,
y estado (disponible/en uso/alerta) marcado por forma, no por color.
Las variables de color viven centralizadas en `:root` de `style.css`.

## Pendiente (siguientes etapas)

- [ ] Conectar el script puente en Python (Arduino → MySQL) con
      `TanqueController.php` para reemplazar la lectura manual del
      sensor por datos reales del HC-SR04.
- [ ] Implementar Offline-First (`assets/js/offline_sync.js`) con
      IndexedDB/LocalStorage para el POS de pista.
- [ ] Migrar el resto de vistas al tema industrial oscuro/naranja
      (por ahora: login, rail, Panel general, Tanques, Precios,
      Inventario/tienda **pendiente**, Proveedores **pendiente**,
      Usuarios, Asistencia y Reportes). El resto sigue con el tema
      anterior en blanco y negro de `assets/css/style.css`.
- [x] Exportar reportes a PDF (mPDF, `composer.json`) y CSV/Excel —
      ver `index.php?accion=reporte_pdf` / `reporte_csv`.
