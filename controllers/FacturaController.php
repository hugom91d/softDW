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

    public function reporteCerradas()
    {
        $fechaInicio = (string) ($_GET['fecha_inicio'] ?? date('Y-m-d'));
        $fechaFin = (string) ($_GET['fecha_fin'] ?? $fechaInicio);
        $model = new FacturaModel();
        $facturas = $model->getFacturasCerradasPorFecha($fechaInicio, $fechaFin);

        require __DIR__ . '/../views/reporte_facturas.php';
    }

    public function descargarReporteCerradas()
    {
        $fechaInicio = (string) ($_GET['fecha_inicio'] ?? '');
        $fechaFin = (string) ($_GET['fecha_fin'] ?? '');
        $model = new FacturaModel();
        $facturas = $model->getFacturasCerradasPorFecha($fechaInicio, $fechaFin);

        $nombreArchivo = 'facturas_cerradas_' . preg_replace('/[^0-9-]/', '', $fechaInicio) . '_a_' . preg_replace('/[^0-9-]/', '', $fechaFin) . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');

        $salida = fopen('php://output', 'wb');
        fwrite($salida, "\xEF\xBB\xBF");
        fputcsv($salida, ['Numero de factura', 'Nombre del responsable', 'Fecha de factura', 'Fecha de cierre'], ';', '"', '\\');

        foreach ($facturas as $factura) {
            fputcsv($salida, [
                $factura['numero_factura'] ?? '',
                $factura['responsable'] ?? 'Sin responsable',
                $factura['fecha'] ?? '',
                $factura['fecha_cierre'] ?? '',
            ], ';', '"', '\\');
        }

        fclose($salida);
    }

    public function reporteNoRepuestos()
    {
        $fechaInicio = (string) ($_GET['fecha_inicio'] ?? date('Y-m-d'));
        $fechaFin = (string) ($_GET['fecha_fin'] ?? $fechaInicio);
        $model = new FacturaModel();
        $detalles = $model->getDetallesNoRepuestos($fechaInicio, $fechaFin);

        require __DIR__ . '/../views/reporte_no_repuestos.php';
    }

    public function descargarReporteNoRepuestos()
    {
        $fechaInicio = (string) ($_GET['fecha_inicio'] ?? '');
        $fechaFin = (string) ($_GET['fecha_fin'] ?? '');
        $model = new FacturaModel();
        $detalles = $model->getDetallesNoRepuestos($fechaInicio, $fechaFin);

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="reporte_no_repuestos.csv"');

        $salida = fopen('php://output', 'wb');
        fwrite($salida, "\xEF\xBB\xBF");
        fputcsv($salida, ['Numero de factura', 'Responsable', 'Codigo', 'Nombre del producto', 'Cantidad', 'Observacion', 'Fecha de factura', 'Fecha de cierre'], ';', '"', '\\');

        foreach ($detalles as $detalle) {
            fputcsv($salida, [
                $detalle['numero_factura'] ?? '',
                $detalle['responsable'] ?? 'Sin responsable',
                $detalle['codigo_producto'] ?? '',
                $detalle['nombre_producto'] ?? '',
                $detalle['cantidad'] ?? '',
                $detalle['observacion'] ?? 'Sin observación',
                $detalle['fecha_factura'] ?? '',
                $detalle['fecha_cierre'] ?? '',
            ], ';', '"', '\\');
        }

        fclose($salida);
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

    public function notificacionSincronizacion()
    {
        $model = new FacturaModel();
        $notificacion = $model->obtenerNotificacionSincronizacion();

        header('Content-Type: application/json');
        echo json_encode($notificacion ?? ['inserted_count' => 0]);
    }
}
