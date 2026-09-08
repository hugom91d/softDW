document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('btnFacturas')?.addEventListener('click', function() {
        window.location.href = '../views/sincronizar.php';
    });

    document.getElementById('btnCodigoProductos')?.addEventListener('click', function() {
        window.location.href = '../views/sincronizar_codigos.php';
    });

    document.getElementById('btnSincronizarStock')?.addEventListener('click', function() {
        window.location.href = '../views/sincronizar_stock.php';
    });
});
