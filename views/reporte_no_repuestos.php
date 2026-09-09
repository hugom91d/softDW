<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../models/FacturaModel.php';
if (!isset($_SESSION['cedula'])) {
    header('Location: ../public/login.php');
    exit;
}
if (($_SESSION['rol'] ?? '') === 'operador') {
    header('Location: reposiciones.php');
    exit;
}

$fechaInicio = (string) ($_GET['fecha_inicio'] ?? date('Y-m-d'));
$fechaFin = (string) ($_GET['fecha_fin'] ?? $fechaInicio);
$fechaInicioValida = DateTime::createFromFormat('!Y-m-d', $fechaInicio);
$fechaFinValida = DateTime::createFromFormat('!Y-m-d', $fechaFin);
if (
    $fechaInicioValida === false || $fechaInicioValida->format('Y-m-d') !== $fechaInicio
    || $fechaFinValida === false || $fechaFinValida->format('Y-m-d') !== $fechaFin
    || $fechaInicio > $fechaFin
) {
    $fechaInicio = date('Y-m-d');
    $fechaFin = $fechaInicio;
}

$model = new FacturaModel();
$detalles = $model->getDetallesNoRepuestos($fechaInicio, $fechaFin);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de no repuestos</title>
    <link rel="icon" type="image/png" href="https://sp-ao.shortpixel.ai/client/to_webp,q_glossy,ret_img,w_32,h_32/https://darwinandwolf.com/wp-content/uploads/2025/12/Fav-50x50.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../public/css/site.css">
    <link rel="stylesheet" href="../public/css/layout.css">
    <link rel="stylesheet" href="../public/css/sidebar.css">
    <link rel="stylesheet" href="../public/css/facturas.css">
</head>

<body>
    <?php require_once __DIR__ . '/layouts/menu.php'; ?>
    <div class="page">
        <header>
            <h1>Reporte de productos no repuestos</h1>
            <p class="subtitle">Consulta los productos no repuestos según la fecha de factura.</p>
        </header>

        <section class="card">
            <form method="GET" class="filters reporte-filtros">
                <label for="fecha_inicio">Fecha de factura inicial
                    <input id="fecha_inicio" name="fecha_inicio" type="date" value="<?= htmlspecialchars($fechaInicio) ?>" required>
                </label>
                <label for="fecha_fin">Fecha de factura final
                    <input id="fecha_fin" name="fecha_fin" type="date" value="<?= htmlspecialchars($fechaFin) ?>" required>
                </label>
                <button type="submit">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    Consultar
                </button>
                <a class="reporte-descarga" href="../public/index.php?controller=factura&action=descargarReporteNoRepuestos&fecha_inicio=<?= urlencode($fechaInicio) ?>&fecha_fin=<?= urlencode($fechaFin) ?>" title="Descargar reporte de no repuestos">
                    <i class="fa-solid fa-download"></i>
                    Descargar
                </a>
            </form>
        </section>

        <section class="card table-container">
            <div class="table-meta">
                <span><?= count($detalles) ?> registro<?= count($detalles) === 1 ? '' : 's' ?> no repuesto<?= count($detalles) === 1 ? '' : 's' ?></span>
            </div>
            <table aria-label="Reporte de productos no repuestos" id="reporteNoRepuestosTable" data-pagination="true">
                <thead>
                    <tr>
                        <th>Número de factura</th>
                        <th>Responsable</th>
                        <th>Código</th>
                        <th>Nombre del producto</th>
                        <th>Observación</th>
                        <th>Fecha de factura</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($detalles === []): ?>
                        <tr class="pagination-empty">
                            <td colspan="6" class="empty-state">No hay productos marcados como no repuestos en el rango seleccionado.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($detalles as $detalle): ?>
                            <tr>
                                <td><?= htmlspecialchars((string) ($detalle['numero_factura'] ?? '-')) ?></td>
                                <td><?= htmlspecialchars((string) ($detalle['responsable'] ?? 'Sin responsable')) ?></td>
                                <td><?= htmlspecialchars((string) ($detalle['codigo_producto'] ?? '-')) ?></td>
                                <td><?= htmlspecialchars((string) ($detalle['nombre_producto'] ?? '-')) ?></td>
                                <td><?= htmlspecialchars((string) ($detalle['observacion'] ?? 'Sin observación')) ?></td>
                                <td><?= htmlspecialchars((string) ($detalle['fecha_factura'] ?? '-')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <div class="table-pagination" id="reporteNoRepuestosTablePagination" aria-label="Paginación del reporte de no repuestos"></div>
        </section>
    </div>
    <script src="../public/js/menu.js"></script>
</body>

</html>
