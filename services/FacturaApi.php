<?php

class FacturaApi
{
    private array $config;
    private HttpClient $http;

    public function __construct()
    {
        $apis = require '../config/api.php';

        $this->config = $apis['facturas'];
        $this->http = new HttpClient();
    }

    public function obtenerFacturas(array $filtros = [])
    {
        return $this->http->get(
            $this->config['base_url'],  
            $filtros,
            [
                'Authorization: '.$this->config['token'],
                'Accept: application/json'
            ]
        );
    }
}