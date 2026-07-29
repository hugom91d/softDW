<?php
session_start();
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'cerrar' && isset($_POST['id_factura'])) {
        $idFactura = intval($_POST['id_factura']);
        $success = $model->cerrarFactura($idFactura);
        header('Content-Type: application/json');
        echo json_encode(['success' => $success], JSON_PRETTY_PRINT);
        exit;
    }

    if ($_POST['action'] === 'repuesto' && isset($_POST['id_detalle'])) {
        $idDetalle = intval($_POST['id_detalle']);
        $success = $model->marcarDetalleComoRepuesto($idDetalle);
        header('Content-Type: application/json');
        echo json_encode(['success' => $success], JSON_PRETTY_PRINT);
        exit;
    }
}

$facturas = $model->getFacturasAbiertasHoy();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reposiciones</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../public/css/site.css">
    <link rel="stylesheet" href="../public/css/layout.css">
    <link rel="stylesheet" href="../public/css/sidebar.css">
    <style>
        .status-open {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 999px;
            background: #fff3cd;
            color: #856404;
            font-weight: 600;
        }
        .acciones button {
            margin-right: 0.5rem;
            border: none;
            background: transparent;
            cursor: pointer;
            font-size: 1rem;
            color: #333;
        }
        .acciones button:hover {
            color: #000;
        }
        .btn-ver {
            color: #0d6efd;
        }
        .btn-cerrar {
            color: #198754;
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
            overflow-y: auto;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        .modal-title {
            margin: 0;
            font-size: 1.25rem;
        }
        .btn-close-modal {
            border: none;
            background: transparent;
            font-size: 1.25rem;
            cursor: pointer;
            color: #666;
        }
        .btn-close-modal:hover {
            color: #000;
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
        .btn-mark-detail {
            width: 2.25rem;
            height: 2.25rem;
            border-radius: 50%;
            background: #198754;
            color: #fff;
            border: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        .btn-mark-detail.disabled {
            background: #6c757d;
            cursor: not-allowed;
            opacity: 0.75;
        }
        .modal-content table {
            width: 100%;
        }
    </style>
</head>

<body>
    <?php require_once __DIR__ . '/layouts/menu.php'; ?>
    <div class="page">
        <header>
            <h1>Reposiciones</h1>
            <p class="subtitle">Facturas abiertas de hoy. Solo se muestran facturas con estado abierto.</p>
        </header>

        <section class="card table-container">
            <table aria-label="Lista de reposiciones" id="reposicionesTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Número Factura</th>
                        <th>Fecha</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="reposicionesBody">
                    <?php if (!empty($facturas)): ?>
                        <?php $contador = 0; ?>
                        <?php foreach ($facturas as $factura): ?>
                            <tr>
                                <td><?= ++$contador ?></td>
                                <td><?= htmlspecialchars($factura['numero_factura']) ?></td>
                                <td><?= htmlspecialchars($factura['fecha']) ?></td>
                                <td><span class="status-open">Abierta</span></td>
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
                            <td colspan="5" class="empty-state">No hay facturas abiertas para hoy.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>

        <div class="modal-overlay" id="modalOverlay">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h2 class="modal-title">Detalle de la factura</h2>
                        <p id="modalFacturaId" style="margin: 0.25rem 0 0 0; color: #666;"></p>
                    </div>
                    <button type="button" class="btn-close-modal" id="closeDetail" aria-label="Cerrar modal">×</button>
                </div>
                <div class="table-container">
                    <table aria-label="Detalle de factura" id="detalleTable">
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
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modalOverlay = document.getElementById('modalOverlay');
            const detalleBody = document.getElementById('detalleBody');
            const closeDetail = document.getElementById('closeDetail');
            const modalFacturaId = document.getElementById('modalFacturaId');
            let currentFacturaId = null;

            function openModal(id) {
                modalOverlay.style.display = 'flex';
                currentFacturaId = id;
                modalFacturaId.textContent = `Factura ID: ${id}`;
            }

            function closeModal() {
                modalOverlay.style.display = 'none';
                detalleBody.innerHTML = '';
                currentFacturaId = null;
            }

            document.querySelectorAll('.btn-ver').forEach(button => {
                button.addEventListener('click', function () {
                    const idFactura = this.dataset.id;
                    if (!idFactura) {
                        return;
                    }

                    fetch(`reposiciones.php?action=detalle&id_factura=${encodeURIComponent(idFactura)}`)
                        .then(response => response.json())
                        .then(data => {
                            const detalles = Array.isArray(data.detalles) ? data.detalles : [];
                            detalleBody.innerHTML = detalles.length > 0
                                ? detalles.map((detalle, index) => `
                                    <tr>
                                        <td>${index + 1}</td>
                                        <td>${detalle.codigo_prenda ?? '-'}</td>
                                        <td>${detalle.cantidad ?? '-'}</td>
                                        <td>${detalle.descripcion ?? '-'}</td>
                                        <td>${detalle.estado == 0 ? 'Abierto' : 'Repuesto'}</td>
                                        <td>
                                            <button type="button" class="btn-mark-detail${detalle.estado != 0 ? ' disabled' : ''}" data-detalle-id="${detalle.id_detalle}" ${detalle.estado != 0 ? 'disabled="disabled"' : ''} title="Marcar como repuesto">
                                                <i class="fa-solid fa-check"></i>
                                            </button>
                                        </td>
                                    </tr>`).join('')
                                : '<tr><td colspan="6">No hay detalles para esta factura.</td></tr>';

                            bindMarkDetailButtons();
                            openModal(idFactura);
                        })
                        .catch(() => {
                            detalleBody.innerHTML = '<tr><td colspan="6">Error al cargar el detalle.</td></tr>';
                            openModal(idFactura);
                        });
                });
            });

            function bindMarkDetailButtons() {
                document.querySelectorAll('.btn-mark-detail').forEach(button => {
                    button.addEventListener('click', function () {
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
                                    }
                                } else {
                                    alert('No se pudo marcar el detalle como repuesto.');
                                }
                            })
                            .catch(() => {
                                alert('Error al marcar el detalle como repuesto.');
                            });
                    });
                });
            }

            closeDetail.addEventListener('click', closeModal);
            modalOverlay.addEventListener('click', function (event) {
                if (event.target === modalOverlay) {
                    closeModal();
                }
            });
        });
    </script>
</body>

</html>