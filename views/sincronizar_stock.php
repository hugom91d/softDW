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
    <title>Sincronizar Stock</title>
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
            <h1>Sincronizar Stock</h1>
            <p class="subtitle">Consulta el stock por bodega en la API usando el código de stock guardado en cada producto.</p>
        </header>

        <div class="sync-toolbar">
            <div class="sync-clock" id="syncClock">00:00</div>
            <button type="button" class="action-button-alt" id="btnReiniciarSincronizacion" disabled>
                <i class="fa-solid fa-rotate-right"></i>
                <span>Volver a sincronizar</span>
            </button>
        </div>

        <section class="card">
            <div class="table-container">
                <table id="stockSyncTable">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>Stock DW</th>
                            <th>Stock Baltra</th>
                            <th>Stock Puerto Ayora</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody id="stockSyncBody"></tbody>
                </table>
            </div>
        </section>
    </div>

    <script src="../public/js/menu.js"></script>
    <script src="../public/js/sincronizar_stock.js"></script>
</body>

</html>
