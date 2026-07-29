<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../services/HttpClient.php';
require_once '../services/FacturaApi.php';
require_once '../models/FacturaModel.php';
require_once '../controllers/FacturaController.php';

$controller = $_GET['controller'] ?? 'factura';
$action = $_GET['action'] ?? 'listar';

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

    default:
        http_response_code(404);
        echo "Controlador no encontrado";
}
