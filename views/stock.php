<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['cedula'])) {
    header('Location: login.php');
    exit;
}
if (($_SESSION['rol'] ?? '') !== 'admin') {
    header('Location: reposiciones.php');
    exit;
}

require_once __DIR__ . '/../models/ProductoModel.php';

$model = new ProductoModel();
$busqueda = trim((string) ($_GET['buscar'] ?? ''));
$porPagina = in_array((int) ($_GET['por_pagina'] ?? 10), [10, 20, 50, 100], true)
    ? (int) ($_GET['por_pagina'] ?? 10)
    : 10;
$pagina = max(1, (int) ($_GET['pagina'] ?? 1));

$resultado = $model->listarStock([
    'buscar' => $busqueda,
    'pagina' => $pagina,
    'por_pagina' => $porPagina,
]);
$stock = $resultado['items'];
$totalProductos = (int) $resultado['total'];
$totalPaginas = (int) $resultado['total_paginas'];
$desde = $totalProductos > 0 ? (($pagina - 1) * $porPagina) + 1 : 0;
$hasta = min($pagina * $porPagina, $totalProductos);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock de Productos</title>
    <link rel="icon" type="image/png" href="https://sp-ao.shortpixel.ai/client/to_webp,q_glossy,ret_img,w_32,h_32/https://darwinandwolf.com/wp-content/uploads/2025/12/Fav-50x50.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../public/css/site.css">
    <link rel="stylesheet" href="../public/css/layout.css">
    <link rel="stylesheet" href="../public/css/sidebar.css">
    <link rel="stylesheet" href="../public/css/stock.css">
</head>

<body>
    <?php require_once __DIR__ . '/layouts/menu.php'; ?>
    <div class="page">
        <header>
            <h1>Stock de Productos</h1>
            <p class="subtitle">Consulta el inventario almacenado en la base de datos por bodega: UIO, Baltra y Puerto Ayora.</p>
        </header>

        <section class="card">
            <form method="GET" class="filters">
                <label style="flex:1;"> Buscar por código o descripción
                    <input type="text" name="buscar" value="<?= htmlspecialchars($busqueda) ?>" placeholder="Código o descripción del producto">
                </label>
                <input type="hidden" name="por_pagina" value="<?= $porPagina ?>">
                <input type="hidden" name="pagina" value="1">
                <button type="submit">Buscar</button>
            </form>
        </section>

        <section class="card">
            <div class="table-container">
                <div class="table-meta">
                    <span><?= $totalProductos > 0 ? 'Mostrando ' . $desde . ' - ' . $hasta . ' de ' . $totalProductos . ' productos' : 'No hay productos' ?></span>
                </div>
                <table id="stockTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Código</th>
                            <th>Descripción</th>
                            <th>Stock UIO</th>
                            <th>Stock Baltra</th>
                            <th>Stock Puerto Ayora</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($stock)): ?>
                            <tr class="pagination-empty">
                                <td colspan="6" class="empty-state">No se encontraron productos.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($stock as $indice => $item): ?>
                                <tr>
                                    <td><?= (($pagina - 1) * $porPagina) + $indice + 1 ?></td>
                                    <td><?= htmlspecialchars($item['codigo']) ?></td>
                                    <td><?= htmlspecialchars($item['descripcion']) ?></td>
                                    <td class="stock-value <?= ($item['stock_uio'] ?? 0) <= 0 ? 'stock-bajo' : '' ?>"><?= number_format((float) ($item['stock_uio'] ?? 0), 0) ?></td>
                                    <td class="stock-value <?= ($item['stock_baltra'] ?? 0) <= 0 ? 'stock-bajo' : '' ?>"><?= number_format((float) ($item['stock_baltra'] ?? 0), 0) ?></td>
                                    <td class="stock-value <?= ($item['stock_puerto_ayora'] ?? 0) <= 0 ? 'stock-bajo' : '' ?>"><?= number_format((float) ($item['stock_puerto_ayora'] ?? 0), 0) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

                <div class="stock-pagination-bar">
                    <form method="GET" class="page-size-form">
                        <input type="hidden" name="buscar" value="<?= htmlspecialchars($busqueda) ?>">
                        <input type="hidden" name="pagina" value="1">
                        <label for="porPagina">Mostrar</label>
                        <select id="porPagina" name="por_pagina" onchange="this.form.requestSubmit()">
                            <?php foreach ([10, 20, 50, 100] as $cantidad): ?>
                                <option value="<?= $cantidad ?>" <?= $porPagina === $cantidad ? 'selected' : '' ?>><?= $cantidad ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span>productos</span>
                    </form>

                    <?php if ($totalPaginas > 1): ?>
                        <form method="GET" class="table-pagination stock-table-pagination" aria-label="Paginación de stock">
                            <input type="hidden" name="buscar" value="<?= htmlspecialchars($busqueda) ?>">
                            <input type="hidden" name="por_pagina" value="<?= $porPagina ?>">
                            <button type="submit" name="pagina" value="<?= $pagina - 1 ?>" class="pagination-button" <?= $pagina === 1 ? 'disabled' : '' ?>>Anterior</button>
                            <span class="pagination-status">Página <?= $pagina ?> de <?= $totalPaginas ?></span>
                            <button type="submit" name="pagina" value="<?= $pagina + 1 ?>" class="pagination-button" <?= $pagina === $totalPaginas ? 'disabled' : '' ?>>Siguiente</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </div>

    <script src="../public/js/menu.js"></script>
</body>

</html>
