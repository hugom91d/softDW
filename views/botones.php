<?php
require_once __DIR__ . '/../config/session.php';
if (!isset($_SESSION['cedula'])) {
    header('Location: ../public/login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Botones de Acción</title>
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
            <h1>Botones de Acción</h1>
            <p class="subtitle">Acceso rápido a funciones principales</p>
        </header>

        <section class="card">
            <div class="sync-actions">
                <div class="sync-quick-actions">
                    <button class="action-button" id="btnFacturas" title="Sincronizar Facturas">
                        <div class="action-icon"><i class="fa-solid fa-file-invoice fa-2x"></i></div>
                        <div class="action-label">Facturas</div>
                    </button>

                </div>
            </div>
        </section>
    </div>

    <script src="../public/js/menu.js"></script>
    <script src="../public/js/botones.js"></script>
</body>

</html>