<?php

class HttpClient
{
    public function get(string $url, array $params = [], array $headers = []): array
    {
        $curl = curl_init();

        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_HTTPHEADER => $headers
        ]);

        $response = curl_exec($curl);

        if (curl_errno($curl)) {
            throw new Exception(curl_error($curl));
        }

        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if (function_exists('curl_close')) {
            @curl_close($curl);
        }

        return [
            'http_code' => $httpCode,
            'data' => json_decode($response, true)
        ];
    }

    /**
     * Ejecuta varias peticiones GET en paralelo usando curl_multi.
     * $requests: array asociativo [clave => ['url' => ..., 'params' => [...], 'headers' => [...]]]
     * Devuelve array asociativo [clave => ['http_code' => ..., 'data' => ...]] preservando las claves de entrada.
     */
    public function getMulti(array $requests): array
    {
        if (empty($requests)) {
            return [];
        }

        $multiHandle = curl_multi_init();
        $handles = [];

        foreach ($requests as $key => $request) {
            $url = $request['url'];
            $params = $request['params'] ?? [];
            $headers = $request['headers'] ?? [];

            if (!empty($params)) {
                $url .= '?' . http_build_query($params);
            }

            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_HTTPHEADER => $headers
            ]);

            curl_multi_add_handle($multiHandle, $curl);
            $handles[$key] = $curl;
        }

        $running = null;
        do {
            curl_multi_exec($multiHandle, $running);
            curl_multi_select($multiHandle);
        } while ($running > 0);

        $resultados = [];
        foreach ($handles as $key => $curl) {
            $response = curl_multi_getcontent($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $error = curl_error($curl);

            $resultados[$key] = [
                'http_code' => $httpCode,
                'data' => $error === '' ? json_decode($response, true) : null,
                'error' => $error !== '' ? $error : null,
            ];

            curl_multi_remove_handle($multiHandle, $curl);
            curl_close($curl);
        }

        curl_multi_close($multiHandle);

        return $resultados;
    }

    public function post(string $url, string $body, array $headers = [])
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_HTTPHEADER => $headers
        ]);

        $response = curl_exec($curl);

        curl_close($curl);

        return json_decode($response, true);
    }
}