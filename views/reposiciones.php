<?php
require_once __DIR__ . '/../config/session.php';
if (!isset($_SESSION['cedula'])) {
    header('Location: ../public/login.php');
    exit;
}

require_once __DIR__ . '/../services/HttpClient.php';
require_once __DIR__ . '/../services/FacturaApi.php';
require_once __DIR__ . '/../models/FacturaModel.php';

$model = new FacturaModel();

if (isset($_GET['action']) && $_GET['action'] === 'detalle' && isset($_GET['id_factura'])) {
    $idFactura = intval($_GET['id_factura']);
    $detalles = $model->getDetalleFactura($idFactura);
    header('Content-Type: application/json');
    echo json_encode(['detalles' => $detalles], JSON_PRETTY_PRINT);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'cerrar' && isset($_POST['id_factura'])) {
        $idFactura = intval($_POST['id_factura']);
        $idResponsable = (string) $_SESSION['cedula'];
        $success = $model->cerrarFactura($idFactura, $idResponsable);
        $message = $success ? 'Factura cerrada correctamente.' : 'No se puede cerrar la factura porque hay detalles abiertos.';
        header('Content-Type: application/json');
        echo json_encode(['success' => $success, 'message' => $message], JSON_PRETTY_PRINT);
        exit;
    }

    if ($_POST['action'] === 'repuesto' && isset($_POST['id_detalle'])) {
        $idDetalle = intval($_POST['id_detalle']);
        $success = $model->marcarDetalleComoRepuesto($idDetalle);
        header('Content-Type: application/json');
        echo json_encode(['success' => $success], JSON_PRETTY_PRINT);
        exit;
    }

    if ($_POST['action'] === 'no_repuesto' && isset($_POST['id_detalle'])) {
        $idDetalle = intval($_POST['id_detalle']);
        $observacion = trim((string)($_POST['observacion'] ?? ''));
        if ($observacion !== '') {
            $success = $model->marcarDetalleComoNoRepuestoConObservacion($idDetalle, $observacion);
        } else {
            $success = $model->marcarDetalleComoNoRepuesto($idDetalle);
        }
        header('Content-Type: application/json');
        echo json_encode(['success' => $success], JSON_PRETTY_PRINT);
        exit;
    }
}

$facturas = $model->getFacturasAbiertasHoy();
$facturasPtoAyoraActuales = [];
$facturasPtoAyoraAnteriores = [];
$facturasBaltraActuales = [];
$facturasBaltraAnteriores = [];
$hoy = (new DateTime())->format('Y-m-d');

foreach ($facturas as $factura) {
    $fechaFactura = (new DateTime($factura['fecha']))->format('Y-m-d');
    $esBaltra = ($factura['sede'] ?? '') === 'Baltra';

    if ($fechaFactura === $hoy && $esBaltra) {
        $facturasBaltraActuales[] = $factura;
    } elseif ($fechaFactura === $hoy) {
        $facturasPtoAyoraActuales[] = $factura;
    } elseif ($esBaltra) {
        $facturasBaltraAnteriores[] = $factura;
    } else {
        $facturasPtoAyoraAnteriores[] = $factura;
    }
}

$facturasActuales = array_merge($facturasPtoAyoraActuales, $facturasBaltraActuales);
$facturasAnteriores = array_merge($facturasPtoAyoraAnteriores, $facturasBaltraAnteriores);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reposiciones</title>
    <link rel="icon" type="image/png" href="https://sp-ao.shortpixel.ai/client/to_webp,q_glossy,ret_img,w_32,h_32/https://darwinandwolf.com/wp-content/uploads/2025/12/Fav-50x50.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../public/css/site.css">
    <link rel="stylesheet" href="../public/css/layout.css">
    <link rel="stylesheet" href="../public/css/sidebar.css">
    <link rel="stylesheet" href="../public/css/reposiciones.css">
</head>

<body>
    <?php require_once __DIR__ . '/layouts/menu.php'; ?>
    <div class="page">
        <header class="header-with-action">
            <div>
                <h1>Reposiciones</h1>
                <p class="subtitle">Facturas abiertas. Solo se muestran facturas con estado abierto.</p>
            </div>
            <button type="button" class="sync-new-invoices" id="syncNewInvoicesButton" title="Sincronizar nuevas facturas" aria-label="Sincronizar nuevas facturas">
                <i class="fa-solid fa-arrows-rotate"></i>
            </button>
        </header>

        <div class="sede-tabs" role="tablist" aria-label="Sede de reposiciones">
            <button type="button" class="sede-tab active" role="tab" aria-selected="true" data-sede="Pto. Ayora">Pto. Ayora</button>
            <button type="button" class="sede-tab" role="tab" aria-selected="false" data-sede="Baltra">Baltra</button>
        </div>

        <section class="card table-container">
            <h2>Actuales</h2>
            <table aria-label="Reposiciones actuales" id="reposicionesActualesTable" data-pagination="true">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Número Factura</th>
                        <th>Fecha</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="reposicionesActualesBody">
                    <?php if (!empty($facturasActuales)): ?>
                        <?php $contadoresActuales = ['Pto. Ayora' => 0, 'Baltra' => 0]; ?>
                        <?php foreach ($facturasActuales as $factura): ?>
                            <?php $sedeFactura = $factura['sede'] ?? 'Pto. Ayora'; ?>
                            <?php $facturaAnulada = ($factura['estado_factura'] ?? '') === 'A'; ?>
                            <tr data-sede="<?= htmlspecialchars($factura['sede'] ?? 'Pto. Ayora') ?>">
                                <td class="invoice-index"><?= ++$contadoresActuales[$sedeFactura] ?></td>
                                <td class="invoice-details">
                                    <span class="invoice-number"><?= htmlspecialchars($factura['numero_factura']) ?></span>
                                    <?php if ($facturaAnulada): ?><span class="invoice-cancelled">Anulada</span><?php endif; ?>
                                    <span class="invoice-date-mobile"><?= htmlspecialchars($factura['fecha']) ?></span>
                                </td>
                                <td class="invoice-date"><?= htmlspecialchars($factura['fecha']) ?></td>
                                <?php
                                $fechaFactura = new DateTime($factura['fecha']);
                                $diasAbierta = (new DateTime())->diff($fechaFactura)->days;
                                // Para facturas actuales mostramos estado en verde pastel
                                $statusClass = 'status-actual';
                                ?>
                                <td class="invoice-status">
                                    <?php if ($facturaAnulada): ?>
                                        <span class="status-cancelled"><span class="status-cancelled-full">No procesar</span><span class="status-cancelled-short">N/A</span></span>
                                    <?php else: ?>
                                        <span class="<?= $statusClass ?>"><span class="status-full"><?= (int)($factura['estado'] ?? 0) === 0 ? 'Abierta' : 'Cerrada' ?></span><span class="status-short"><?= (int)($factura['estado'] ?? 0) === 0 ? 'A' : 'C' ?></span></span>
                                    <?php endif; ?>
                                </td>
                                <td class="acciones">
                                    <button type="button" class="btn-ver<?= $facturaAnulada ? ' disabled' : '' ?>" data-id="<?= $factura['id_factura'] ?>" title="<?= $facturaAnulada ? 'Factura anulada' : 'Ver detalle' ?>" <?= $facturaAnulada ? 'disabled' : '' ?>>
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                    <button type="button" class="btn-cerrar<?= $facturaAnulada ? ' disabled' : '' ?>" data-id="<?= $factura['id_factura'] ?>" title="<?= $facturaAnulada ? 'Factura anulada' : 'Cerrar factura' ?>" <?= $facturaAnulada ? 'disabled' : '' ?>>
                                        <i class="fa-solid fa-check"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="empty-state">No hay facturas abiertas actuales.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <div class="table-pagination" id="reposicionesActualesTablePagination" aria-label="Paginación de reposiciones actuales"></div>
        </section>

        <section class="card table-container">
            <h2>Fechas anteriores</h2>
            <table aria-label="Reposiciones fechas anteriores" id="reposicionesAnterioresTable" data-pagination="true">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Número Factura</th>
                        <th>Fecha</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="reposicionesAnterioresBody">
                    <?php if (!empty($facturasAnteriores)): ?>
                        <?php $contadoresAnteriores = ['Pto. Ayora' => 0, 'Baltra' => 0]; ?>
                        <?php foreach ($facturasAnteriores as $factura): ?>
                            <?php $sedeFactura = $factura['sede'] ?? 'Pto. Ayora'; ?>
                            <?php $facturaAnulada = ($factura['estado_factura'] ?? '') === 'A'; ?>
                            <tr data-sede="<?= htmlspecialchars($factura['sede'] ?? 'Pto. Ayora') ?>">
                                <td class="invoice-index"><?= ++$contadoresAnteriores[$sedeFactura] ?></td>
                                <td class="invoice-details">
                                    <span class="invoice-number"><?= htmlspecialchars($factura['numero_factura']) ?></span>
                                    <?php if ($facturaAnulada): ?><span class="invoice-cancelled">Anulada</span><?php endif; ?>
                                    <span class="invoice-date-mobile"><?= htmlspecialchars($factura['fecha']) ?></span>
                                </td>
                                <td class="invoice-date"><?= htmlspecialchars($factura['fecha']) ?></td>
                                <?php
                                $fechaFactura = new DateTime($factura['fecha']);
                                $diasAbierta = (new DateTime())->diff($fechaFactura)->days;
                                // Para facturas en fechas anteriores mostramos estado en rojo pastel
                                $statusClass = 'status-anterior';
                                ?>
                                <td class="invoice-status">
                                    <?php if ($facturaAnulada): ?>
                                        <span class="status-cancelled"><span class="status-cancelled-full">No procesar</span><span class="status-cancelled-short">N/A</span></span>
                                    <?php else: ?>
                                        <span class="<?= $statusClass ?>"><span class="status-full"><?= (int)($factura['estado'] ?? 0) === 0 ? 'Abierta' : 'Cerrada' ?></span><span class="status-short"><?= (int)($factura['estado'] ?? 0) === 0 ? 'A' : 'C' ?></span></span>
                                    <?php endif; ?>
                                </td>
                                <td class="acciones">
                                    <button type="button" class="btn-ver<?= $facturaAnulada ? ' disabled' : '' ?>" data-id="<?= $factura['id_factura'] ?>" title="<?= $facturaAnulada ? 'Factura anulada' : 'Ver detalle' ?>" <?= $facturaAnulada ? 'disabled' : '' ?>>
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                    <button type="button" class="btn-cerrar<?= $facturaAnulada ? ' disabled' : '' ?>" data-id="<?= $factura['id_factura'] ?>" title="<?= $facturaAnulada ? 'Factura anulada' : 'Cerrar factura' ?>" <?= $facturaAnulada ? 'disabled' : '' ?>>
                                        <i class="fa-solid fa-check"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="empty-state">No hay facturas abiertas en fechas anteriores.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <div class="table-pagination" id="reposicionesAnterioresTablePagination" aria-label="Paginación de reposiciones anteriores"></div>
        </section>

        <div class="modal-overlay" id="modalOverlay">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h2 class="modal-title">Detalle de la factura</h2>
                        <p id="modalFacturaId" style="margin: 0.25rem 0 0 0; color: #666;"></p>
                    </div>
                    <button type="button" class="btn-close-modal" id="closeDetail" aria-label="Cerrar modal"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <div class="table-container">
                    <table aria-label="Detalle de factura" id="detalleTable" data-pagination="true">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Prenda</th>
                                <th>Cantidad</th>
                                <th>Nombre</th>
                                <th>Estado</th>
                                <th>Repuesto</th>
                            </tr>
                        </thead>
                        <tbody id="detalleBody"></tbody>
                    </table>
                    <div class="table-pagination" id="detalleTablePagination" aria-label="Paginación del detalle"></div>
                </div>
            </div>
        </div>

        <div class="system-notification" id="systemNotification" role="alertdialog" aria-modal="true" aria-labelledby="systemNotificationTitle">
            <div class="system-notification-content">
                <h2 class="system-notification-title" id="systemNotificationTitle">
                    <i class="fa-solid fa-circle-info"></i>
                    <span>Darwin and Wolf</span>
                </h2>
                <p class="system-notification-message" id="systemNotificationMessage"></p>
                <div class="system-notification-actions">
                    <button type="button" class="system-notification-button" id="systemNotificationAccept">Aceptar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="../public/js/reposiciones.js"></script>
    <script src="../public/js/menu.js"></script>
</body>

</html>