<?php
session_start();
require_once '../config/database.php';

$cedula = trim($_POST['cedula'] ?? '');
$password = $_POST['password'] ?? '';

$sql = 'SELECT Cedula, Nombre, Rol, Estado, Contrasena FROM usuarios WHERE Cedula = ?';
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $cedula);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows === 1) {
    $usuario = $resultado->fetch_assoc();

    if ($usuario['Estado'] == 1 && password_verify($password, $usuario['Contrasena'])) {
        $_SESSION['cedula'] = $usuario['Cedula'];
        $_SESSION['nombre'] = $usuario['Nombre'];
        $_SESSION['rol'] = $usuario['Rol'];

        header('Location: ../views/facturas.php');
        exit;
    }
}

echo 'Usuario o contraseña incorrectos.';
