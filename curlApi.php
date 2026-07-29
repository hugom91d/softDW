<?php

$token = "D9C8xJTou25JSdBA8GVe92h9Ni7AwIBbLTVaxvEGlhQ";

$rawBody = file_get_contents('php://input');
$payload = [];

if (!empty($rawBody)) {
    $decoded = json_decode($rawBody, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $payload = $decoded;
    } else {
        parse_str($rawBody, $payload);
    }
}

$url = $payload['url'] ?? '';

if (empty($url)) {
    http_response_code(400);
    echo json_encode(['error' => 'Se requiere el parámetro POST "url".']);
    exit;
}

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
    http_response_code(500);
    echo json_encode(['error' => 'Error cURL: ' . curl_error($curl)]);
    exit;
}

$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

curl_close($curl);

echo json_encode([
    'http_code' => $httpCode,
    'response' => json_decode($response, true) ?: $response
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
