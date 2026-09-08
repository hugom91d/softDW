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
}
