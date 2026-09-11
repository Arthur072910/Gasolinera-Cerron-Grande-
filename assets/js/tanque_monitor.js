/**
 * tanque_monitor.js
 * -----------------------------------------------------------------------
 * Encargado de refrescar en vivo el panel de monitoreo de tanques que
 * alimenta el Arduino (sensor ultrasonico HC-SR04 -> script Python ->
 * base de datos MySQL -> este archivo via fetch/AJAX).
 *
 * ESTADO ACTUAL: inactivo. No hay endpoint todavia (no hay BDD conectada).
 * Cuando exista, descomentar el bloque de abajo.
 * -----------------------------------------------------------------------
 */

// const INTERVALO_MS = 3000;
//
// async function actualizarTanques() {
//     try {
//         const respuesta = await fetch('index.php?accion=api_tanques');
//         const datos = await respuesta.json();
//         datos.forEach((tanque) => {
//             const barra = document.querySelector(`[data-tanque="${tanque.id}"] .nivel-barra__relleno`);
//             if (barra) {
//                 const porcentaje = Math.round((tanque.nivel_actual / tanque.capacidad) * 100);
//                 barra.style.width = porcentaje + '%';
//             }
//         });
//     } catch (error) {
//         console.error('No se pudo actualizar el nivel de tanques:', error);
//     }
// }
//
// setInterval(actualizarTanques, INTERVALO_MS);
// document.addEventListener('DOMContentLoaded', actualizarTanques);

console.info('[tanque_monitor.js] Caparazon listo. Pendiente de conectar BDD + puente Arduino/Python.');
