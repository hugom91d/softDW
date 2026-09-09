<?php

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../services/FacturaApi.php';

class FacturaModel
{
    private FacturaApi $api;

    public function __construct()
    {
        $this->api = new FacturaApi();
    }

    public function listar(array $filtros = []): array
    {
        $resultado = $this->api->obtenerFacturas($filtros);

        if (!is_array($resultado) || empty($resultado['data']) || !is_array($resultado['data'])) {
            return [];
        }

        $facturas = $resultado['data']['results'] ?? [];
        if (!is_array($facturas)) {
            return [];
        }

        usort($facturas, static function (array $facturaA, array $facturaB): int {
            $fechaA = strtotime(trim((string) ($facturaA['fecha_emision'] ?? '')) . ' ' . trim((string) ($facturaA['hora_emision'] ?? ''))) ?: 0;
            $fechaB = strtotime(trim((string) ($facturaB['fecha_emision'] ?? '')) . ' ' . trim((string) ($facturaB['hora_emision'] ?? ''))) ?: 0;

            if ($fechaA === $fechaB) {
                return (int) ($facturaB['id'] ?? 0) <=> (int) ($facturaA['id'] ?? 0);
            }

            return $fechaB <=> $fechaA;
        });

        return $facturas;
    }

    public function getFacturasAbiertasHoy(): array
    {
        $conn = $this->getConnection();
        $stmt = $conn->prepare(
            "SELECT id_factura, numero_factura, fecha, fecha_cierre, estado, estado_factura, id_responsable, sede
            FROM factura
            WHERE estado = 0
            ORDER BY fecha DESC, id_factura DESC"
        );

        if (!$stmt) {
            return [];
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $facturas = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();

        return is_array($facturas) ? $facturas : [];
    }

    public function getFacturasCerradasPorFecha(string $fechaInicio, string $fechaFin): array
    {
        $fechaInicio = trim($fechaInicio);
        $fechaFin = trim($fechaFin);
        $inicio = DateTime::createFromFormat('!Y-m-d', $fechaInicio);
        $fin = DateTime::createFromFormat('!Y-m-d', $fechaFin);
        if (
            $inicio === false || $inicio->format('Y-m-d') !== $fechaInicio
            || $fin === false || $fin->format('Y-m-d') !== $fechaFin
            || $inicio > $fin
        ) {
            return [];
        }

        $inicioSql = $inicio->format('Y-m-d 00:00:00');
        $finSql = $fin->modify('+1 day')->format('Y-m-d 00:00:00');

        $conn = $this->getConnection();
        $stmt = $conn->prepare(
            "SELECT f.numero_factura, f.fecha, f.fecha_cierre,
                    COALESCE(NULLIF(TRIM(u.Nombre), ''), 'Sin responsable') AS responsable
             FROM factura f
             LEFT JOIN usuarios u ON CAST(u.Cedula AS CHAR) = CAST(f.id_responsable AS CHAR)
             WHERE f.estado = 1 AND f.fecha >= ? AND f.fecha < ?
             ORDER BY f.fecha_cierre ASC, f.id_factura ASC"
        );

        if (!$stmt) {
            return [];
        }

        $stmt->bind_param('ss', $inicioSql, $finSql);
        $stmt->execute();
        $result = $stmt->get_result();
        $facturas = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();

        return is_array($facturas) ? $facturas : [];
    }

    public function getDetalleFactura(int $idFactura): array
    {
        $conn = $this->getConnection();
        $stmt = $conn->prepare("SELECT id_detalle, cantidad, codigo_prenda, codigo_interno, descripcion, estado FROM detalle_factura WHERE id_factura = ?");
        if (!$stmt) {
            return [];
        }

        $stmt->bind_param('i', $idFactura);
        $stmt->execute();
        $result = $stmt->get_result();
        $detalles = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();

        return is_array($detalles) ? $detalles : [];
    }

    public function facturaTieneDetallesAbiertos(int $idFactura): bool
    {
        $conn = $this->getConnection();
        $stmt = $conn->prepare("SELECT 1 FROM detalle_factura WHERE id_factura = ? AND estado = 0 LIMIT 1");
        if (!$stmt) {
            return true;
        }

        $stmt->bind_param('i', $idFactura);
        $stmt->execute();
        $stmt->store_result();
        $hasOpen = $stmt->num_rows > 0;
        $stmt->close();

        return $hasOpen;
    }

    public function cerrarFactura(int $idFactura, string $idResponsable): bool
    {
        if ($this->facturaTieneDetallesAbiertos($idFactura)) {
            return false;
        }

        $conn = $this->getConnection();
        $fechaCierre = (new DateTime('now', new DateTimeZone('America/Guayaquil')))->format('Y-m-d H:i:s');
        $stmt = $conn->prepare("UPDATE factura SET estado = 1, fecha_cierre = ?, id_responsable = ? WHERE id_factura = ? AND estado = 0 AND (estado_factura IS NULL OR estado_factura <> 'A')");
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('ssi', $fechaCierre, $idResponsable, $idFactura);
        $success = $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();

        return $success && $affected > 0;
    }

    public function archivarFacturaAnulada(int $idFactura): bool
    {
        $conn = $this->getConnection();
        $stmt = $conn->prepare("UPDATE factura SET estado = 1 WHERE id_factura = ? AND estado_factura = 'A'");
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('i', $idFactura);
        $success = $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();

        return $success && $affected > 0;
    }

    public function marcarDetalleComoRepuesto(int $idDetalle): bool
    {
        $conn = $this->getConnection();
        $stmt = $conn->prepare("UPDATE detalle_factura SET estado = 1 WHERE id_detalle = ? AND estado = 0");
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('i', $idDetalle);
        $success = $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();

        return $success && $affected > 0;
    }

    public function marcarDetalleComoNoRepuesto(int $idDetalle): bool
    {
        $conn = $this->getConnection();
        $fechaNoRepuesto = (new DateTime('now', new DateTimeZone('America/Guayaquil')))->format('Y-m-d H:i:s');
        $stmt = $conn->prepare("UPDATE detalle_factura SET estado = 2, fecha_no_repuesto = ? WHERE id_detalle = ? AND estado = 0");
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('si', $fechaNoRepuesto, $idDetalle);
        $success = $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();

        return $success && $affected > 0;
    }

    public function marcarDetalleComoNoRepuestoConObservacion(int $idDetalle, string $observacion = ''): bool
    {
        $conn = $this->getConnection();
        $fechaNoRepuesto = (new DateTime('now', new DateTimeZone('America/Guayaquil')))->format('Y-m-d H:i:s');
        $stmt = $conn->prepare("UPDATE detalle_factura SET estado = 2, observacion = ?, fecha_no_repuesto = ? WHERE id_detalle = ? AND estado = 0");
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('ssi', $observacion, $fechaNoRepuesto, $idDetalle);
        $success = $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();

        return $success && $affected > 0;
    }

    public function getDetallesNoRepuestos(string $fechaInicio, string $fechaFin): array
    {
        $fechaInicio = trim($fechaInicio);
        $fechaFin = trim($fechaFin);
        $inicio = DateTime::createFromFormat('!Y-m-d', $fechaInicio);
        $fin = DateTime::createFromFormat('!Y-m-d', $fechaFin);
        if (
            $inicio === false || $inicio->format('Y-m-d') !== $fechaInicio
            || $fin === false || $fin->format('Y-m-d') !== $fechaFin
            || $inicio > $fin
        ) {
            return [];
        }

        $inicioSql = $inicio->format('Y-m-d 00:00:00');
        $finSql = $fin->modify('+1 day')->format('Y-m-d 00:00:00');
        $conn = $this->getConnection();
        $stmt = $conn->prepare(
            "SELECT f.numero_factura,
                    COALESCE(NULLIF(TRIM(u.Nombre), ''), 'Sin responsable') AS responsable,
                    d.codigo_interno AS codigo_producto,
                    d.descripcion AS nombre_producto,
                    d.cantidad,
                    COALESCE(NULLIF(TRIM(d.observacion), ''), 'Sin observación') AS observacion,
                    f.fecha AS fecha_factura,
                    f.fecha_cierre
             FROM detalle_factura d
             INNER JOIN factura f ON f.id_factura = d.id_factura
             LEFT JOIN usuarios u ON CAST(u.Cedula AS CHAR) = CAST(f.id_responsable AS CHAR)
             WHERE d.estado = 2 AND f.fecha >= ? AND f.fecha < ?
             ORDER BY f.fecha DESC, f.id_factura DESC, d.id_detalle DESC"
        );

        if (!$stmt) {
            return [];
        }

        $stmt->bind_param('ss', $inicioSql, $finSql);
        $stmt->execute();
        $result = $stmt->get_result();
        $detalles = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();

        return is_array($detalles) ? $detalles : [];
    }

    private function getConnection(): mysqli
    {
        require __DIR__ . '/../config/database.php';
        return $conn;
    }

    private function parseFechaEmision(string $fecha, string $hora = ''): string
    {
        $fecha = trim($fecha);
        $hora = trim($hora);

        if (empty($fecha)) {
            return date('Y-m-d H:i:s');
        }

        $parsed = $hora !== ''
            ? DateTime::createFromFormat('!d/m/Y H:i:s', $fecha . ' ' . $hora)
            : DateTime::createFromFormat('!d/m/Y', $fecha);
        if ($parsed === false) {
            $parsed = date_create($fecha . ($hora !== '' ? ' ' . $hora : ''));
        }

        return $parsed ? $parsed->format('Y-m-d H:i:s') : date('Y-m-d H:i:s');
    }

    private function facturaExiste(string $numeroFactura): bool
    {
        $conn = $this->getConnection();
        $stmt = $conn->prepare("SELECT id_factura FROM factura WHERE numero_factura = ? LIMIT 1");
        $stmt->bind_param('s', $numeroFactura);
        $stmt->execute();
        $stmt->store_result();
        $exists = $stmt->num_rows > 0;
        $stmt->close();

        return $exists;
    }

    private function obtenerIdResponsableActual(): int
    {
        $valor = $_SESSION['cedula'] ?? $_SESSION['id_usuario'] ?? $_SESSION['user_id'] ?? $_SESSION['id'] ?? '';

        if (is_numeric($valor)) {
            return (int) $valor;
        }

        if (is_string($valor) && preg_match('/^\d+$/', trim($valor)) === 1) {
            return (int) trim($valor);
        }

        return 0;
    }

    private function insertarFacturaLocal(array $factura, int $idResponsable): int
    {
        $numeroFactura = $factura['documento'] ?? '';
        if (empty($numeroFactura) || $this->facturaExiste($numeroFactura)) {
            return 0;
        }

        if ($idResponsable <= 0) {
            return 0;
        }

        $fecha = $this->parseFechaEmision($factura['fecha_emision'] ?? '', $factura['hora_emision'] ?? '');
        $estado = 0;
        $estadoFactura = (string) ($factura['estado'] ?? '');
        $sede = str_starts_with($numeroFactura, '002-002') ? 'Baltra' : 'Pto. Ayora';

        $conn = $this->getConnection();
        $stmt = $conn->prepare("INSERT INTO factura (numero_factura, fecha, fecha_cierre, estado, estado_factura, id_responsable, sede) VALUES (?, ?, NULL, ?, ?, ?, ?)");
        if (!$stmt) {
            return 0;
        }
        $stmt->bind_param('ssisis', $numeroFactura, $fecha, $estado, $estadoFactura, $idResponsable, $sede);
        $stmt->execute();

        $insertId = $stmt->insert_id;
        $stmt->close();

        return $insertId;
    }

    private function obtenerCodigoInternoProducto(string $codigoPrenda): ?string
    {
        if ($codigoPrenda === '') {
            return null;
        }

        $conn = $this->getConnection();
        $stmt = $conn->prepare("SELECT codigo FROM productos WHERE codigo_interno = ? LIMIT 1");
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param('s', $codigoPrenda);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        return $row['codigo'] ?? null;
    }

    private const PRODUCTOS_ID_EXCLUIDOS = ['KVeZYXp6kHyJyd8P', 'O8bYWX0EzSPrPe7j', 'loejZVl7OikWkeQM'];

    private function insertarDetalleFacturaLocal(int $idFactura, array $detalle): bool
    {
        $productoNombre = $detalle['producto_nombre'] ?? '';
        $productoDescripcion = $detalle['producto_descipcion'] ?? '';
        $codigoPrenda = $detalle['producto_id'] ?? '';

        if (in_array((string) $codigoPrenda, self::PRODUCTOS_ID_EXCLUIDOS, true)) {
            return false;
        }

        $textoComparacion = mb_strtolower(trim($productoNombre . ' ' . $productoDescripcion), 'UTF-8');
        if (str_contains($textoComparacion, 'envio') || str_contains($textoComparacion, 'shipping')) {
            return false;
        }

        $cantidad = isset($detalle['cantidad']) ? (int) $detalle['cantidad'] : 0;
        $codigoInterno = $this->obtenerCodigoInternoProducto((string) $codigoPrenda);
        $descripcion = $productoNombre ?: $productoDescripcion;
        $estado = 0;

        $conn = $this->getConnection();
        $stmt = $conn->prepare("INSERT INTO detalle_factura (id_factura, cantidad, codigo_prenda, codigo_interno, descripcion, estado) VALUES (?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('iisssi', $idFactura, $cantidad, $codigoPrenda, $codigoInterno, $descripcion, $estado);
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }

    public function guardarFacturasHoy(?int $idResponsable = null): array
    {
        $fechaHoy = date('d/m/Y');
        $filtros = [
            'fecha_emision' => $fechaHoy,
            'tipo_registro' => 'CLI',
            'tipo' => 'FAC',
        ];

        if ($idResponsable === null || $idResponsable <= 0) {
            $idResponsable = $this->obtenerIdResponsableActual();
        }

        $facturas = $this->listar($filtros);
        $inserted = [];

        foreach ($facturas as $factura) {
            $numeroFactura = (string) ($factura['documento'] ?? '');
            if ($numeroFactura === '') {
                continue;
            }

            $idFactura = $this->insertarFacturaLocal($factura, $idResponsable);
            if ($idFactura <= 0) {
                continue;
            }

            if (!empty($factura['detalles']) && is_array($factura['detalles'])) {
                foreach ($factura['detalles'] as $detalle) {
                    $this->insertarDetalleFacturaLocal($idFactura, $detalle);
                }
            }

            $inserted[] = [
                'documento' => $numeroFactura,
                'fecha' => $factura['fecha_emision'] ?? '',
                'hora' => $factura['hora_emision'] ?? '',
            ];
        }

        if (!empty($inserted)) {
            $this->guardarLogSincronizacion($inserted, $idResponsable);
            if (PHP_SAPI === 'cli') {
                $this->guardarNotificacionSincronizacion($inserted);
            }
        }

        return [
            'consulted' => $facturas,
            'inserted' => $inserted,
            'responsable' => $idResponsable,
        ];
    }

    private function guardarLogSincronizacion(array $facturasInsertadas, int $idResponsable): void
    {
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir) && !mkdir($logDir, 0775, true) && !is_dir($logDir)) {
            return;
        }

        $fechaActual = date('Y-m-d');
        $logPath = $logDir . '/sincronizacion_' . $fechaActual . '.txt';
        $timestamp = date('Y-m-d H:i:s');
        $lista = [];
        $lista[] = 'Fecha/Hora: ' . $timestamp;
        $lista[] = 'Responsable: ' . $idResponsable;
        $lista[] = 'Total sincronizadas: ' . count($facturasInsertadas);
        $lista[] = '---';

        foreach ($facturasInsertadas as $factura) {
            $documento = (string) ($factura['documento'] ?? '');
            if ($documento !== '') {
                $lista[] = $documento;
            }
        }

        $lista[] = '==================================================';
        $contenido = implode(PHP_EOL, $lista) . PHP_EOL;
        file_put_contents($logPath, $contenido, LOCK_EX);
    }

    private function guardarNotificacionSincronizacion(array $facturasInsertadas): void
    {
        $notificacionPath = __DIR__ . '/../logs/notificacion_sincronizacion.json';
        $notificacion = [
            'inserted_count' => count($facturasInsertadas),
            'documentos' => array_values(array_filter(array_map(
                static fn(array $factura): string => (string) ($factura['documento'] ?? ''),
                $facturasInsertadas
            ))),
            'created_at' => date('Y-m-d H:i:s'),
        ];

        file_put_contents($notificacionPath, json_encode($notificacion), LOCK_EX);
    }

    public function obtenerNotificacionSincronizacion(): ?array
    {
        $notificacionPath = __DIR__ . '/../logs/notificacion_sincronizacion.json';
        if (!is_file($notificacionPath)) {
            return null;
        }

        $contenido = file_get_contents($notificacionPath);
        if ($contenido === false) {
            return null;
        }

        $notificacion = json_decode($contenido, true);
        if (!is_array($notificacion)) {
            return null;
        }

        return $notificacion;
    }
}
