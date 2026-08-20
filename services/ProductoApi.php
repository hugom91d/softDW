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
}
