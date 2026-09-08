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
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sincronizar Código Productos</title>
    <link rel="icon" type="image/png" href="https://sp-ao.shortpixel.ai/client/to_webp,q_glossy,ret_img,w_32,h_32/https://darwinandwolf.com/wp-content/uploads/2025/12/Fav-50x50.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../public/css/site.css">
    <link rel="stylesheet" href="../public/css/layout.css">
    <link rel="stylesheet" href="../public/css/sidebar.css">
    <link rel="stylesheet" href="../public/css/botones.css">
</head>

<body>
    <?php require_once __DIR__ . "/layouts/menu.php"; ?>

    <div class="page">
        <header>
            <h1>Sincronizar Código Productos</h1>
            <p class="subtitle">Consulta el código interno de cada producto en la API de inventario y guarda el código de stock.</p>
        </header>

        <div class="sync-toolbar">
            <div class="sync-clock" id="syncClock">00:00</div>
            <button type="button" class="action-button-alt" id="btnReiniciarSincronizacion" disabled>
                <i class="fa-solid fa-rotate-right"></i>
                <span>Volver a sincronizar</span>
            </button>
        </div>

        <section class="card terminal-card">
            <pre class="terminal-body" id="terminalBody"></pre>
        </section>
    </div>

    <script src="../public/js/menu.js"></script>
    <script src="../public/js/sincronizar_codigos.js"></script>
</body>

</html>
