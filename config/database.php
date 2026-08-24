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
