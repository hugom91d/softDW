<?php

class ProductoController
{
    public function codigosPendientes()
    {
        header('Content-Type: application/json');

        if (($_SESSION['rol'] ?? '') !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Acceso no autorizado']);
            return;
        }

        require_once __DIR__ . '/../models/ConfiguracionSincronizacionModel.php';
        $configuracion = (new ConfiguracionSincronizacionModel())->obtener();

        $model = new ProductoModel();
        echo json_encode($model->obtenerCodigosPendientesSincronizacion($configuracion['limite_codigos']), JSON_PRETTY_PRINT);
    }

    public function sincronizarUno()
    {
        header('Content-Type: application/json');

        if (($_SESSION['rol'] ?? '') !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Acceso no autorizado']);
            return;
        }

        $codigo = trim((string) ($_GET['codigo'] ?? ''));
        if ($codigo === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Debe indicar el parámetro codigo']);
            return;
        }

        $model = new ProductoModel();
        echo json_encode($model->sincronizarUnCodigo($codigo), JSON_PRETTY_PRINT);
    }

    public function sincronizarCodigos()
    {
        header('Content-Type: application/json');

        if (($_SESSION['rol'] ?? '') !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Acceso no autorizado']);
            return;
        }

        $model = new ProductoModel();
        $resultado = $model->sincronizarCodigosStock();

        echo json_encode($resultado, JSON_PRETTY_PRINT);
    }

    public function productosPendientesStock()
    {
        header('Content-Type: application/json');

        if (($_SESSION['rol'] ?? '') !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Acceso no autorizado']);
            return;
        }

        require_once __DIR__ . '/../models/ConfiguracionSincronizacionModel.php';
        $configuracion = (new ConfiguracionSincronizacionModel())->obtener();

        $model = new ProductoModel();
        echo json_encode($model->obtenerProductosPendientesStock($configuracion['limite_stock']), JSON_PRETTY_PRINT);
    }

    public function sincronizarStockUno()
    {
        header('Content-Type: application/json');

        if (($_SESSION['rol'] ?? '') !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Acceso no autorizado']);
            return;
        }

        $codigo = trim((string) ($_GET['codigo'] ?? ''));
        $codigoStock = trim((string) ($_GET['codigoStock'] ?? ''));
        if ($codigo === '' || $codigoStock === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Debe indicar codigo y codigoStock']);
            return;
        }

        $model = new ProductoModel();
        echo json_encode($model->sincronizarStockUnProducto($codigo, $codigoStock), JSON_PRETTY_PRINT);
    }

    public function sincronizarStockLote()
    {
        header('Content-Type: application/json');

        if (($_SESSION['rol'] ?? '') !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Acceso no autorizado']);
            return;
        }

        $entrada = json_decode(file_get_contents('php://input'), true);
        $productos = is_array($entrada['productos'] ?? null) ? $entrada['productos'] : [];

        if (empty($productos)) {
            http_response_code(400);
            echo json_encode(['error' => 'Debe indicar el arreglo de productos']);
            return;
        }

        // Se limpia cualquier salida previa (warnings/notices) para no corromper el JSON de respuesta.
        ob_start();
        try {
            $model = new ProductoModel();
            $resultado = $model->sincronizarStockLote($productos);
        } catch (Throwable $e) {
            $resultado = array_map(fn($p) => [
                'codigo' => $p['codigo'] ?? '',
                'actualizado' => false,
                'stock_uio' => 0,
                'stock_baltra' => 0,
                'stock_puerto_ayora' => 0,
                'error' => $e->getMessage(),
            ], $productos);
        }
        ob_end_clean();

        echo json_encode($resultado, JSON_PRETTY_PRINT);
    }
}

