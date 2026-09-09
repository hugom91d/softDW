<?php
require_once __DIR__ . '/../config/session.php';
if (!isset($_SESSION['cedula'])) {
    header('Location: ../public/login.php');
    exit;
}
if (($_SESSION['rol'] ?? '') !== 'admin') {
    header('Location: reposiciones.php');
    exit;
}

require_once __DIR__ . '/../models/ProductoModel.php';
require_once __DIR__ . '/../models/ConfiguracionStockModel.php';

$model = new ProductoModel();
$configuracionStock = (new ConfiguracionStockModel())->obtener();
$busqueda = trim((string) ($_GET['buscar'] ?? ''));
$orden = (string) ($_GET['orden'] ?? '');
$direccion = strtolower((string) ($_GET['direccion'] ?? '')) === 'asc' ? 'asc' : 'desc';
$porPagina = in_array((int) ($_GET['por_pagina'] ?? 10), [10, 20, 50, 100], true)
    ? (int) ($_GET['por_pagina'] ?? 10)
    : 10;
$pagina = max(1, (int) ($_GET['pagina'] ?? 1));

$resultado = $model->listarStock([
    'buscar' => $busqueda,
    'pagina' => $pagina,
    'por_pagina' => $porPagina,
    'orden' => $orden,
    'direccion' => $direccion,
]);
$stock = $resultado['items'];
$totalProductos = (int) $resultado['total'];
$totalPaginas = (int) $resultado['total_paginas'];
$desde = $totalProductos > 0 ? (($pagina - 1) * $porPagina) + 1 : 0;
$hasta = min($pagina * $porPagina, $totalProductos);
$claseStock = static function ($cantidad) use ($configuracionStock): string {
    $cantidad = (float) ($cantidad ?? 0);

    if ($cantidad < $configuracionStock['limite_critico']) {
        return 'stock-critico';
    }

    return $cantidad < $configuracionStock['limite_advertencia'] ? 'stock-advertencia' : 'stock-disponible';
};
$enlaceOrden = static function (string $columna) use ($orden, $direccion, $busqueda, $porPagina): string {
    $nuevaDireccion = $orden === $columna && $direccion === 'asc' ? 'desc' : 'asc';

    return '?' . http_build_query([
        'buscar' => $busqueda,
        'por_pagina' => $porPagina,
        'pagina' => 1,
        'orden' => $columna,
        'direccion' => $nuevaDireccion,
    ]);
};
$indicadorOrden = static function (string $columna) use ($orden, $direccion): string {
    if ($orden !== $columna) {
        return '↕';
    }

    return $direccion === 'asc' ? '↑' : '↓';
};
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
                            <?php foreach ([
                                'codigo' => 'Código',
                                'descripcion' => 'Descripción',
                                'stock_uio' => 'Stock UIO',
                                'stock_baltra' => 'Stock Baltra',
                                'stock_puerto_ayora' => 'Stock Puerto Ayora',
                                'estado' => 'Estado',
                            ] as $columna => $etiqueta): ?>
                                <th aria-sort="<?= $orden === $columna ? ($direccion === 'asc' ? 'ascending' : 'descending') : 'none' ?>">
                                    <a class="stock-sort-link" href="<?= htmlspecialchars($enlaceOrden($columna)) ?>">
                                        <?= htmlspecialchars($etiqueta) ?>
                                        <span aria-hidden="true"><?= $indicadorOrden($columna) ?></span>
                                    </a>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($stock)): ?>
                            <tr class="pagination-empty">
                                <td colspan="7" class="empty-state">No se encontraron productos.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($stock as $indice => $item): ?>
                                <?php $estado = strtoupper(trim((string) ($item['estado'] ?? ''))); ?>
                                <tr>
                                    <td><?= (($pagina - 1) * $porPagina) + $indice + 1 ?></td>
                                    <td><?= htmlspecialchars($item['codigo']) ?></td>
                                    <td><?= htmlspecialchars($item['descripcion']) ?></td>
                                    <td><span class="stock-value <?= $claseStock($item['stock_uio'] ?? 0) ?>" style="--stock-color: <?= htmlspecialchars($configuracionStock['color_' . substr($claseStock($item['stock_uio'] ?? 0), 6)]) ?>"><?= number_format((float) ($item['stock_uio'] ?? 0), 0) ?></span></td>
                                    <td><span class="stock-value <?= $claseStock($item['stock_baltra'] ?? 0) ?>" style="--stock-color: <?= htmlspecialchars($configuracionStock['color_' . substr($claseStock($item['stock_baltra'] ?? 0), 6)]) ?>"><?= number_format((float) ($item['stock_baltra'] ?? 0), 0) ?></span></td>
                                    <td><span class="stock-value <?= $claseStock($item['stock_puerto_ayora'] ?? 0) ?>" style="--stock-color: <?= htmlspecialchars($configuracionStock['color_' . substr($claseStock($item['stock_puerto_ayora'] ?? 0), 6)]) ?>"><?= number_format((float) ($item['stock_puerto_ayora'] ?? 0), 0) ?></span></td>
                                    <td><span class="producto-estado <?= $estado === 'A' ? 'estado-activo' : 'estado-inactivo' ?>"><span class="estado-semaforo" aria-hidden="true"></span><?= $estado === 'A' ? 'Activo' : 'Inactivo' ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

                <div class="stock-pagination-bar">
                    <form method="GET" class="page-size-form">
                        <input type="hidden" name="buscar" value="<?= htmlspecialchars($busqueda) ?>">
                        <input type="hidden" name="orden" value="<?= htmlspecialchars($orden) ?>">
                        <input type="hidden" name="direccion" value="<?= htmlspecialchars($direccion) ?>">
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
                            <input type="hidden" name="orden" value="<?= htmlspecialchars($orden) ?>">
                            <input type="hidden" name="direccion" value="<?= htmlspecialchars($direccion) ?>">
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
