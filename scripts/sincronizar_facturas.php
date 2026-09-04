<?php

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../models/FacturaModel.php';

$idResponsable = 0;
if (isset($argv[1])) {
    $idResponsable = (int) $argv[1];
}

if ($idResponsable <= 0) {
    $idResponsable = (int) ($_SESSION['cedula'] ?? $_SESSION['id_usuario'] ?? $_SESSION['user_id'] ?? $_SESSION['id'] ?? 0);
}

if ($idResponsable <= 0) {
    fwrite(STDERR, "ERROR: Debe indicar el id del responsable o tener la sesión activa.\n");
    exit(1);
}

$_SESSION['cedula'] = (string) $idResponsable;

$model = new FacturaModel();
$resultado = $model->guardarFacturasHoy($idResponsable);

$insertadas = $resultado['inserted'] ?? [];

echo "Sincronización finalizada. Facturas insertadas: " . count($insertadas) . PHP_EOL;

foreach ($insertadas as $factura) {
    $documento = (string) ($factura['documento'] ?? '');
    if ($documento !== '') {
        echo $documento . PHP_EOL;
    }
}
