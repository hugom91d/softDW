<?php
require_once __DIR__ . '/../config/session.php';

if (!isset($_SESSION['cedula'])) {
    header('Location: ../public/login.php');
    exit;
}

if (($_SESSION['rol'] ?? '') !== 'admin') {
    header('Location: reposiciones.php');
    exit;
}

require_once __DIR__ . '/../models/OpcionesNoRepuestoModel.php';

$model = new OpcionesNoRepuestoModel();
$mensaje = '';
$tipoMensaje = '';

if (empty($_SESSION['configuracion_no_repuesto_csrf'])) {
    $_SESSION['configuracion_no_repuesto_csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = (string) ($_POST['csrf_token'] ?? '');
    $opciones = is_array($_POST['opciones'] ?? null) ? $_POST['opciones'] : [];

    if (!hash_equals($_SESSION['configuracion_no_repuesto_csrf'], $token)) {
        $mensaje = 'La solicitud no es válida. Recarga la página e inténtalo nuevamente.';
        $tipoMensaje = 'error';
    } elseif ($model->guardar($opciones)) {
        $mensaje = 'Opciones de no reposición guardadas correctamente.';
        $tipoMensaje = 'success';
    } else {
        $mensaje = 'Debes conservar al menos una opción válida.';
        $tipoMensaje = 'error';
    }
}

$opciones = $model->obtenerTodas();
$configuracionActiva = 'no_repuesto';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Opciones de no reposición</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../public/css/site.css">
    <link rel="stylesheet" href="../public/css/layout.css">
    <link rel="stylesheet" href="../public/css/sidebar.css">
    <link rel="stylesheet" href="../public/css/configuracion_stock.css">
    <link rel="stylesheet" href="../public/css/configuracion.css">
</head>
<body>
    <?php require_once __DIR__ . '/layouts/menu.php'; ?>
    <div class="page">
        <header>
            <h1>Configuración</h1>
            <p class="subtitle">Administra los motivos disponibles cuando un producto no se repone.</p>
        </header>

        <?php require __DIR__ . '/layouts/configuracion_tabs.php'; ?>

        <?php if ($mensaje !== ''): ?>
            <div class="stock-config-message <?= htmlspecialchars($tipoMensaje) ?>" role="status"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>

        <section class="card stock-config-card opciones-config-card">
            <form method="POST" id="opcionesNoRepuestoForm">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['configuracion_no_repuesto_csrf']) ?>">
                <div class="opciones-config-list" id="opcionesNoRepuestoList">
                    <?php foreach ($opciones as $opcion): ?>
                        <div class="opcion-config-row">
                            <input type="text" name="opciones[]" maxlength="255" required value="<?= htmlspecialchars($opcion['texto']) ?>">
                            <button type="button" class="btn-remove-option" title="Quitar opción" aria-label="Quitar opción"><i class="fas fa-trash"></i></button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="button-group">
                    <button type="button" id="addNoRepuestoOption"><i class="fas fa-plus"></i> Agregar opción</button>
                    <button type="submit"><i class="fas fa-save"></i> Guardar opciones</button>
                </div>
            </form>
        </section>
    </div>
    <script src="../public/js/menu.js"></script>
    <script>
        const opcionesList = document.getElementById('opcionesNoRepuestoList');
        const addOptionButton = document.getElementById('addNoRepuestoOption');

        function actualizarBotonesQuitar() {
            const filas = opcionesList.querySelectorAll('.opcion-config-row');
            filas.forEach(fila => {
                fila.querySelector('.btn-remove-option').disabled = filas.length === 1;
            });
        }

        addOptionButton.addEventListener('click', function() {
            const fila = document.createElement('div');
            fila.className = 'opcion-config-row';
            fila.innerHTML = '<input type="text" name="opciones[]" maxlength="255" required><button type="button" class="btn-remove-option" title="Quitar opción" aria-label="Quitar opción"><i class="fas fa-trash"></i></button>';
            opcionesList.appendChild(fila);
            fila.querySelector('input').focus();
            actualizarBotonesQuitar();
        });

        opcionesList.addEventListener('click', function(event) {
            const button = event.target.closest('.btn-remove-option');
            if (button && opcionesList.querySelectorAll('.opcion-config-row').length > 1) {
                button.closest('.opcion-config-row').remove();
                actualizarBotonesQuitar();
            }
        });

        actualizarBotonesQuitar();
    </script>
</body>
</html>