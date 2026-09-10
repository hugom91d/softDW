<?php

require_once __DIR__ . '/HttpClient.php';

class ProductoApi
{
    private array $config;
    private HttpClient $http;

    public function __construct()
    {
        $apis = require __DIR__ . '/../config/api.php';

        $this->config = $apis['inventario'];
        $this->http = new HttpClient();
    }

    public function obtenerStockPorBodega(string $bodega): array
    {
        $config = $this->config[$bodega] ?? null;
        if (!$config || empty($config['token'])) {
            return [];
        }

        $resultado = $this->http->get(
            $config['base_url'],
            [],
            [
                'Authorization: ' . $config['token'],
                'Accept: application/json'
            ]
        );

        return is_array($resultado['data'] ?? null) ? $resultado['data'] : [];
    }

    public function buscarProductoPorCodigo(string $bodega, string $codigo): ?array
    {
        $config = $this->config[$bodega] ?? null;
        if (!$config || empty($config['token']) || trim($codigo) === '') {
            return null;
        }

        $resultado = $this->http->get(
            $config['base_url'],
            ['codigo' => $codigo],
            [
                'Authorization: ' . $config['token'],
                'Accept: application/json'
            ]
        );

        $data = $resultado['data'] ?? null;
        if (!is_array($data)) {
            return null;
        }

        // La API puede responder con un solo objeto o un arreglo de coincidencias
        if (isset($data['id'])) {
            return $data;
        }

        foreach ($data as $item) {
            if (is_array($item) && isset($item['id'])) {
                return $item;
            }
        }

        return null;
    }

    public function consultarStockPorCodigo(string $codigoStock): ?array
    {
        $config = $this->config['stock'] ?? null;
        if (!$config || empty($config['token']) || empty($config['stock_url']) || trim($codigoStock) === '') {
            return null;
        }

        $url = rtrim($config['stock_url'], '/') . '/' . rawurlencode($codigoStock) . '/stock/';

        $resultado = $this->http->get(
            $url,
            [],
            [
                'Authorization: ' . $config['token'],
                'Accept: application/json'
            ]
        );

        $data = $resultado['data'] ?? null;

        return is_array($data) ? $data : null;
    }

    /**
     * Consulta el stock de varios códigos en paralelo (curl_multi), procesando en
     * chunks para no abrir miles de conexiones simultáneas contra la API externa.
     * Devuelve un mapa [codigoStock => bodegas] (o null si falló ese código en particular).
     */
    public function consultarStockPorCodigosEnLote(array $codigosStock, int $tamanoChunk = 30): array
    {
        $config = $this->config['stock'] ?? null;
        if (!$config || empty($config['token']) || empty($config['stock_url'])) {
            return [];
        }

        $codigosStock = array_values(array_unique(array_filter(array_map('trim', $codigosStock), fn($c) => $c !== '')));
        if (empty($codigosStock)) {
            return [];
        }

        $headers = [
            'Authorization: ' . $config['token'],
            'Accept: application/json'
        ];
        $baseUrl = rtrim($config['stock_url'], '/');

        $resultado = [];
        foreach (array_chunk($codigosStock, max(1, $tamanoChunk)) as $chunk) {
            $requests = [];
            foreach ($chunk as $codigoStock) {
                $requests[$codigoStock] = [
                    'url' => $baseUrl . '/' . rawurlencode($codigoStock) . '/stock/',
                    'headers' => $headers,
                ];
            }

            $respuestas = $this->http->getMulti($requests);
            foreach ($respuestas as $codigoStock => $respuesta) {
                $data = $respuesta['data'] ?? null;
                $resultado[$codigoStock] = is_array($data) ? $data : null;
            }
        }

        return $resultado;
    }

    /**
     * Busca varios códigos de producto en paralelo (curl_multi, por chunks) para
     * obtener su id de stock. Devuelve un mapa [codigo => idStock] (solo encontrados).
     */
    public function buscarProductosPorCodigosEnLote(string $bodega, array $codigos, int $tamanoChunk = 30): array
    {
        $config = $this->config[$bodega] ?? null;
        if (!$config || empty($config['token'])) {
            return [];
        }

        $codigos = array_values(array_unique(array_filter(array_map('trim', $codigos), fn($c) => $c !== '')));
        if (empty($codigos)) {
            return [];
        }

        $headers = [
            'Authorization: ' . $config['token'],
            'Accept: application/json'
        ];

        $resultado = [];
        foreach (array_chunk($codigos, max(1, $tamanoChunk)) as $chunk) {
            $requests = [];
            foreach ($chunk as $codigo) {
                $requests[$codigo] = [
                    'url' => $config['base_url'],
                    'params' => ['codigo' => $codigo],
                    'headers' => $headers,
                ];
            }

            $respuestas = $this->http->getMulti($requests);
            foreach ($respuestas as $codigo => $respuesta) {
                $data = $respuesta['data'] ?? null;
                if (!is_array($data)) {
                    continue;
                }

                $item = isset($data['id']) ? $data : null;
                if ($item === null) {
                    foreach ($data as $posible) {
                        if (is_array($posible) && isset($posible['id'])) {
                            $item = $posible;
                            break;
                        }
                    }
                }

                $idStock = trim((string) ($item['id'] ?? ''));
                if ($idStock !== '') {
                    $resultado[$codigo] = $idStock;
                }
            }
        }

        return $resultado;
    }
}
