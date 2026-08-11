<?php
session_start();
if (!isset($_SESSION['cedula'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facturas</title>
    <link rel="icon" type="image/png" href="https://sp-ao.shortpixel.ai/client/to_webp,q_glossy,ret_img,w_32,h_32/https://darwinandwolf.com/wp-content/uploads/2025/12/Fav-50x50.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../public/css/site.css">
    <link rel="stylesheet" href="../public/css/layout.css">
    <link rel="stylesheet" href="../public/css/sidebar.css">
</head>

<body>
    <?php require_once __DIR__ . "/layouts/menu.php"; ?>
    <div class="page">
        <header>
            <h1>Facturas</h1>
            <p class="subtitle">Consulta de Facturas según la fecha de emisión.</p>
        </header>

        <section class="card">
            <form method="GET" action="../public/index.php" class="filters">
                <input type="hidden" name="controller" value="factura">
                <input type="hidden" name="action" value="listar">
                <label> Fecha de emisión
                    <input id="fecha_emision" name="fecha_emision" type="date" value="<?= htmlspecialchars($filtros['fecha_emision'] ?? '') ?>">
                </label>
                <button type="submit">
                    Buscar
                </button>
            </form>

        </section>

        <section class="card table-container">
            <table aria-label="Lista de facturas" id="facturasTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Número Factura</th>
                        <th>Cliente</th>
                        <th>Total</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody id="facturasBody">
                    <?php if (!empty($facturas)): ?>
                        <?php $contador = 0; ?>
                        <?php foreach ($facturas as $factura): ?>
                            <?php if (strpos($factura['documento'], '002-002') === false) { ?>
                                <tr>
                                    <td><?= ++$contador ?></td>
                                    <td><?= $factura['documento'] ?></td>
                                    <td><?= $factura['cliente']['nombre_comercial'] ?></td>
                                    <td><?= $factura['total'] ?></td>
                                    <td class="acciones">
                                        <!-- <button type="button" class="btn-ver" data-id="<?= $factura['id'] ?>" title="Ver detalle">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>
                                        <button type="button" class="btn-finalizar" data-id="<?= $factura['id'] ?>" title="Finalizar">
                                            <i class="fa-solid fa-check"></i>
                                        </button> -->
                                        <a href="<?= $factura['url_ride'] ?>" class="btn-pdf" target="_blank" download title="Descargar PDF">
                                            <i class="fa-solid fa-file-pdf"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php } ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="empty-state">
                                No se encontraron facturas.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </div>
    <script src="../public/js/menu.js"></script>
</body>

</html>