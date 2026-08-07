<?php

class FacturaController
{
    public function index()
    {
        $model = new FacturaModel();

        $allowedKeys = ['fecha_emision', 'tipo_registro', 'tipo'];
        $filtros = [];

        foreach ($allowedKeys as $key) {
            if (!empty($_GET[$key])) {
                $filtros[$key] = $_GET[$key];
            }
        }

        $facturas = $model->listar($filtros);
        header('Content-Type: application/json');
        echo json_encode($facturas, JSON_PRETTY_PRINT);
    }

    public function listar()
    {

        $model = new FacturaModel();

        $fechaInput = $_GET['fecha_emision'] ?? '';
        if (!empty($fechaInput)) {
            $fechaInput = date('d/m/Y', strtotime($fechaInput));
        }

        $filtros = [
            'fecha_emision' => $fechaInput,
            // 'tipo_registro' => $_GET['tipo_registro'] ?? '',
            // 'tipo' => $_GET['tipo'] ?? '',
            'tipo_registro' => 'CLI',
            'tipo' => 'FAC',
        ];

        $facturas = $model->listar($filtros);

        require __DIR__ . '/../views/facturas.php';
    }

    public function sincronizar()
    {
        $model = new FacturaModel();
        $result = $model->guardarFacturasHoy();

        header('Content-Type: application/json');
        echo json_encode([
            'facturas' => $result['inserted'],
            'inserted_count' => count($result['inserted']),
            'consulted_count' => count($result['consulted']),
        ], JSON_PRETTY_PRINT);
    }
}
