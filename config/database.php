<?php

$host = "localhost";
$usuario = "dwadmin";
$password = "DWadmin2026";
$baseDatos = "darwinandwolf";

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
