        <footer class="pie-sistema">
            <div class="pie-sistema__col">
                <div class="pie-sistema__marca">
                    <span class="pie-sistema__icono">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2C12 2 5 10.5 5 15a7 7 0 0 0 14 0c0-4.5-7-13-7-13Z"/></svg>
                    </span>
                    <span class="fuente-display pie-sistema__nombre">EL CERRON GRANDE</span>
                </div>
                <p class="pie-sistema__tagline">Sistema POS para estacion de servicio &mdash; pista, tienda y turnos en un solo lugar.</p>
            </div>
            <div class="pie-sistema__col pie-sistema__col--sistema">
                <div class="pie-sistema__titulo">Sistema</div>
                <div class="pie-sistema__dato">Version <?= htmlspecialchars(VERSION_SISTEMA) ?></div>
                <div class="pie-sistema__dato">Sesion: <?= htmlspecialchars(ucfirst((string) $rol)) ?></div>
            </div>
            <div class="pie-sistema__barra">
                <span>&copy; <?= date('Y') ?> <?= htmlspecialchars(NOMBRE_SISTEMA) ?>. Todos los derechos reservados.</span>
                <span>Desarrollado por <strong>DevCore</strong></span>
            </div>
        </footer>
    </main>
</div>
<script src="assets/js/rail.js"></script>
<script src="assets/js/transiciones.js"></script>
</body>
</html>
