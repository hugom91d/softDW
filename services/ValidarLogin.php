<?php
require_once __DIR__ . '/../config/session.php';
require_once '../config/database.php';

if (($_POST['action'] ?? '') === 'change_password') {
    $cedula = (string) ($_SESSION['cedula'] ?? '');
    $nueva = (string) ($_POST['nueva_password'] ?? '');
    $confirmacion = (string) ($_POST['confirmacion_password'] ?? '');

    if ($cedula === '') {
        header('Location: ../public/login.php?error=1');
        exit;
    }

    if ($nueva === '' || $confirmacion === '') {
        $_SESSION['reset_error'] = 'Debes ingresar la nueva contraseña y confirmarla.';
        header('Location: ../public/login.php?force_change=1');
        exit;
    }

    if (strlen($nueva) < 8) {
        $_SESSION['reset_error'] = 'La contraseña debe tener al menos 8 caracteres.';
        header('Location: ../public/login.php?force_change=1');
        exit;
    }

    if ($nueva !== $confirmacion) {
        $_SESSION['reset_error'] = 'La confirmación de contraseña no coincide.';
        header('Location: ../public/login.php?force_change=1');
        exit;
    }

    $hash = password_hash($nueva, PASSWORD_DEFAULT);
    $stmt = $conn->prepare('UPDATE usuarios SET Contrasena = ?, DebeCambiarContrasena = 0 WHERE Cedula = ?');
    if (!$stmt) {
        $_SESSION['reset_error'] = 'No se pudo actualizar la contraseña.';
        header('Location: ../public/login.php?force_change=1');
        exit;
    }

    $stmt->bind_param('ss', $hash, $cedula);
    $ok = $stmt->execute();
    $stmt->close();

    if (!$ok) {
        $_SESSION['reset_error'] = 'No se pudo actualizar la contraseña.';
        header('Location: ../public/login.php?force_change=1');
        exit;
    }

    $_SESSION['fuerza_cambio_password'] = false;
    unset($_SESSION['reset_error']);
    header('Location: ../views/facturas.php');
    exit;
}

$cedula = trim($_POST['cedula'] ?? '');
$password = $_POST['password'] ?? '';

$sql = 'SELECT Cedula, Nombre, Rol, Estado, Contrasena, DebeCambiarContrasena FROM usuarios WHERE Cedula = ?';
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

        $debeCambiar = isset($usuario['DebeCambiarContrasena']) && (int) $usuario['DebeCambiarContrasena'] === 1;
        $_SESSION['fuerza_cambio_password'] = $debeCambiar ? true : false;

        if ($debeCambiar) {
            header('Location: ../public/login.php?force_change=1');
            exit;
        }

        header('Location: ../views/facturas.php');
        exit;
    }
}

header('Location: ../public/login.php?error=1');
exit;
