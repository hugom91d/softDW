<?php

date_default_timezone_set('America/Guayaquil');

$host = "157.90.5.84";
$usuario = "darwinan_gps";
$password = "lvh38KjM5lzXcrV)";
$baseDatos = "darwinan_gpsGestion";

$conn = new mysqli(
    $host,
    $usuario,
    $password,
    $baseDatos
);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

$resultado = $conn->query("SHOW COLUMNS FROM usuarios LIKE 'DebeCambiarContrasena'");
if ($resultado && $resultado->num_rows === 0) {
    $conn->query("ALTER TABLE usuarios ADD COLUMN DebeCambiarContrasena TINYINT(1) NOT NULL DEFAULT 1 AFTER Contrasena");
}

$camposProductos = [
    'stock_uio' => 'DECIMAL(10,2) NULL DEFAULT NULL',
    'stock_baltra' => 'DECIMAL(10,2) NULL DEFAULT NULL',
    'stock_puerto_ayora' => 'DECIMAL(10,2) NULL DEFAULT NULL',
];

foreach ($camposProductos as $campo => $tipo) {
    $resultadoCampo = $conn->query("SHOW COLUMNS FROM productos LIKE '$campo'");
    if ($resultadoCampo && $resultadoCampo->num_rows === 0) {
        $conn->query("ALTER TABLE productos ADD COLUMN $campo $tipo");
    }
}
