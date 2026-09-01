<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['cedula'])) {
    header('Location: ../public/login.php');
    exit;
}

if (($_SESSION['rol'] ?? '') !== 'admin') {
    header('Location: reposiciones.php');
    exit;
}

require_once __DIR__ . '/../models/ConfiguracionStockModel.php';

$model = new ConfiguracionStockModel();
$mensaje = '';
$tipoMensaje = '';

if (empty($_SESSION['configuracion_stock_csrf'])) {
    $_SESSION['configuracion_stock_csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = (string) ($_POST['csrf_token'] ?? '');
    $configuracion = [
        'limite_critico' => (float) ($_POST['limite_critico'] ?? 0),
        'limite_advertencia' => (float) ($_POST['limite_advertencia'] ?? 0),
        'color_critico' => (string) ($_POST['color_critico'] ?? ''),
        'color_advertencia' => (string) ($_POST['color_advertencia'] ?? ''),
        'color_disponible' => (string) ($_POST['color_disponible'] ?? ''),
    ];

    if (!hash_equals($_SESSION['configuracion_stock_csrf'], $token)) {
        $mensaje = 'La solicitud no es válida. Recarga la página e inténtalo nuevamente.';
        $tipoMensaje = 'error';
    } elseif ($configuracion['limite_critico'] < 0 || $configuracion['limite_advertencia'] <= $configuracion['limite_critico']) {
        $mensaje = 'El límite de advertencia debe ser mayor que el límite crítico.';
        $tipoMensaje = 'error';
    } elseif (!$model->colorValido($configuracion['color_critico']) || !$model->colorValido($configuracion['color_advertencia']) || !$model->colorValido($configuracion['color_disponible'])) {
        $mensaje = 'Los colores seleccionados no son válidos.';
        $tipoMensaje = 'error';
    } elseif ($model->guardar($configuracion)) {
        $mensaje = 'Configuración de stock guardada correctamente.';
        $tipoMensaje = 'success';
    } else {
        $mensaje = 'No se pudo guardar la configuración de stock.';
        $tipoMensaje = 'error';
    }
}

$configuracion = $model->obtener();
$configuracionActiva = 'stock';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración de Stock</title>
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
            <p class="subtitle">Define los límites y colores que se aplican a todos los productos.</p>
        </header>

        <?php require __DIR__ . '/layouts/configuracion_tabs.php'; ?>

        <?php if ($mensaje !== ''): ?>
            <div class="stock-config-message <?= htmlspecialchars($tipoMensaje) ?>" role="status"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>

        <section class="card stock-config-card">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['configuracion_stock_csrf']) ?>">
                <div class="stock-config-grid">
                    <label for="limite_critico">Límite crítico
                        <input type="number" id="limite_critico" name="limite_critico" min="0" step="0.01" required value="<?= htmlspecialchars((string) $configuracion['limite_critico']) ?>">
                        <span>Valores inferiores se muestran como críticos.</span>
                    </label>
                    <label for="limite_advertencia">Límite de advertencia
                        <input type="number" id="limite_advertencia" name="limite_advertencia" min="0.01" step="0.01" required value="<?= htmlspecialchars((string) $configuracion['limite_advertencia']) ?>">
                        <span>Valores inferiores se muestran como advertencia.</span>
                    </label>
                    <label for="color_critico">Color crítico
                        <input type="color" id="color_critico" name="color_critico" value="<?= htmlspecialchars($configuracion['color_critico']) ?>">
                    </label>
                    <label for="color_advertencia">Color de advertencia
                        <input type="color" id="color_advertencia" name="color_advertencia" value="<?= htmlspecialchars($configuracion['color_advertencia']) ?>">
                    </label>
                    <label for="color_disponible">Color disponible
                        <input type="color" id="color_disponible" name="color_disponible" value="<?= htmlspecialchars($configuracion['color_disponible']) ?>">
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