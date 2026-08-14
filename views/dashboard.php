<?php
session_start();
if (!isset($_SESSION['cedula'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../models/FacturaModel.php';

$model = new FacturaModel();
$facturas = $model->getFacturasAbiertasHoy();
$facturasActuales = [];
$facturasAnteriores = [];
$hoy = (new DateTime())->format('Y-m-d');

foreach ($facturas as $factura) {
    $fechaFactura = (new DateTime($factura['fecha']))->format('Y-m-d');
    if ($fechaFactura === $hoy) {
        $facturasActuales[] = $factura;
    } else {
        $facturasAnteriores[] = $factura;
    }
}

$totalActuales = count($facturasActuales);
$totalAnteriores = count($facturasAnteriores);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="icon" type="image/png" href="https://sp-ao.shortpixel.ai/client/to_webp,q_glossy,ret_img,w_32,h_32/https://darwinandwolf.com/wp-content/uploads/2025/12/Fav-50x50.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../public/css/site.css">
    <link rel="stylesheet" href="../public/css/layout.css">
    <link rel="stylesheet" href="../public/css/sidebar.css">
    <style>
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .dashboard-card {
            background: #ffffff;
            border-radius: 1rem;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
            border: 1px solid #e5e7eb;
            min-height: 170px;
            position: relative;
        }

        .dashboard-card h2 {
            margin: 0;
            font-size: 2.5rem;
            color: #0f172a;
        }

        .dashboard-card p {
            margin: 0.85rem 0 1.3rem;
            color: #475569;
            line-height: 1.5;
        }

        .dashboard-card .btn-card {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: #0d6efd;
            color: #ffffff;
            padding: 0.75rem 1rem;
            border-radius: 999px;
            border: none;
            text-decoration: none;
            font-weight: 700;
            transition: background-color .2s ease;
        }

        .dashboard-card .btn-card:hover {
            background: #0a58ca;
        }

        .dashboard-section {
            display: grid;
            gap: 1rem;
        }

        .dashboard-section h2 {
            margin-bottom: 0.75rem;
        }

        .dashboard-table {
            width: 100%;
            border-collapse: collapse;
            background: #ffffff;
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.05);
        }

        .dashboard-table thead {
            background: #f8fafc;
        }

        .dashboard-table th,
        .dashboard-table td {
            padding: 1rem 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        .dashboard-table tbody tr:last-child td {
            border-bottom: none;
        }

        .dashboard-table td.status {
            font-weight: 700;
            color: #0f172a;
        }

        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }

            .dashboard-card {
                min-height: 0;
                padding: 1.15rem;
            }

            .dashboard-card h2 {
                font-size: 2rem;
            }

            .dashboard-card .btn-card {
                width: 100%;
                justify-content: center;
            }

            .dashboard-section {
                min-width: 0;
            }

            .dashboard-table {
                display: block;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            .dashboard-table thead,
            .dashboard-table tbody {
                display: table;
                width: 100%;
                min-width: 460px;
                table-layout: fixed;
            }

            .dashboard-table th,
            .dashboard-table td {
                padding: .75rem .6rem;
                font-size: .85rem;
            }
        }
    </style>
</head>

<body>
    <?php require_once __DIR__ . '/layouts/menu.php'; ?>
    <div class="page">
        <header>
            <h1>Dashboard</h1>
            <p class="subtitle">Resumen rápido de facturas y accesos directos.</p>
        </header>

        <section class="dashboard-grid">
            <article class="dashboard-card">
                <h2><?= $totalActuales ?></h2>
                <p>Facturas abiertas hoy.</p>
                <a class="btn-card" href="reposiciones.php"><i class="fas fa-rotate"></i> Ver reposiciones</a>
            </article>
            <article class="dashboard-card">
                <h2><?= $totalAnteriores ?></h2>
                <p>Facturas abiertas en días anteriores.</p>
                <a class="btn-card" href="reposiciones.php"><i class="fas fa-clock"></i> Revisar facturas</a>
            </article>
            <article class="dashboard-card">
                <h2>Facturas</h2>
                <p>Consulta todas las facturas disponibles y trabaja con ellas desde aquí.</p>
                <a class="btn-card" href="facturas.php"><i class="fas fa-file-invoice"></i> Ver facturas</a>
            </article>
        </section>

        <section class="dashboard-section">
            <h2>Últimas facturas abiertas</h2>
            <table class="dashboard-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Número de factura</th>
                        <th>Fecha</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($facturas)): ?>
                        <?php $count = 0; ?>
                        <?php foreach ($facturas as $factura): ?>
                            <?php if ($count >= 8) break; ?>
                            <tr>
                                <td><?= ++$count ?></td>
                                <td><?= htmlspecialchars($factura['numero_factura'] ?? $factura['documento'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($factura['fecha']) ?></td>
                                <td class="status"><?= $factura['estado'] == 0 ? 'Abierta' : ($factura['estado'] == 1 ? 'Repuesto' : 'No repuesto') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4">No hay facturas abiertas para mostrar.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </div>
    <script src="../public/js/menu.js"></script>
</body>

</html>