<?php

$token = "D9C8xJTou25JSdBA8GVe92h9Ni7AwIBbLTVaxvEGlhQ";

// $url = "https://api.contifico.com/sistema/api/v1/producto/";
// $url = "https://api.contifico.com/sistema/api/v2/documento/?fecha_emision=17/07/2026";

$curl = curl_init();

curl_setopt_array($curl, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_HTTPHEADER => [
        "Authorization: $token",
        "Accept: application/json"
    ]
]);

$response = curl_exec($curl);

if (curl_errno($curl)) {
    die("Error cURL: " . curl_error($curl));
}

$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

curl_close($curl);

echo "<h3>HTTP CODE: $httpCode</h3>";
echo "<pre>";
print_r(json_decode($response, true));
echo "</pre>";

?>