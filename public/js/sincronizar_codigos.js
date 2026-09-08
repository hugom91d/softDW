document.addEventListener('DOMContentLoaded', function() {
    const terminalBody = document.getElementById('terminalBody');
    const syncClock = document.getElementById('syncClock');
    const btnReiniciar = document.getElementById('btnReiniciarSincronizacion');

    let timerInterval = null;

    function formatearTiempo(segundos) {
        const mm = String(Math.floor(segundos / 60)).padStart(2, '0');
        const ss = String(segundos % 60).padStart(2, '0');
        return `${mm}:${ss}`;
    }

    function iniciarTimer() {
        const inicio = Date.now();
        syncClock.textContent = '00:00';
        timerInterval = setInterval(() => {
            const transcurrido = Math.floor((Date.now() - inicio) / 1000);
            syncClock.textContent = formatearTiempo(transcurrido);
        }, 1000);
    }

    function detenerTimer() {
        if (timerInterval) {
            clearInterval(timerInterval);
            timerInterval = null;
        }
    }

    function escribirLinea(texto) {
        const hora = new Date().toLocaleTimeString('es-EC', { hour12: false });
        terminalBody.textContent += `[${hora}] ${texto}\n`;
        terminalBody.scrollTop = terminalBody.scrollHeight;
    }

    async function sincronizarCodigos() {
        btnReiniciar.disabled = true;
        terminalBody.textContent = '';
        iniciarTimer();
        escribirLinea('Buscando códigos pendientes por sincronizar...');

        try {
            const respuestaPendientes = await fetch('../public/index.php?controller=producto&action=codigosPendientes');
            if (!respuestaPendientes.ok) {
                throw new Error(`HTTP ${respuestaPendientes.status}`);
            }

            const codigos = await respuestaPendientes.json();

            if (!Array.isArray(codigos) || codigos.length === 0) {
                escribirLinea('No hay códigos pendientes por sincronizar.');
                return;
            }

            escribirLinea(`Códigos encontrados: ${codigos.length}`);

            let actualizados = 0;
            let noEncontrados = 0;
            let errores = 0;

            for (let i = 0; i < codigos.length; i++) {
                const item = codigos[i];
                const descripcion = item.descripcion ? ` - ${item.descripcion}` : '';
                escribirLinea(`(${i + 1}/${codigos.length}) Sincronizando ${item.codigo}${descripcion}...`);

                try {
                    const respuesta = await fetch(`../public/index.php?controller=producto&action=sincronizarUno&codigo=${encodeURIComponent(item.codigo)}`);
                    const resultado = await respuesta.json();

                    if (resultado.encontrado) {
                        actualizados++;
                        escribirLinea(`   OK -> codigoStock: ${resultado.codigoStock}`);
                    } else if (resultado.error) {
                        errores++;
                        escribirLinea(`   ERROR: ${resultado.error}`);
                    } else {
                        noEncontrados++;
                        escribirLinea('   No encontrado en la API.');
                    }
                } catch (errorItem) {
                    errores++;
                    escribirLinea(`   ERROR: ${errorItem.message}`);
                }
            }

            escribirLinea('---');
            escribirLinea(`Sincronización finalizada. Actualizados: ${actualizados} | No encontrados: ${noEncontrados} | Errores: ${errores}`);
        } catch (error) {
            escribirLinea(`ERROR: ${error.message}`);
        } finally {
            detenerTimer();
            btnReiniciar.disabled = false;
        }
    }

    btnReiniciar.addEventListener('click', sincronizarCodigos);

    sincronizarCodigos();
});
