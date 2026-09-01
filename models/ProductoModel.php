    <?php

class ProductoModel
{
    public function listarStock(array $filtros = []): array
    {
        $buscar = trim((string) ($filtros['buscar'] ?? ''));
        $pagina = max(1, (int) ($filtros['pagina'] ?? 1));
        $porPagina = in_array((int) ($filtros['por_pagina'] ?? 10), [10, 20, 50, 100], true)
            ? (int) ($filtros['por_pagina'] ?? 10)
            : 10;

        $conn = $this->getConnection();
        $offset = ($pagina - 1) * $porPagina;
        $descripcionCampo = $this->obtenerCampoDescripcion($conn);
        $fechaCreacionCampo = $this->obtenerCampoFechaCreacion($conn);

        if ($descripcionCampo === null) {
            return [
                'items' => [],
                'total' => 0,
                'pagina' => $pagina,
                'por_pagina' => $porPagina,
                'total_paginas' => 0,
            ];
        }

        $whereSql = '';
        $params = [];
        $types = '';

        if ($buscar !== '') {
            $like = '%' . $buscar . '%';
            $whereSql = ' WHERE (codigo LIKE ? OR ' . $descripcionCampo . ' LIKE ?)';
            $params = [$like, $like];
            $types = 'ss';
        }

        $total = $this->contarProductos($conn, $whereSql, $params, $types);

        $ordenSql = $fechaCreacionCampo !== null
            ? ' ORDER BY ' . $fechaCreacionCampo . ' DESC'
            : ' ORDER BY codigo ASC';
        $sql = "SELECT codigo, " . $descripcionCampo . " AS descripcion, stock_uio, stock_baltra, stock_puerto_ayora FROM productos" . $whereSql . $ordenSql . " LIMIT ? OFFSET ?";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return [
                'items' => [],
                'total' => 0,
                'pagina' => $pagina,
                'por_pagina' => $porPagina,
                'total_paginas' => 0,
            ];
        }

        $bindValues = [];
        $bindTypes = $types . 'ii';
        foreach ($params as $param) {
            $bindValues[] = $param;
        }
        $bindValues[] = $porPagina;
        $bindValues[] = $offset;

        $refs = [];
        foreach ($bindValues as $key => $value) {
            $refs[$key] = &$bindValues[$key];
        }

        if ($bindValues !== []) {
            $stmt->bind_param($bindTypes, ...$refs);
        } else {
            $stmt->bind_param('ii', $porPagina, $offset);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $items = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();

        $totalPaginas = $total > 0 ? (int) ceil($total / $porPagina) : 0;

        return [
            'items' => array_map(function (array $item): array {
                return [
                    'codigo' => trim((string) ($item['codigo'] ?? '')),
                    'descripcion' => (string) ($item['descripcion'] ?? $item['producto'] ?? $item['nombres'] ?? ''),
                    'stock_uio' => $this->formatearCantidad($item['stock_uio'] ?? null),
                    'stock_baltra' => $this->formatearCantidad($item['stock_baltra'] ?? null),
                    'stock_puerto_ayora' => $this->formatearCantidad($item['stock_puerto_ayora'] ?? null),
                ];
            }, $items),
            'total' => $total,
            'pagina' => $pagina,
            'por_pagina' => $porPagina,
            'total_paginas' => $totalPaginas,
        ];
    }

    private function contarProductos(mysqli $conn, string $whereSql, array $params, string $types): int
    {
        $sql = 'SELECT COUNT(*) AS total FROM productos' . $whereSql;
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return 0;
        }

        if ($params !== []) {
            $bindValues = [];
            foreach ($params as $param) {
                $bindValues[] = $param;
            }
            $refs = [];
            foreach ($bindValues as $key => $value) {
                $refs[$key] = &$bindValues[$key];
            }
            $stmt->bind_param($types, ...$refs);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        return (int) ($row['total'] ?? 0);
    }

    private function obtenerCampoDescripcion(mysqli $conn): ?string
    {
        $resultado = $conn->query('SHOW COLUMNS FROM productos');
        if (!$resultado) {
            return null;
        }

        $candidatos = ['nombres', 'nombre', 'producto', 'descripcion', 'descripcion_producto', 'descripcion_corta'];
        $columnas = [];
        while ($fila = $resultado->fetch_assoc()) {
            $columnas[] = strtolower((string) ($fila['Field'] ?? ''));
        }

        foreach ($candidatos as $campo) {
            if (in_array($campo, $columnas, true)) {
                return $campo;
            }
        }

        return $columnas[1] ?? null;
    }

    private function obtenerCampoFechaCreacion(mysqli $conn): ?string
    {
        $resultado = $conn->query('SHOW COLUMNS FROM productos');
        if (!$resultado) {
            return null;
        }

        $candidatos = ['fecha_creacion', 'fecha_registro', 'created_at', 'fecha_alta', 'fecha'];
        $columnas = [];
        while ($fila = $resultado->fetch_assoc()) {
            $columnas[] = strtolower((string) ($fila['Field'] ?? ''));
        }

        foreach ($candidatos as $campo) {
            if (in_array($campo, $columnas, true)) {
                return $campo;
            }
        }

        return null;
    }

    private function getConnection(): mysqli
    {
        require __DIR__ . '/../config/database.php';
        return $conn;
    }

    private function formatearCantidad($cantidad): float
    {
        return $cantidad !== null && $cantidad !== '' ? (float) $cantidad : 0.0;
    }
}
