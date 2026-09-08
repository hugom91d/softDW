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

        if (resultado.error) {
            fila.querySelector('.stock-estado').textContent = `Error: ${resultado.error}`;
            return;
        }

        fila.querySelector('.stock-dw').textContent = resultado.stock_uio ?? 0;
        fila.querySelector('.stock-baltra').textContent = resultado.stock_baltra ?? 0;
        fila.querySelector('.stock-ayora').textContent = resultado.stock_puerto_ayora ?? 0;
        fila.querySelector('.stock-estado').textContent = resultado.actualizado ? 'Sincronizado' : 'Sin cambios';
    }

    async function sincronizarStock() {
        btnReiniciar.disabled = true;
        stockSyncBody.innerHTML = '';
        iniciarTimer();

        try {
            const respuestaPendientes = await fetch('../public/index.php?controller=producto&action=productosPendientesStock');
            if (!respuestaPendientes.ok) {
                throw new Error(`HTTP ${respuestaPendientes.status}`);
            }

            const productos = await respuestaPendientes.json();

            if (!Array.isArray(productos) || productos.length === 0) {
                stockSyncBody.innerHTML = '<tr><td colspan="6">No hay productos con código de stock por sincronizar.</td></tr>';
                return;
            }

            productos.forEach(agregarFila);

            for (const producto of productos) {
                const fila = document.getElementById(`fila-${producto.codigo}`);
                if (fila) {
                    fila.querySelector('.stock-estado').textContent = 'Sincronizando...';
                }

                try {
                    const url = `../public/index.php?controller=producto&action=sincronizarStockUno&codigo=${encodeURIComponent(producto.codigo)}&codigoStock=${encodeURIComponent(producto.codigoStock)}`;
                    const respuesta = await fetch(url);
                    const resultado = await respuesta.json();
                    actualizarFila(producto.codigo, resultado);
                } catch (errorItem) {
                    actualizarFila(producto.codigo, { error: errorItem.message });
                }
            }
        } catch (error) {
            stockSyncBody.innerHTML = `<tr><td colspan="6">Error: ${error.message}</td></tr>`;
        } finally {
            detenerTimer();
            btnReiniciar.disabled = false;
        }
    }

    btnReiniciar.addEventListener('click', sincronizarStock);

    sincronizarStock();
});
