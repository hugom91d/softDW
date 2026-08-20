<?php
return [

    'facturas' => [
        'base_url' => 'https://api.contifico.com/sistema/api/v2/documento/',
        //'token' => 'D9C8xJTou25JSdBA8GVe92h9Ni7AwIBbLTVaxvEGlhQ'
        'token' => 'y0pUCh4zeIFCV9aQjpNHEFGOFW9pRqigdEW2kWWh8Sk'
    ],
    'inventario' => [
        'dw' => [
            'base_url' => 'https://api.contifico.com/sistema/api/v1/producto/',
            'token' => 'y0pUCh4zeIFCV9aQjpNHEFGOFW9pRqigdEW2kWWh8Sk'
        ],
        'baltra' => [
            'base_url' => 'https://api.contifico.com/sistema/api/v1/producto/',
            // TODO: agregar token de Contifico de la bodega Baltra
            'token' => ''
        ],
        'ayora' => [
            'base_url' => 'https://api.contifico.com/sistema/api/v1/producto/',
            // TODO: agregar token de Contifico de la bodega Ayora
            'token' => ''
        ]
    ]
];
