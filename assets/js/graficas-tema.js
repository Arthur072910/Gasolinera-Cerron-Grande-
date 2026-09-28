/**
 * graficas-tema.js
 * -----------------------------------------------------------------------
 * Configuracion compartida de Chart.js (assets/js/vendor/chart.umd.min.js)
 * para que todas las graficas del panel se vean consistentes con la
 * identidad industrial oscuro/naranja, sin repetir esta configuracion en
 * cada vista. Se carga despues de Chart.js y antes del script propio de
 * cada vista que dibuje una grafica.
 * -----------------------------------------------------------------------
 */
window.PALETA_GRAFICA = {
    naranja: '#eb5a28',
    naranjaFuerte: '#ff6a35',
    pizarra: '#567384',
    ceniza: '#96989a',
    verde: '#3ddc84',
    texto: '#eef1f2',
    textoMuted: '#96989a',
    rejilla: 'rgba(150, 152, 154, 0.12)',
    panel: '#212f39',
};

if (typeof Chart !== 'undefined') {
    Chart.defaults.color = window.PALETA_GRAFICA.textoMuted;
    Chart.defaults.font.family = "'JetBrains Mono', ui-monospace, 'Courier New', monospace";
    Chart.defaults.font.size = 11;
    Chart.defaults.plugins.legend.labels.color = window.PALETA_GRAFICA.texto;
    Chart.defaults.plugins.tooltip.backgroundColor = window.PALETA_GRAFICA.panel;
    Chart.defaults.plugins.tooltip.titleColor = window.PALETA_GRAFICA.texto;
    Chart.defaults.plugins.tooltip.bodyColor = window.PALETA_GRAFICA.texto;
    Chart.defaults.plugins.tooltip.borderColor = 'rgba(150, 152, 154, 0.3)';
    Chart.defaults.plugins.tooltip.borderWidth = 1;
    Chart.defaults.plugins.tooltip.padding = 10;
    Chart.defaults.plugins.tooltip.cornerRadius = 5;
    Chart.defaults.animation.duration = 700;
    Chart.defaults.animation.easing = 'easeOutQuart';
}
