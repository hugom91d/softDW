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
            <p class="subtitle">La aplicación está sincronizando datos. Por favor, espere.</p>
        </header>

        <section class="card">
            <div class="sync-actions">
                <div class="sync-quick-actions">
                    <button class="action-button" id="btnFacturas" title="Sincronizar Facturas">
                        <div class="action-icon"><i class="fa-solid fa-file-invoice fa-2x"></i></div>
                        <div class="action-label">Facturas</div>
                    </button>

                    <button class="action-button" id="btnProductos" title="Productos">
                        <div class="action-icon"><i class="fa-solid fa-box-open fa-2x"></i></div>
                        <div class="action-label">Productos</div>
                    </button>

                    <button class="action-button" id="btnStock" title="Stock Productos">
                        <div class="action-icon"><i class="fa-solid fa-layer-group fa-2x"></i></div>
                        <div class="action-label">Stock Productos</div>
                    </button>
                </div>
            </div>
        </section>

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
    <style>
        .sync-quick-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-start;
            align-items: stretch;
            padding: 1rem 0;
        }

        .action-button {
            width: 150px;
            height: 150px;
            border-radius: 12px;
            border: 1px solid #e6e6e6;
            background: #ffffff;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            cursor: pointer;
            box-shadow: 0 6px 18px rgba(0,0,0,0.06);
            transition: transform .12s ease, box-shadow .12s ease;
        }

        .action-button:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.12);
        }

        .action-icon {
            width: 64px;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: #f3f4f6;
        }

        .action-label {
            font-weight: 700;
            color: #0f172a;
            text-align: center;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const btnFacturas = document.getElementById('btnFacturas');
            const syncCard = document.getElementById('syncCard');
            const syncStatus = document.getElementById('syncStatus');
            const progressFill = document.getElementById('progressFill');

            function revealSync() {
                if (syncCard) {
                    syncCard.style.display = 'block';
                    syncCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }

            btnFacturas && btnFacturas.addEventListener('click', function() {
                // mostrar panel de sincronización y simular inicio
                revealSync();
                if (syncStatus) syncStatus.textContent = 'Iniciando sincronización de facturas...';
                if (progressFill) {
                    progressFill.style.width = '30%';
                    setTimeout(() => { progressFill.style.width = '70%'; }, 700);
                    setTimeout(() => { progressFill.style.width = '100%'; if (syncStatus) syncStatus.textContent = 'Sincronización completada (simulada).'; }, 1600);
                }
            });

            // Placeholders for other buttons
            document.getElementById('btnProductos')?.addEventListener('click', function() {
                alert('Función Productos: pendiente de desarrollo.');
            });
            document.getElementById('btnStock')?.addEventListener('click', function() {
                alert('Función Stock Productos: pendiente de desarrollo.');
            });
        });
    </script>
</body>

</html>
