<?php
require_once __DIR__ . '/../config/session.php';
if (!isset($_SESSION['cedula'])) {
    header('Location: ../public/login.php');
    exit;
}
if (($_SESSION['rol'] ?? '') === 'operador') {
    header('Location: reposiciones.php');
    exit;
}

require_once __DIR__ . '/../models/FacturaModel.php';
require_once __DIR__ . '/../models/ProductoModel.php';
require_once __DIR__ . '/../models/ConfiguracionStockModel.php';

$model = new FacturaModel();
$productoModel = new ProductoModel();
$configuracionStock = (new ConfiguracionStockModel())->obtener();
$stockBajoPorBodega = $productoModel->contarStockBajoPorBodega($configuracionStock['limite_critico']);
$facturas = $model->getFacturasAbiertasHoy();
$facturas = array_values(array_filter($facturas, 'is_array'));
usort($facturas, static function (array $facturaA, array $facturaB): int {
    $fechaA = strtotime((string)($facturaA['fecha'] ?? '')) ?: 0;
    $fechaB = strtotime((string)($facturaB['fecha'] ?? '')) ?: 0;

    if ($fechaA === $fechaB) {
        return (int)($facturaB['id_factura'] ?? 0) <=> (int)($facturaA['id_factura'] ?? 0);
    }

    return $fechaB <=> $fechaA;
});
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
    <link rel="stylesheet" href="../public/css/dashboard.css?v=<?= filemtime(__DIR__ . '/../public/css/dashboard.css') ?>">
</head>

<body>
    <?php require_once __DIR__ . '/layouts/menu.php'; ?>
    <div class="page">
        <header>
            <h1>Dashboard</h1>
            <p class="subtitle">Resumen rápido de facturas y accesos directos.</p>
        </header>

        <section class="dashboard-grid">
            <article class="dashboard-card stock-alert-card">
                <h2><?= $stockBajoPorBodega['uio'] ?></h2>
                <p>Productos con stock menor a <?= number_format($configuracionStock['limite_critico'], 0) ?> en UIO.</p>
                <a class="btn-card" href="stock.php"><i class="fas fa-warehouse"></i> Ver stock UIO</a>
            </article>
            <article class="dashboard-card stock-alert-card">
                <h2><?= $stockBajoPorBodega['baltra'] ?></h2>
                <p>Productos con stock menor a <?= number_format($configuracionStock['limite_critico'], 0) ?> en Baltra.</p>
                <a class="btn-card" href="stock.php"><i class="fas fa-warehouse"></i> Ver stock Baltra</a>
            </article>
            <article class="dashboard-card stock-alert-card">
                <h2><?= $stockBajoPorBodega['ayora'] ?></h2>
                <p>Productos con stock menor a <?= number_format($configuracionStock['limite_critico'], 0) ?> en Puerto Ayora.</p>
                <a class="btn-card" href="stock.php"><i class="fas fa-warehouse"></i> Ver stock Ayora</a>
            </article>
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

        <section class="dashboard-section dashboard-invoices">
            <h2>Últimas facturas abiertas</h2>
            <table class="dashboard-table" id="dashboardInvoicesTable" data-pagination="true">
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
                            <?php if ($count >= 10) break; ?>
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
            <div class="table-pagination" id="dashboardInvoicesTablePagination" aria-label="Paginación del dashboard"></div>
        </section>
    </div>
    <script src="../public/js/menu.js"></script>
</body>

</html>