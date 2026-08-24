<?php

require_once __DIR__ . '/../services/ProductoApi.php';

class ProductoModel
{
    private ProductoApi $api;

    public function __construct()
    {
        $this->api = new ProductoApi();
    }

    public function listarStock(): array
    {
        $dw = $this->api->obtenerStockPorBodega('dw');
        $baltra = $this->indexarPorId($this->api->obtenerStockPorBodega('baltra'));
        $ayora = $this->indexarPorId($this->api->obtenerStockPorBodega('ayora'));

        $stock = [];
        foreach ($dw as $producto) {
            $id = $producto['id'] ?? '';

            $stock[] = [
                'codigo' => trim((string) ($producto['codigo'] ?? '')),
                'producto' => (string) ($producto['nombre'] ?? ''),
                'stock_dw' => $this->formatearCantidad($producto['cantidad_stock'] ?? null),
                'stock_baltra' => isset($baltra[$id]) ? $this->formatearCantidad($baltra[$id]['cantidad_stock'] ?? null) : null,
                'stock_ayora' => isset($ayora[$id]) ? $this->formatearCantidad($ayora[$id]['cantidad_stock'] ?? null) : null,
            ];
        }

        return $stock;
    }

    private function indexarPorId(array $productos): array
    {
        $indexado = [];
        foreach ($productos as $producto) {
            $id = $producto['id'] ?? null;
            if ($id !== null) {
                $indexado[$id] = $producto;
            }
        }

        return $indexado;
    }

    private function formatearCantidad($cantidad): float
    {
        return $cantidad !== null ? (float) $cantidad : 0.0;
    }
}
