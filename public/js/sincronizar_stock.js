document.addEventListener('DOMContentLoaded', function() {
    const syncClock = document.getElementById('syncClock');
    const btnReiniciar = document.getElementById('btnReiniciarSincronizacion');
    const stockSyncBody = document.getElementById('stockSyncBody');

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

    function agregarFila(producto) {
        const fila = document.createElement('tr');
        fila.id = `fila-${producto.codigo}`;
        fila.innerHTML = `
            <td>${producto.codigo}</td>
            <td>${producto.descripcion || '-'}</td>
            <td class="stock-dw">-</td>
            <td class="stock-baltra">-</td>
            <td class="stock-ayora">-</td>
            <td class="stock-estado">Pendiente...</td>
        `;
        stockSyncBody.appendChild(fila);
    }

    function actualizarFila(codigo, resultado) {
        const fila = document.getElementById(`fila-${codigo}`);
        if (!fila) {
            return;
        }

        fila.querySelector('.stock-dw').textContent = resultado.stock_uio ?? 0;
        fila.querySelector('.stock-baltra').textContent = resultado.stock_baltra ?? 0;
        fila.querySelector('.stock-ayora').textContent = resultado.stock_puerto_ayora ?? 0;

        if (resultado.error) {
            fila.querySelector('.stock-estado').textContent = `Error: ${resultado.error}`;
            return;
        }

        fila.querySelector('.stock-estado').textContent = resultado.actualizado ? 'Sincronizado' : 'Sin cambios';
    }

    async function sincronizarStock() {
        btnReiniciar.disabled = true;
        stockSyncBody.innerHTML = '';
        iniciarTimer();

        let productos = [];

        try {
            const respuestaPendientes = await fetch('../public/index.php?controller=producto&action=productosPendientesStock');
            if (!respuestaPendientes.ok) {
                throw new Error(`HTTP ${respuestaPendientes.status}`);
            }

            productos = await respuestaPendientes.json();

            if (!Array.isArray(productos) || productos.length === 0) {
                stockSyncBody.innerHTML = '<tr><td colspan="6">No hay productos con código de stock por sincronizar.</td></tr>';
                return;
            }

            productos.forEach(producto => {
                agregarFila(producto);
                const fila = document.getElementById(`fila-${producto.codigo}`);
                if (fila) {
                    fila.querySelector('.stock-estado').textContent = 'Sincronizando...';
                }
            });

            // Se envían todos los productos en una sola petición: la API se consulta en
            // paralelo (curl_multi) en el servidor en lugar de una petición por producto.
            const respuesta = await fetch('../public/index.php?controller=producto&action=sincronizarStockLote', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ productos })
            });

            if (!respuesta.ok) {
                throw new Error(`HTTP ${respuesta.status}`);
            }

            const texto = await respuesta.text();
            let resultados;
            try {
                resultados = JSON.parse(texto);
            } catch (errorParseo) {
                // Respuesta no era JSON válido (p.ej. un warning de PHP mezclado): se marca
                // cada producto en 0 con error y se continúa en vez de romper la tabla completa.
                resultados = productos.map(producto => ({
                    codigo: producto.codigo,
                    actualizado: false,
                    stock_uio: 0,
                    stock_baltra: 0,
                    stock_puerto_ayora: 0,
                    error: 'Respuesta inválida del servidor'
                }));
            }

            if (!Array.isArray(resultados)) {
                resultados = [];
            }

            resultados.forEach(resultado => actualizarFila(resultado.codigo, resultado));
        } catch (error) {
            (Array.isArray(productos) ? productos : []).forEach(producto => actualizarFila(producto.codigo, {
                codigo: producto.codigo,
                actualizado: false,
                stock_uio: 0,
                stock_baltra: 0,
                stock_puerto_ayora: 0,
                error: error.message
            }));
        } finally {
            detenerTimer();
            btnReiniciar.disabled = false;
        }
    }

    btnReiniciar.addEventListener('click', sincronizarStock);

    sincronizarStock();
});
