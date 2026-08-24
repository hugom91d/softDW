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

require_once __DIR__ . '/../services/HttpClient.php';
require_once __DIR__ . '/../services/ProductoApi.php';
require_once __DIR__ . '/../models/ProductoModel.php';

$model = new ProductoModel();
$stock = $model->listarStock();

$busqueda = trim((string) ($_GET['buscar'] ?? ''));
if ($busqueda !== '') {
    $busquedaLower = mb_strtolower($busqueda, 'UTF-8');
    $stock = array_values(array_filter($stock, static function (array $item) use ($busquedaLower): bool {
        return str_contains(mb_strtolower($item['codigo'], 'UTF-8'), $busquedaLower)
            || str_contains(mb_strtolower($item['producto'], 'UTF-8'), $busquedaLower);
    }));
}
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
            <p class="subtitle">Consulta el stock por bodega: DW, Baltra y Ayora.</p>
        </header>

        <section class="card">
            <form method="GET" class="filters">
                <label style="flex:1;"> Buscar por código o producto
                    <input type="text" name="buscar" value="<?= htmlspecialchars($busqueda) ?>" placeholder="Código o nombre del producto">
                </label>
                <button type="submit">Buscar</button>
            </form>
        </section>

        <section class="card">
            <div class="table-container">
                <table id="stockTable" data-pagination="true">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Código</th>
                            <th>Producto</th>
                            <th>Stock DW</th>
                            <th>Stock Baltra</th>
                            <th>Stock Ayora</th>
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
                                    <td><?= $indice + 1 ?></td>
                                    <td><?= htmlspecialchars($item['codigo']) ?></td>
                                    <td><?= htmlspecialchars($item['producto']) ?></td>
                                    <td class="stock-value <?= $item['stock_dw'] <= 0 ? 'stock-bajo' : '' ?>"><?= number_format($item['stock_dw'], 0) ?></td>
                                    <td class="stock-value <?= $item['stock_baltra'] === null ? 'stock-na' : ($item['stock_baltra'] <= 0 ? 'stock-bajo' : '') ?>">
                                        <?= $item['stock_baltra'] === null ? 'N/D' : number_format($item['stock_baltra'], 0) ?>
                                    </td>
                                    <td class="stock-value <?= $item['stock_ayora'] === null ? 'stock-na' : ($item['stock_ayora'] <= 0 ? 'stock-bajo' : '') ?>">
                                        <?= $item['stock_ayora'] === null ? 'N/D' : number_format($item['stock_ayora'], 0) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                <div class="table-pagination" id="stockTablePagination" aria-label="Paginación de stock"></div>
            </div>
        </section>
    </div>

    <script src="../public/js/menu.js"></script>
</body>

</html>
