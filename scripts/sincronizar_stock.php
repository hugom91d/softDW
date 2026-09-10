<?php

require_once __DIR__ . '/../models/ProductoModel.php';

set_time_limit(0);

$tamanoLote = isset($argv[1]) ? (int) $argv[1] : 200;
if ($tamanoLote <= 0) {
    $tamanoLote = 200;
}

$inicio = microtime(true);

$model = new ProductoModel();
$resultado = $model->sincronizarTodoStockPendiente($tamanoLote, function (int $procesados, int $total) {
    echo "Procesados {$procesados}/{$total}..." . PHP_EOL;
});

$duracion = round(microtime(true) - $inicio, 2);

echo "Sincronización de stock finalizada en {$duracion}s." . PHP_EOL;
echo "Total: {$resultado['total']} | Actualizados: {$resultado['actualizados']} | Sin cambios: {$resultado['sin_cambios']} | Errores: {$resultado['errores']}" . PHP_EOL;
