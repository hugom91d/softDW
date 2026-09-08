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

        $model = new ProductoModel();
        echo json_encode($model->obtenerCodigosPendientesSincronizacion(), JSON_PRETTY_PRINT);
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

        $model = new ProductoModel();
        echo json_encode($model->obtenerProductosPendientesStock(), JSON_PRETTY_PRINT);
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
}

