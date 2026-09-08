<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/session.php';
if (!isset($_SESSION['cedula'])) {
    header('Location: login.php');
    exit;
}

if (!empty($_SESSION['fuerza_cambio_password'])) {
    header('Location: ../views/cambiar_password.php');
    exit;
}

$controller = $_GET['controller'] ?? 'factura';
$action = $_GET['action'] ?? 'listar';

$accionesPermitidasOperador = ['sincronizar', 'notificacionSincronizacion'];
if (($_SESSION['rol'] ?? '') === 'operador' && !in_array($action, $accionesPermitidasOperador, true)) {
    http_response_code(403);
    echo "Acceso no autorizado";
    exit;
}

if ($controller === 'producto' && ($_SESSION['rol'] ?? '') !== 'admin') {
    http_response_code(403);
    echo "Acceso no autorizado";
    exit;
}

require_once '../services/HttpClient.php';
require_once '../services/FacturaApi.php';
require_once '../services/ProductoApi.php';
require_once '../models/FacturaModel.php';
require_once '../models/ProductoModel.php';
require_once '../controllers/FacturaController.php';
require_once '../controllers/ProductoController.php';

switch ($controller) {

    case 'factura':

        $obj = new FacturaController();

        if (method_exists($obj, $action)) {
            $obj->$action();
        } else {
            http_response_code(404);
            echo "Acción no encontrada";
        }

        break;

    case 'producto':

        $obj = new ProductoController();

        if (method_exists($obj, $action)) {
            $obj->$action();
        } else {
            http_response_code(404);
            echo "Acción no encontrada";
        }

        break;

    default:
        http_response_code(404);
        echo "Controlador no encontrado";
}
