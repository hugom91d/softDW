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
        $ordenSolicitado = (string) ($filtros['orden'] ?? '');
        $direccionSolicitada = strtolower((string) ($filtros['direccion'] ?? ''));

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
        $ordenesPermitidos = [
            'codigo' => 'codigo',
            'descripcion' => $descripcionCampo,
            'stock_uio' => 'stock_uio',
            'stock_baltra' => 'stock_baltra',
            'stock_puerto_ayora' => 'stock_puerto_ayora',
            'estado' => 'estado',
        ];
        if (isset($ordenesPermitidos[$ordenSolicitado])) {
            $direccion = $direccionSolicitada === 'asc' ? 'ASC' : 'DESC';
            $ordenSql = ' ORDER BY ' . $ordenesPermitidos[$ordenSolicitado] . ' ' . $direccion . ', codigo ASC';
        }

        $sql = "SELECT codigo, " . $descripcionCampo . " AS descripcion, stock_uio, stock_baltra, stock_puerto_ayora, estado FROM productos" . $whereSql . $ordenSql . " LIMIT ? OFFSET ?";

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
                    'estado' => strtoupper(trim((string) ($item['estado'] ?? ''))),
                ];
            }, $items),
            'total' => $total,
            'pagina' => $pagina,
            'por_pagina' => $porPagina,
            'total_paginas' => $totalPaginas,
        ];
    }

    public function contarStockBajoPorBodega(float $limiteCritico = 5): array
    {
        $conn = $this->getConnection();
        $stmt = $conn->prepare(
            'SELECT
                SUM(CASE WHEN COALESCE(stock_uio, 0) < ? THEN 1 ELSE 0 END) AS uio,
                SUM(CASE WHEN COALESCE(stock_baltra, 0) < ? THEN 1 ELSE 0 END) AS baltra,
                SUM(CASE WHEN COALESCE(stock_puerto_ayora, 0) < ? THEN 1 ELSE 0 END) AS ayora
            FROM productos'
        );

        if (!$stmt) {
            return ['uio' => 0, 'baltra' => 0, 'ayora' => 0];
        }

        $stmt->bind_param('ddd', $limiteCritico, $limiteCritico, $limiteCritico);
        $stmt->execute();
        $result = $stmt->get_result();
        $stockBajo = $result ? $result->fetch_assoc() : [];
        $stmt->close();

        return [
            'uio' => (int) ($stockBajo['uio'] ?? 0),
            'baltra' => (int) ($stockBajo['baltra'] ?? 0),
            'ayora' => (int) ($stockBajo['ayora'] ?? 0),
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

    public function obtenerCodigosPendientesSincronizacion(int $limite = 0): array
    {
        $conn = $this->getConnection();
        $descripcionCampo = $this->obtenerCampoDescripcion($conn);
        $selectDescripcion = $descripcionCampo !== null ? ", $descripcionCampo AS descripcion" : ", '' AS descripcion";

        // $limite <= 0 significa sin límite
        $sql = "SELECT codigo" . $selectDescripcion . " FROM productos WHERE codigo IS NOT NULL AND codigo <> '' AND (codigoStock IS NULL OR codigoStock = '') ORDER BY codigo ASC";

        if ($limite > 0) {
            $stmt = $conn->prepare($sql . ' LIMIT ?');
            if (!$stmt) {
                return [];
            }
            $stmt->bind_param('i', $limite);
            $stmt->execute();
            $resultado = $stmt->get_result();
        } else {
            $resultado = $conn->query($sql);
        }

        if (!$resultado) {
            return [];
        }

        $codigos = [];
        while ($fila = $resultado->fetch_assoc()) {
            $codigo = trim((string) ($fila['codigo'] ?? ''));
            if ($codigo !== '') {
                $codigos[] = [
                    'codigo' => $codigo,
                    'descripcion' => trim((string) ($fila['descripcion'] ?? '')),
                ];
            }
        }

        return $codigos;
    }

    public function actualizarCodigoStock(string $codigo, string $codigoStock): bool
    {
        $conn = $this->getConnection();
        $stmt = $conn->prepare(
            "UPDATE productos SET codigoStock = ? WHERE codigo = ? AND (codigoStock IS NULL OR codigoStock = '')"
        );
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('ss', $codigoStock, $codigo);
        $success = $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();

        return $success && $affected > 0;
    }

    public function sincronizarUnCodigo(string $codigo): array
    {
        require_once __DIR__ . '/../services/ProductoApi.php';

        $codigo = trim($codigo);
        if ($codigo === '') {
            return ['codigo' => $codigo, 'encontrado' => false, 'error' => 'Código vacío'];
        }

        $api = new ProductoApi();

        try {
            $producto = $api->buscarProductoPorCodigo('stock', $codigo);
        } catch (Exception $e) {
            return ['codigo' => $codigo, 'encontrado' => false, 'error' => $e->getMessage()];
        }

        $idStock = trim((string) ($producto['id'] ?? ''));
        if ($idStock === '') {
            return ['codigo' => $codigo, 'encontrado' => false];
        }

        $actualizado = $this->actualizarCodigoStock($codigo, $idStock);

        return [
            'codigo' => $codigo,
            'encontrado' => true,
            'codigoStock' => $idStock,
            'actualizado' => $actualizado,
        ];
    }

    public function sincronizarCodigosStock(): array
    {
        require_once __DIR__ . '/../services/ProductoApi.php';

        $api = new ProductoApi();
        $codigos = $this->obtenerCodigosPendientesSincronizacion();

        $actualizados = [];
        $noEncontrados = [];
        $errores = [];

        foreach ($codigos as $item) {
            $codigo = $item['codigo'];
            try {
                $producto = $api->buscarProductoPorCodigo('stock', $codigo);
            } catch (Exception $e) {
                $errores[] = $codigo;
                continue;
            }

            $idStock = trim((string) ($producto['id'] ?? ''));
            if ($idStock === '') {
                $noEncontrados[] = $codigo;
                continue;
            }

            if ($this->actualizarCodigoStock($codigo, $idStock)) {
                $actualizados[] = ['codigo' => $codigo, 'codigoStock' => $idStock];
            } else {
                $errores[] = $codigo;
            }
        }

        return [
            'total' => count($codigos),
            'actualizados' => $actualizados,
            'no_encontrados' => $noEncontrados,
            'errores' => $errores,
        ];
    }

    private function getConnection(): mysqli
    {
        require __DIR__ . '/../config/database.php';
        return $conn;
    }

    public function obtenerProductosPendientesStock(int $limite = 5): array
    {
        $conn = $this->getConnection();
        $descripcionCampo = $this->obtenerCampoDescripcion($conn);
        $selectDescripcion = $descripcionCampo !== null ? ", $descripcionCampo AS descripcion" : ", '' AS descripcion";

        $sql = "SELECT codigo, codigoStock" . $selectDescripcion . " FROM productos WHERE estado = 'A' AND codigoStock IS NOT NULL AND codigoStock <> '' ORDER BY RAND()";

        if ($limite > 0) {
            $stmt = $conn->prepare($sql . ' LIMIT ?');
            if (!$stmt) {
                return [];
            }
            $stmt->bind_param('i', $limite);
            $stmt->execute();
            $resultado = $stmt->get_result();
        } else {
            $resultado = $conn->query($sql);
        }

        if (!$resultado) {
            return [];
        }

        $productos = [];
        while ($fila = $resultado->fetch_assoc()) {
            $codigo = trim((string) ($fila['codigo'] ?? ''));
            $codigoStock = trim((string) ($fila['codigoStock'] ?? ''));
            if ($codigo !== '' && $codigoStock !== '') {
                $productos[] = [
                    'codigo' => $codigo,
                    'codigoStock' => $codigoStock,
                    'descripcion' => trim((string) ($fila['descripcion'] ?? '')),
                ];
            }
        }

        return $productos;
    }

    public function sincronizarStockUnProducto(string $codigo, string $codigoStock): array
    {
        require_once __DIR__ . '/../services/ProductoApi.php';

        $codigo = trim($codigo);
        $codigoStock = trim($codigoStock);
        if ($codigo === '' || $codigoStock === '') {
            return ['codigo' => $codigo, 'actualizado' => false, 'error' => 'Faltan datos del producto'];
        }

        $api = new ProductoApi();

        try {
            $bodegas = $api->consultarStockPorCodigo($codigoStock);
        } catch (Exception $e) {
            return ['codigo' => $codigo, 'actualizado' => false, 'error' => $e->getMessage()];
        }

        if (!is_array($bodegas)) {
            return ['codigo' => $codigo, 'actualizado' => false, 'error' => 'Respuesta inválida de la API'];
        }

        $stock = [
            'stock_uio' => 0.0,
            'stock_baltra' => 0.0,
            'stock_puerto_ayora' => 0.0,
        ];

        foreach ($bodegas as $item) {
            if (!is_array($item)) {
                continue;
            }

            $columna = $this->mapearColumnaBodega((string) ($item['bodega_nombre'] ?? ''));
            if ($columna !== null) {
                $stock[$columna] = (float) ($item['cantidad'] ?? 0);
            }
        }

        $actualizado = $this->actualizarStockProducto($codigo, $stock);

        return [
            'codigo' => $codigo,
            'actualizado' => $actualizado,
            'stock_uio' => $stock['stock_uio'],
            'stock_baltra' => $stock['stock_baltra'],
            'stock_puerto_ayora' => $stock['stock_puerto_ayora'],
        ];
    }

    public function actualizarStockProducto(string $codigo, array $stock): bool
    {
        $conn = $this->getConnection();
        $stmt = $conn->prepare(
            'UPDATE productos SET stock_uio = ?, stock_baltra = ?, stock_puerto_ayora = ? WHERE codigo = ?'
        );
        if (!$stmt) {
            return false;
        }

        $uio = (float) ($stock['stock_uio'] ?? 0.0);
        $baltra = (float) ($stock['stock_baltra'] ?? 0.0);
        $ayora = (float) ($stock['stock_puerto_ayora'] ?? 0.0);

        $stmt->bind_param('ddds', $uio, $baltra, $ayora, $codigo);
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }

    private function mapearColumnaBodega(string $nombreBodega): ?string
    {
        $nombre = mb_strtolower($nombreBodega, 'UTF-8');

        if ($nombre === '') {
            return null;
        }

        if (str_contains($nombre, 'dw') || str_contains($nombre, 'import-export') || str_contains($nombre, 'import export')) {
            return 'stock_uio';
        }

        if (str_contains($nombre, 'baltra')) {
            return 'stock_baltra';
        }

        if (str_contains($nombre, 'ayora')) {
            return 'stock_puerto_ayora';
        }

        return null;
    }

    private function formatearCantidad($cantidad): float
    {
        return $cantidad !== null && $cantidad !== '' ? (float) $cantidad : 0.0;
    }
}
