<?php

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
            "SELECT id_factura, numero_factura, fecha, fecha_cierre, estado, id_responsable, sede
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
        $stmt = $conn->prepare("UPDATE factura SET estado = 1, fecha_cierre = ?, id_responsable = ? WHERE id_factura = ? AND estado = 0");
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('ssi', $fechaCierre, $idResponsable, $idFactura);
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
        $stmt = $conn->prepare("UPDATE detalle_factura SET estado = 2 WHERE id_detalle = ? AND estado = 0");
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('i', $idDetalle);
        $success = $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();

        return $success && $affected > 0;
    }

    public function marcarDetalleComoNoRepuestoConObservacion(int $idDetalle, string $observacion = ''): bool
    {
        $conn = $this->getConnection();
        $stmt = $conn->prepare("UPDATE detalle_factura SET estado = 2, observacion = ? WHERE id_detalle = ? AND estado = 0");
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('si', $observacion, $idDetalle);
        $success = $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();

        return $success && $affected > 0;
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

    private function insertarFacturaLocal(array $factura): int
    {
        $numeroFactura = $factura['documento'] ?? '';
        if (empty($numeroFactura) || $this->facturaExiste($numeroFactura)) {
            return 0;
        }

        $fecha = $this->parseFechaEmision($factura['fecha_emision'] ?? '', $factura['hora_emision'] ?? '');
        $estado = 0;
        $idResponsable = 1719893057;
        $sede = str_starts_with($numeroFactura, '002-002') ? 'Baltra' : 'Pto. Ayora';

        $conn = $this->getConnection();
        $stmt = $conn->prepare("INSERT INTO factura (numero_factura, fecha, fecha_cierre, estado, id_responsable, sede) VALUES (?, ?, NULL, ?, ?, ?)");
        if (!$stmt) {
            return 0;
        }
        $stmt->bind_param('ssiis', $numeroFactura, $fecha, $estado, $idResponsable, $sede);
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

    public function guardarFacturasHoy(): array
    {
        $fechaHoy = date('d/m/Y');
        $filtros = [
            'fecha_emision' => $fechaHoy,
            'tipo_registro' => 'CLI',
            'tipo' => 'FAC',
        ];

        $facturas = $this->listar($filtros);
        $inserted = [];

        foreach ($facturas as $factura) {
            $idFactura = $this->insertarFacturaLocal($factura);
            if ($idFactura <= 0) {
                continue;
            }

            if (!empty($factura['detalles']) && is_array($factura['detalles'])) {
                foreach ($factura['detalles'] as $detalle) {
                    $this->insertarDetalleFacturaLocal($idFactura, $detalle);
                }
            }

            $inserted[] = $factura;
        }

        return [
            'consulted' => $facturas,
            'inserted' => $inserted,
        ];
    }
}
