<?php
session_start();
if (!isset($_SESSION['cedula'])) {
    header('Location: login.php');
    exit;
}

$fechaHoy = date('d/m/Y');
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sincronizar</title>
    <link rel="icon" type="image/png" href="https://sp-ao.shortpixel.ai/client/to_webp,q_glossy,ret_img,w_32,h_32/https://darwinandwolf.com/wp-content/uploads/2025/12/Fav-50x50.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../public/css/site.css">
    <link rel="stylesheet" href="../public/css/layout.css">
    <link rel="stylesheet" href="../public/css/sidebar.css">
    <meta name="sync-date" content="<?= htmlspecialchars($fechaHoy) ?>">
</head>

<body>
    <?php require_once __DIR__ . "/layouts/menu.php"; ?>

    <div class="sync-blocker" id="syncBlocker"></div>

    <div class="page">
        <header>
            <h1>Sincronizar</h1>
            <p class="subtitle">Sincroniza los datos de la aplicación.</p>
        </header>

        <section class="card sync-card" id="syncCard" style="display:none;">
            <h2>Sincronización en curso</h2>
            <div class="progress-bar">
                <div class="progress-fill" id="progressFill"></div>
            </div>
            <p class="sync-status" id="syncStatus">Consultando facturas del día...</p>
        </section>

        <section class="card" id="resultsCard" style="display:none;">
            <h2>Facturas consultadas</h2>
            <p id="resultsSummary">Facturas encontradas para la fecha de hoy (<?= htmlspecialchars($fechaHoy) ?>): 0</p>
            <div class="table-container">
                <table id="resultsTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Documento</th>
                            <th>Cliente</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody id="resultsBody"></tbody>
                </table>
            </div>
            <p id="noResultsMessage" style="display:none;">No se encontraron facturas para hoy o nuevas por sincronizar.</p>
        </section>
    </div>

    <script src="../public/js/menu.js"></script>

</body>

</html>
