<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['cedula'])) {
    header('Location: login.php');
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
        $success = $model->cerrarFactura($idFactura);
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
    <style>
        body {
            color: #000;
        }

        .btn-ver {
            color: #0d6efd;
        }

        .btn-cerrar {
            color: #198754;
        }

        table thead th,
        table tbody td {
            text-align: center;
        }

        table tbody td.acciones {
            text-align: left;
        }

        .acciones {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            white-space: nowrap;
        }

        .btn-ver,
        .btn-cerrar {
            width: 44px;
            min-width: 44px;
            height: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            border-radius: 10px;
            color: #ffffff;
            cursor: pointer;
            transition: background-color .2s ease, transform .2s ease;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.12);
        }

        .btn-ver {
            background-color: #0d6efd !important;
            /* azul primary más oscuro */
            color: #ffffff;
        }

        .btn-ver:not(.disabled):hover {
            background-color: #000000 !important;
            transform: translateY(-1px);
        }

        .btn-ver:not(.disabled):hover i {
            color: #ff3333 !important;
        }

        .btn-ver.disabled {
            background-color: #e5e7eb !important;
            color: #6c757d !important;
            cursor: not-allowed;
            opacity: 0.75;
            pointer-events: none;
        }

        .btn-cerrar {
            background-color: #198754 !important;
            /* verde más visible */
            color: #ffffff;
        }

        .btn-cerrar i {
            color: #ffffff !important;
        }

        .btn-cerrar:not(.disabled):hover {
            background-color: #000000 !important;
            transform: translateY(-1px);
        }

        .btn-cerrar:not(.disabled):hover i {
            color: #ff3333 !important;
        }

        .btn-cerrar.disabled {
            background: #6c757d !important;
            cursor: not-allowed;
            opacity: 0.75;
        }

        .status-closed {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 999px;
            background: #d1e7dd;
            color: #0f5132;
            font-weight: 600;
        }

        .status-open-delayed {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 999px;
            background: #f8d7da;
            color: #842029;
            font-weight: 600;
            border: 1px solid #f5c2c7;
        }

        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.4);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            padding: 1rem;
        }

        .modal-content {
            width: 100%;
            max-width: 900px;
            background: #fff;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            position: relative;
            max-height: 90vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            flex: 0 0 auto;
            background: #fff;
            position: relative;
            z-index: 1;
        }

        .modal-title {
            margin: 0;
            font-size: 1.25rem;
        }

        .btn-close-modal {
            width: 40px;
            height: 40px;
            border: none;
            background: #dc2626;
            font-size: 1.25rem;
            cursor: pointer;
            color: #ffffff;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-close-modal i {
            color: inherit;
            transition: color .12s ease, transform .08s ease;
        }

        .btn-close-modal:hover {
            background: #000000 !important;
            color: #ff3333 !important;
            transform: translateY(-1px);
        }

        .modal-actions {
            margin-top: 1rem;
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .modal-actions button {
            border: none;
            cursor: pointer;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            font-weight: 600;
            color: #fff;
        }

        .btn-close-factura {
            background: #198754;
        }

        .btn-close-factura.disabled {
            background: #6c757d;
            cursor: not-allowed;
            opacity: 0.75;
        }

        .btn-ok {
            background: #0d6efd !important;
            /* primary blue */
            color: #ffffff !important;
            /* texto blanco */
            opacity: 1 !important;
            font-weight: 800;
            /* más gruesa */
            border: none;
            cursor: pointer;
            transition: background-color .12s ease, transform .08s ease, color .08s ease;
            width: 44px;
            height: 36px;
            padding: 0;
            /* tamaño fijo */
            font-size: 0.95rem;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 10px rgba(13, 110, 253, 0.18);
        }

        .btn-ok:not(.disabled):hover {
            background: #000000 !important;
            /* negro al pasar */
            color: #ff3333 !important;
            /* icono/texto rojo */
            transform: translateY(-1px);
        }

        .btn-mark-detail,
        .btn-remove-detail {
            min-width: 44px;
            height: 44px;
            border-radius: 50%;
            color: #ffffff;
            border: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
            transition: background-color .2s ease, transform .2s ease;
            width: auto !important;
        }

        .btn-mark-detail {
            background: #15803d;
            /* verde más oscuro para que se vea activo */
        }

        .btn-mark-detail:not(.disabled):hover {
            background: #000000 !important;
            transform: translateY(-1px);
        }

        .btn-mark-detail:not(.disabled):hover i {
            color: #ff3333 !important;
        }

        .btn-mark-detail.disabled {
            background: #e5e7eb !important;
            cursor: not-allowed;
            opacity: 0.75;
            pointer-events: none;
        }

        .btn-remove-detail {
            background: #ef4444;
        }

        .btn-remove-detail:not(.disabled):hover {
            background: #000000 !important;
            transform: translateY(-1px);
        }

        .btn-remove-detail:not(.disabled):hover i {
            color: #ff3333 !important;
        }

        .btn-mark-detail.disabled+.btn-remove-detail,
        .btn-remove-detail.disabled {
            background: #e5e7eb;
            cursor: not-allowed;
            opacity: 0.75;
        }

        .modal-content table {
            width: 100%;
        }

        .modal-content > .table-container {
            min-height: 0;
            overflow-x: hidden;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }

        .system-notification {
            position: fixed;
            inset: 0;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            background: rgba(15, 23, 42, 0.45);
            z-index: 11000;
        }

        .system-notification.show {
            display: flex;
        }

        .system-notification-content {
            width: min(440px, 100%);
            padding: 1.25rem;
            border-radius: 0.85rem;
            background: #fff;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);
        }

        .system-notification-title {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 0 0 0.9rem;
            color: #111827;
            font-size: 1rem;
        }

        .system-notification-title i {
            color: #0d6efd;
        }

        .system-notification-message {
            margin: 0 0 1.15rem;
            color: #374151;
            line-height: 1.5;
        }

        .system-notification-actions {
            display: flex;
            justify-content: flex-end;
        }

        .system-notification-button {
            width: auto;
            min-width: 88px;
            margin: 0;
            padding: 0.65rem 1rem;
            background: #0d6efd;
        }

        .system-notification-button:hover {
            background: #0a58ca;
        }
        /* Estados: actuales (verde pastel) y anteriores (rojo pastel) */
        .status-actual,
        .status-anterior {
            display: inline-block;
            padding: 0.25rem 0.6rem;
            border-radius: 0.5rem;
            font-weight: 700;
            font-size: 0.9rem;
            line-height: 1;
        }

        .status-actual {
            background: #e6ffea; /* verde pastel */
            color: #000000; /* texto negro */
            border: 1px solid #c9f3d0;
        }

        .status-anterior {
            background: #ffecec; /* rojo pastel */
            color: #000000; /* texto negro */
            border: 1px solid #ffd6d6;
        }

        .status-short {
            display: none;
        }

        @media (max-width: 768px) {
            #reposicionesActualesTable,
            #reposicionesAnterioresTable {
                min-width: 0;
                table-layout: fixed;
            }

            .card.table-container:has(#reposicionesActualesTable),
            .card.table-container:has(#reposicionesAnterioresTable) {
                overflow-x: hidden;
            }

            #reposicionesActualesTable th:nth-child(1),
            #reposicionesActualesTable td:nth-child(1),
            #reposicionesActualesTable th:nth-child(3),
            #reposicionesActualesTable td:nth-child(3),
            #reposicionesAnterioresTable th:nth-child(1),
            #reposicionesAnterioresTable td:nth-child(1),
            #reposicionesAnterioresTable th:nth-child(3),
            #reposicionesAnterioresTable td:nth-child(3) {
                display: none;
            }

            #reposicionesActualesTable th:nth-child(2),
            #reposicionesActualesTable td:nth-child(2),
            #reposicionesAnterioresTable th:nth-child(2),
            #reposicionesAnterioresTable td:nth-child(2) {
                width: 54%;
                max-width: 0;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            #reposicionesActualesTable th:nth-child(4),
            #reposicionesActualesTable td:nth-child(4),
            #reposicionesAnterioresTable th:nth-child(4),
            #reposicionesAnterioresTable td:nth-child(4) {
                display: none;
            }

            #reposicionesActualesTable th:nth-child(5),
            #reposicionesActualesTable td:nth-child(5),
            #reposicionesAnterioresTable th:nth-child(5),
            #reposicionesAnterioresTable td:nth-child(5) {
                white-space: nowrap;
            }

            #reposicionesActualesTable .acciones,
            #reposicionesAnterioresTable .acciones {
                width: 100%;
                gap: 0.25rem;
                justify-content: flex-end;
            }

            #reposicionesActualesTable .status-full,
            #reposicionesAnterioresTable .status-full {
                display: none;
            }

            #reposicionesActualesTable .status-short,
            #reposicionesAnterioresTable .status-short {
                display: inline;
            }

            #reposicionesActualesTable .btn-ver,
            #reposicionesActualesTable .btn-cerrar,
            #reposicionesAnterioresTable .btn-ver,
            #reposicionesAnterioresTable .btn-cerrar {
                width: 32px;
                min-width: 32px;
                height: 32px;
                border-radius: 7px;
                font-size: 0.8rem;
            }

            #reposicionesActualesTable .status-actual,
            #reposicionesAnterioresTable .status-anterior {
                min-width: 24px;
                padding: 0.25rem;
                font-size: 0.8rem;
            }

            .modal-overlay {
                padding: 0.5rem;
                align-items: flex-end;
            }

            .modal-content {
                max-height: 92vh;
                padding: 1rem;
                border-radius: 1rem 1rem 0 0;
            }

            .modal-header {
                align-items: flex-start;
            }

            .modal-title {
                font-size: 1.05rem;
            }

            .modal-actions {
                flex-direction: column;
            }

            .modal-actions button {
                width: 100%;
                margin-top: 0;
            }

            .detalle-actions {
                gap: 0.35rem !important;
            }

            #detalleTable,
            #detalleTable tbody,
            #detalleTable tr,
            #detalleTable td {
                display: block;
                width: 100%;
                min-width: 0;
            }

            #detalleTable {
                min-width: 0;
            }

            #detalleTable thead {
                display: none;
            }

            #detalleTable tbody tr {
                margin-bottom: 0.75rem;
                padding: 0.8rem;
                border: 1px solid #e5e7eb;
                border-radius: 0.75rem;
                background: #fff;
            }

            #detalleTable tbody td {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: 0.75rem;
                padding: 0.55rem 0;
                text-align: right;
                white-space: normal;
                overflow-wrap: anywhere;
            }

            #detalleTable tbody td::before {
                flex: 0 0 auto;
                color: #475569;
                font-size: 0.76rem;
                font-weight: 700;
                text-align: left;
                text-transform: uppercase;
            }

            #detalleTable tbody td:nth-child(1) {
                display: none;
            }

            #detalleTable tbody td:nth-child(2)::before {
                content: 'Prenda';
            }

            #detalleTable tbody td:nth-child(3)::before {
                content: 'Cantidad';
            }

            #detalleTable tbody td:nth-child(4)::before {
                content: 'Descripción';
            }

            #detalleTable tbody td:nth-child(5)::before {
                content: 'Estado';
            }

            #detalleTable tbody td:nth-child(6) {
                align-items: center;
                padding-top: 0.75rem;
                border-top: 1px solid #e5e7eb;
            }

            #detalleTable tbody td:nth-child(6)::before {
                content: 'Acciones';
            }

            #detalleTable .detalle-actions {
                display: inline-flex !important;
                justify-content: flex-end;
                gap: 0.35rem !important;
                flex: 1;
            }

            #detalleTable .btn-mark-detail,
            #detalleTable .btn-remove-detail {
                width: 34px;
                min-width: 34px;
                height: 34px;
                margin: 0;
            }

            #detalleTable .no-repuesto-row,
            #detalleTable .no-repuesto-row td {
                display: block !important;
            }

            #detalleTable .no-repuesto-form {
                display: flex !important;
                flex-direction: column;
                align-items: stretch !important;
                gap: 0.5rem !important;
            }

            #detalleTable .no-repuesto-form input,
            #detalleTable .no-repuesto-form .btn-ok {
                width: 100%;
                margin-top: 0;
            }
        }
    </style>
</head>

<body>
    <?php require_once __DIR__ . '/layouts/menu.php'; ?>
    <div class="page">
        <header>
            <h1>Reposiciones</h1>
            <p class="subtitle">Facturas abiertas. Solo se muestran facturas con estado abierto.</p>
        </header>

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
                        <?php $contadorActuales = 0; ?>
                        <?php foreach ($facturasActuales as $factura): ?>
                            <tr>
                                <td><?= ++$contadorActuales ?></td>
                                <td><?= htmlspecialchars($factura['numero_factura']) ?></td>
                                <td><?= htmlspecialchars($factura['fecha']) ?></td>
                                <?php
                                $fechaFactura = new DateTime($factura['fecha']);
                                $diasAbierta = (new DateTime())->diff($fechaFactura)->days;
                                // Para facturas actuales mostramos estado en verde pastel
                                $statusClass = 'status-actual';
                                ?>
                                <td><span class="<?= $statusClass ?>"><span class="status-full"><?= (int)($factura['estado'] ?? 0) === 0 ? 'Abierta' : 'Cerrada' ?></span><span class="status-short"><?= (int)($factura['estado'] ?? 0) === 0 ? 'A' : 'C' ?></span></span></td>
                                <td class="acciones">
                                    <button type="button" class="btn-ver" data-id="<?= $factura['id_factura'] ?>" title="Ver detalle">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                    <button type="button" class="btn-cerrar" data-id="<?= $factura['id_factura'] ?>" title="Cerrar factura">
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
                        <?php $contadorAnteriores = 0; ?>
                        <?php foreach ($facturasAnteriores as $factura): ?>
                            <tr>
                                <td><?= ++$contadorAnteriores ?></td>
                                <td><?= htmlspecialchars($factura['numero_factura']) ?></td>
                                <td><?= htmlspecialchars($factura['fecha']) ?></td>
                                <?php
                                $fechaFactura = new DateTime($factura['fecha']);
                                $diasAbierta = (new DateTime())->diff($fechaFactura)->days;
                                // Para facturas en fechas anteriores mostramos estado en rojo pastel
                                $statusClass = 'status-anterior';
                                ?>
                                <td><span class="<?= $statusClass ?>"><span class="status-full"><?= (int)($factura['estado'] ?? 0) === 0 ? 'Abierta' : 'Cerrada' ?></span><span class="status-short"><?= (int)($factura['estado'] ?? 0) === 0 ? 'A' : 'C' ?></span></span></td>
                                <td class="acciones">
                                    <button type="button" class="btn-ver" data-id="<?= $factura['id_factura'] ?>" title="Ver detalle">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                    <button type="button" class="btn-cerrar" data-id="<?= $factura['id_factura'] ?>" title="Cerrar factura">
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
                                <th>Descripción</th>
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
                    <span>localhost</span>
                </h2>
                <p class="system-notification-message" id="systemNotificationMessage"></p>
                <div class="system-notification-actions">
                    <button type="button" class="system-notification-button" id="systemNotificationAccept">Aceptar</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modalOverlay = document.getElementById('modalOverlay');
            const detalleBody = document.getElementById('detalleBody');
            const closeDetail = document.getElementById('closeDetail');
            const modalFacturaId = document.getElementById('modalFacturaId');
            const systemNotification = document.getElementById('systemNotification');
            const systemNotificationMessage = document.getElementById('systemNotificationMessage');
            const systemNotificationAccept = document.getElementById('systemNotificationAccept');
            let currentFacturaId = null;
            let currentNoRepuestoId = null;
            let currentNoRepuestoButton = null;

            function showSystemNotification(message) {
                systemNotificationMessage.textContent = message;
                systemNotification.classList.add('show');
                systemNotificationAccept.focus();
            }

            function closeSystemNotification() {
                systemNotification.classList.remove('show');
            }

            function removeExistingNoRepuestoForm() {
                const existing = detalleBody.querySelector('.no-repuesto-row');
                if (existing) existing.remove();
                currentNoRepuestoId = null;
                currentNoRepuestoButton = null;
            }

            function openModal(id) {
                removeExistingNoRepuestoForm();
                modalOverlay.style.display = 'flex';
                currentFacturaId = id;
                modalFacturaId.textContent = `Factura ID: ${id}`;
            }

            function openNoRepuestoForm(idDetalle, buttonElement) {
                removeExistingNoRepuestoForm();
                currentNoRepuestoId = idDetalle;
                currentNoRepuestoButton = buttonElement;

                const row = buttonElement.closest('tr');
                if (!row) return;

                const formRow = document.createElement('tr');
                formRow.className = 'no-repuesto-row';
                const td = document.createElement('td');
                td.colSpan = 6;
                td.innerHTML = `
                    <div class="no-repuesto-form" style="margin-top:0.75rem; border:1px solid #f5c2c7; background:#fff1f2; border-radius:0.75rem; padding:0.75rem; display:flex; align-items:center; gap:0.75rem;">
                        <label style="font-weight:700; color:#842029; margin:0; white-space:nowrap;">Observación</label>
                        <input type="text" id="noRepuestoInput" placeholder="Escribe la razón aquí..." style="flex:1; padding:0.6rem 0.85rem; border-radius:0.5rem; border:1px solid #ced4da; font-family:inherit;" />
                        <button type="button" class="btn-ok" id="saveNoRepuestoButton">OK</button>
                    </div>
                `;

                formRow.appendChild(td);
                row.parentNode.insertBefore(formRow, row.nextSibling);

                const input = document.getElementById('noRepuestoInput');
                input.focus();

                document.getElementById('saveNoRepuestoButton').addEventListener('click', function() {
                    const observacion = input.value.trim();
                    if (observacion.length === 0) {
                        showSystemNotification('Escribe una observación antes de continuar.');
                        input.focus();
                        return;
                    }

                    const idDetalle = currentNoRepuestoId;
                    const buttonEl = currentNoRepuestoButton;
                    if (!idDetalle || !buttonEl) {
                        removeExistingNoRepuestoForm();
                        return;
                    }

                    fetch('reposiciones.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: `action=no_repuesto&id_detalle=${encodeURIComponent(idDetalle)}&observacion=${encodeURIComponent(observacion)}`
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                const rowEl = buttonEl.closest('tr');
                                if (rowEl) {
                                    rowEl.querySelector('td:nth-child(5)').textContent = 'No repuesto';
                                    const markButton = rowEl.querySelector('.btn-mark-detail');
                                    if (markButton) {
                                        markButton.disabled = true;
                                        markButton.classList.add('disabled');
                                    }
                                    buttonEl.disabled = true;
                                    buttonEl.classList.add('disabled');
                                }
                                removeExistingNoRepuestoForm();
                            } else {
                                showSystemNotification('No se pudo guardar la observación.');
                            }
                        })
                        .catch(() => {
                            showSystemNotification('Error al guardar la observación.');
                        });
                });
            }

            function closeModal() {
                removeExistingNoRepuestoForm();
                modalOverlay.style.display = 'none';
                detalleBody.innerHTML = '';
                currentFacturaId = null;
            }

            document.querySelectorAll('.btn-ver').forEach(button => {
                button.addEventListener('click', function() {
                    const idFactura = this.dataset.id;
                    if (!idFactura) {
                        return;
                    }

                    fetch(`reposiciones.php?action=detalle&id_factura=${encodeURIComponent(idFactura)}`)
                        .then(response => response.json())
                        .then(data => {
                            const detalles = Array.isArray(data.detalles) ? data.detalles : [];
                            detalleBody.innerHTML = detalles.length > 0 ?
                                detalles.map((detalle, index) => `
                                    <tr>
                                        <td>${index + 1}</td>
                                        <td data-label="Prenda">${detalle.codigo_interno ?? '-'}</td>
                                        <td data-label="Cantidad">${detalle.cantidad ?? '-'}</td>
                                        <td data-label="Descripción">${detalle.descripcion ?? '-'}</td>
                                        <td>${detalle.estado == 0 ? 'Abierto' : (detalle.estado == 1 ? 'Repuesto' : 'No repuesto')}</td>
                                        <td data-label="Acciones">
                                            <div class="detalle-actions" style="display:inline-flex; gap:0.5rem; align-items:center;">
                                                <button type="button" class="btn-mark-detail${detalle.estado != 0 ? ' disabled' : ''}" data-detalle-id="${detalle.id_detalle}" ${detalle.estado != 0 ? 'disabled="disabled"' : ''} title="Marcar como repuesto">
                                                    <i class="fa-solid fa-check"></i>
                                                </button>
                                                <button type="button" class="btn-remove-detail${detalle.estado != 0 ? ' disabled' : ''}" data-detalle-id="${detalle.id_detalle}" ${detalle.estado != 0 ? 'disabled="disabled"' : ''} title="Marcar como no repuesto">
                                                    <i class="fa-solid fa-xmark"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>`).join('') :
                                '<tr><td colspan="6">No hay detalles para esta factura.</td></tr>';

                            bindMarkDetailButtons();
                            refreshTablePagination('detalleTable');
                            openModal(idFactura);
                        })
                        .catch(() => {
                            detalleBody.innerHTML = '<tr><td colspan="6">Error al cargar el detalle.</td></tr>';
                            openModal(idFactura);
                        });
                });
            });

            bindCloseFacturaButtons();

            function bindCloseFacturaButtons() {
                document.querySelectorAll('.btn-cerrar').forEach(button => {
                    button.addEventListener('click', function() {
                        const idFactura = this.dataset.id;
                        if (!idFactura || this.disabled) {
                            return;
                        }

                        const buttonElement = this;
                        fetch('reposiciones.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded'
                                },
                                body: `action=cerrar&id_factura=${encodeURIComponent(idFactura)}`
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    const row = buttonElement.closest('tr');
                                    if (row) {
                                        const estadoCell = row.querySelector('td:nth-child(4)');
                                        if (estadoCell) {
                                            estadoCell.innerHTML = '<span class="status-closed">Cerrada</span>';
                                        }
                                        const viewButton = row.querySelector('.btn-ver');
                                        [buttonElement, viewButton].forEach(el => {
                                            if (el) {
                                                el.disabled = true;
                                                el.classList.add('disabled');
                                            }
                                        });
                                    }
                                } else {
                                    showSystemNotification(data.message || 'No se puede cerrar la factura porque hay detalles abiertos.');
                                }
                            })
                            .catch(() => {
                                showSystemNotification('Error al cerrar la factura.');
                            });
                    });
                });
            }

            function bindMarkDetailButtons() {
                document.querySelectorAll('.btn-mark-detail').forEach(button => {
                    button.addEventListener('click', function() {
                        const idDetalle = this.dataset.detalleId;
                        if (!idDetalle || this.disabled) {
                            return;
                        }
                        const buttonElement = this;

                        fetch('reposiciones.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded'
                                },
                                body: `action=repuesto&id_detalle=${encodeURIComponent(idDetalle)}`
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    buttonElement.disabled = true;
                                    buttonElement.classList.add('disabled');
                                    const row = buttonElement.closest('tr');
                                    if (row) {
                                        row.querySelector('td:nth-child(5)').textContent = 'Repuesto';
                                        const removeButton = row.querySelector('.btn-remove-detail');
                                        if (removeButton) {
                                            removeButton.disabled = true;
                                            removeButton.classList.add('disabled');
                                        }
                                    }
                                } else {
                                    showSystemNotification('No se pudo marcar el detalle como repuesto.');
                                }
                            })
                            .catch(() => {
                                showSystemNotification('Error al marcar el detalle como repuesto.');
                            });
                    });
                });

                document.querySelectorAll('.btn-remove-detail').forEach(button => {
                    button.addEventListener('click', function() {
                        const idDetalle = this.dataset.detalleId;
                        if (!idDetalle || this.disabled) {
                            return;
                        }
                        openNoRepuestoForm(idDetalle, this);
                    });
                });
            }

            closeDetail.addEventListener('click', closeModal);
            systemNotificationAccept.addEventListener('click', closeSystemNotification);
            systemNotification.addEventListener('click', function(event) {
                if (event.target === systemNotification) {
                    closeSystemNotification();
                }
            });
            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    closeSystemNotification();
                }
            });
            modalOverlay.addEventListener('click', function(event) {
                if (event.target === modalOverlay) {
                    closeModal();
                }
            });
        });
    </script>
    <script src="../public/js/menu.js"></script>
</body>

</html>