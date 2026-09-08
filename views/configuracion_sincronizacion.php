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

require_once __DIR__ . '/../models/ConfiguracionSincronizacionModel.php';

$model = new ConfiguracionSincronizacionModel();
$mensaje = '';
$tipoMensaje = '';

if (empty($_SESSION['configuracion_sincronizacion_csrf'])) {
    $_SESSION['configuracion_sincronizacion_csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = (string) ($_POST['csrf_token'] ?? '');
    $configuracion = [
        'limite_codigos' => (int) ($_POST['limite_codigos'] ?? 0),
        'limite_stock' => (int) ($_POST['limite_stock'] ?? 0),
    ];

    if (!hash_equals($_SESSION['configuracion_sincronizacion_csrf'], $token)) {
        $mensaje = 'La solicitud no es válida. Recarga la página e inténtalo nuevamente.';
        $tipoMensaje = 'error';
    } elseif ($configuracion['limite_codigos'] < 0 || $configuracion['limite_stock'] < 0) {
        $mensaje = 'Los límites no pueden ser negativos.';
        $tipoMensaje = 'error';
    } elseif ($model->guardar($configuracion)) {
        $mensaje = 'Configuración de sincronización guardada correctamente.';
        $tipoMensaje = 'success';
    } else {
        $mensaje = 'No se pudo guardar la configuración de sincronización.';
        $tipoMensaje = 'error';
    }
}

$configuracion = $model->obtener();
$configuracionActiva = 'sincronizacion';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración de Sincronización</title>
    <link rel="icon" type="image/png" href="https://sp-ao.shortpixel.ai/client/to_webp,q_glossy,ret_img,w_32,h_32/https://darwinandwolf.com/wp-content/uploads/2025/12/Fav-50x50.png">
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
            <p class="subtitle">Define el límite de productos a procesar en cada sincronización.</p>
        </header>

        <?php require __DIR__ . '/layouts/configuracion_tabs.php'; ?>

        <?php if ($mensaje !== ''): ?>
            <div class="stock-config-message <?= htmlspecialchars($tipoMensaje) ?>" role="status"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>

        <section class="card stock-config-card">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['configuracion_sincronizacion_csrf']) ?>">
                <div class="stock-config-grid">
                    <label for="limite_codigos">Límite sincronización de códigos
                        <input type="number" id="limite_codigos" name="limite_codigos" min="0" step="1" required value="<?= htmlspecialchars((string) $configuracion['limite_codigos']) ?>">
                        <span>Cantidad máxima de códigos a procesar por ejecución. Usa 0 para no limitar.</span>
                    </label>
                    <label for="limite_stock">Límite sincronización de stock
                        <input type="number" id="limite_stock" name="limite_stock" min="0" step="1" required value="<?= htmlspecialchars((string) $configuracion['limite_stock']) ?>">
                        <span>Cantidad máxima de productos a procesar por ejecución. Usa 0 para no limitar.</span>
                    </label>
                </div>
                <div class="button-group">
                    <button type="submit"><i class="fas fa-save"></i> Guardar configuración</button>
                </div>
            </form>
        </section>
    </div>
    <script src="../public/js/menu.js"></script>
</body>

</html>
