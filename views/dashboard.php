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
$actualesPtoAyora = count(array_filter($facturasActuales, static fn(array $factura): bool => ($factura['sede'] ?? 'Pto. Ayora') === 'Pto. Ayora'));
$actualesBaltra = count(array_filter($facturasActuales, static fn(array $factura): bool => ($factura['sede'] ?? '') === 'Baltra'));
$anterioresPtoAyora = count(array_filter($facturasAnteriores, static fn(array $factura): bool => ($factura['sede'] ?? 'Pto. Ayora') === 'Pto. Ayora'));
$anterioresBaltra = count(array_filter($facturasAnteriores, static fn(array $factura): bool => ($factura['sede'] ?? '') === 'Baltra'));
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
            <article class="dashboard-card invoice-summary-card">
                <p class="invoice-summary-title">Facturas abiertas hoy</p>
                <div class="invoice-sede-counts">
                    <div>
                        <span>Pto. Ayora</span>
                        <strong><?= $actualesPtoAyora ?></strong>
                    </div>
                    <div>
                        <span>Baltra</span>
                        <strong><?= $actualesBaltra ?></strong>
                    </div>
                </div>
                <a class="btn-card" href="reposiciones.php"><i class="fas fa-rotate"></i> Ver reposiciones</a>
            </article>
            <article class="dashboard-card invoice-summary-card">
                <p class="invoice-summary-title">Facturas abiertas anteriores</p>
                <div class="invoice-sede-counts">
                    <div>
                        <span>Pto. Ayora</span>
                        <strong><?= $anterioresPtoAyora ?></strong>
                    </div>
                    <div>
                        <span>Baltra</span>
                        <strong><?= $anterioresBaltra ?></strong>
                    </div>
                </div>
                <a class="btn-card" href="reposiciones.php"><i class="fas fa-clock"></i> Revisar facturas</a>
            </article>
            <article class="dashboard-card stock-alert-card stock-summary-card">
                <p class="invoice-summary-title">Productos con stock menor a <?= number_format($configuracionStock['limite_critico'], 0) ?></p>
                <div class="stock-bodega-counts">
                    <div>
                        <span>UIO</span>
                        <strong><?= $stockBajoPorBodega['uio'] ?></strong>
                    </div>
                    <div>
                        <span>Baltra</span>
                        <strong><?= $stockBajoPorBodega['baltra'] ?></strong>
                    </div>
                    <div>
                        <span>Pto. Ayora</span>
                        <strong><?= $stockBajoPorBodega['ayora'] ?></strong>
                    </div>
                </div>
                <a class="btn-card" href="stock.php"><i class="fas fa-warehouse"></i> Ver stock</a>
            </article>
        </section>

        <section class="dashboard-section dashboard-invoices">
            <h2>Últimas facturas</h2>
            <table class="dashboard-table" id="dashboardInvoicesTable" data-pagination="true">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Número de factura</th>
                        <th>Fecha</th>
                        <th>Sede</th>
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
                                <td><?= htmlspecialchars($factura['sede'] ?? 'Pto. Ayora') ?></td>
                                <td class="status">
                                    <?php if (($factura['estado_factura'] ?? '') === 'A'): ?>
                                        <span class="invoice-status-badge cancelled">Anulada</span>
                                    <?php elseif (($factura['estado_factura'] ?? '') === 'C'): ?>
                                        <span class="invoice-status-badge invoiced">Facturada</span>
                                    <?php else: ?>
                                        <span class="invoice-status-badge pending">Sin estado</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5">No hay facturas abiertas para mostrar.</td>
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