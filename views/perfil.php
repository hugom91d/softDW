<?php
require_once __DIR__ . '/../config/session.php';

if (!isset($_SESSION['cedula'])) {
    header('Location: ../public/login.php');
    exit;
}

if (!empty($_SESSION['fuerza_cambio_password'])) {
    header('Location: cambiar_password.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$cedulaSesion = (string) $_SESSION['cedula'];
$mensaje = '';
$tipoMensaje = '';
$forzarCambio = !empty($_SESSION['fuerza_cambio_password']);

if (empty($_SESSION['perfil_csrf'])) {
    $_SESSION['perfil_csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = (string) ($_POST['csrf_token'] ?? '');
    $nombre = trim((string) ($_POST['nombre'] ?? ''));
    $contrasenaActual = (string) ($_POST['contrasena_actual'] ?? '');
    $contrasenaNueva = (string) ($_POST['contrasena_nueva'] ?? '');
    $confirmacion = (string) ($_POST['confirmacion_contrasena'] ?? '');

    if (!hash_equals($_SESSION['perfil_csrf'], $token)) {
        $mensaje = 'La solicitud no es válida. Recarga la página e inténtalo nuevamente.';
        $tipoMensaje = 'error';
    } elseif ($nombre === '') {
        $mensaje = 'El nombre no puede estar vacío.';
        $tipoMensaje = 'error';
    } else {
        $stmt = $conn->prepare('SELECT Nombre, Contrasena FROM usuarios WHERE Cedula = ? AND Estado = 1 LIMIT 1');
        $usuario = null;

        if ($stmt) {
            $stmt->bind_param('s', $cedulaSesion);
            $stmt->execute();
            $resultado = $stmt->get_result();
            $usuario = $resultado ? $resultado->fetch_assoc() : null;
            $stmt->close();
        }

        if (!$usuario) {
            $mensaje = 'No se encontró el usuario activo.';
            $tipoMensaje = 'error';
        } elseif (($contrasenaActual !== '' || $contrasenaNueva !== '' || $confirmacion !== '') && ($contrasenaActual === '' || $contrasenaNueva === '' || $confirmacion === '')) {
            $mensaje = 'Para cambiar la contraseña debes completar los tres campos.';
            $tipoMensaje = 'error';
        } elseif ($contrasenaNueva !== '' && !password_verify($contrasenaActual, $usuario['Contrasena'])) {
            $mensaje = 'La contraseña actual no es correcta.';
            $tipoMensaje = 'error';
        } elseif ($contrasenaNueva !== '' && $contrasenaNueva !== $confirmacion) {
            $mensaje = 'La confirmación de contraseña no coincide.';
            $tipoMensaje = 'error';
        } elseif ($contrasenaNueva !== '' && strlen($contrasenaNueva) < 8) {
            $mensaje = 'La nueva contraseña debe tener al menos 8 caracteres.';
            $tipoMensaje = 'error';
        } else {
            $nombreActualizado = false;
            $contrasenaActualizada = false;

            $stmtNombre = $conn->prepare('UPDATE usuarios SET Nombre = ? WHERE Cedula = ?');
            if ($stmtNombre) {
                $stmtNombre->bind_param('ss', $nombre, $cedulaSesion);
                $nombreActualizado = $stmtNombre->execute();
                $stmtNombre->close();
            }

            if ($contrasenaNueva !== '') {
                $hash = password_hash($contrasenaNueva, PASSWORD_DEFAULT);
                $stmtContrasena = $conn->prepare('UPDATE usuarios SET Contrasena = ?, DebeCambiarContrasena = 0 WHERE Cedula = ?');
                if ($stmtContrasena) {
                    $stmtContrasena->bind_param('ss', $hash, $cedulaSesion);
                    $contrasenaActualizada = $stmtContrasena->execute();
                    $stmtContrasena->close();
                }
            }

            if ($nombreActualizado && ($contrasenaNueva === '' || $contrasenaActualizada)) {
                $_SESSION['nombre'] = $nombre;
                if ($forzarCambio && $contrasenaNueva !== '') {
                    $stmtFinal = $conn->prepare('UPDATE usuarios SET DebeCambiarContrasena = 0 WHERE Cedula = ?');
                    if ($stmtFinal) {
                        $stmtFinal->bind_param('s', $cedulaSesion);
                        $stmtFinal->execute();
                        $stmtFinal->close();
                    }
                    $_SESSION['fuerza_cambio_password'] = false;
                    header('Location: ../views/facturas.php');
                    exit;
                }
                $mensaje = 'Los datos del perfil se actualizaron correctamente.';
                $tipoMensaje = 'success';
            } else {
                $mensaje = 'No se pudieron actualizar todos los datos del perfil.';
                $tipoMensaje = 'error';
            }
        }
    }
}

$stmt = $conn->prepare('SELECT Nombre, Cedula, Estado FROM usuarios WHERE Cedula = ? LIMIT 1');
$usuario = null;
if ($stmt) {
    $stmt->bind_param('s', $cedulaSesion);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $usuario = $resultado ? $resultado->fetch_assoc() : null;
    $stmt->close();
}

if (!$usuario) {
    $usuario = [
        'Nombre' => $_SESSION['nombre'] ?? '',
        'Cedula' => $cedulaSesion,
        'Estado' => 1,
    ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil</title>
    <link rel="icon" type="image/png" href="https://sp-ao.shortpixel.ai/client/to_webp,q_glossy,ret_img,w_32,h_32/https://darwinandwolf.com/wp-content/uploads/2025/12/Fav-50x50.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../public/css/site.css">
    <link rel="stylesheet" href="../public/css/layout.css">
    <link rel="stylesheet" href="../public/css/sidebar.css">
    <link rel="stylesheet" href="../public/css/perfil.css">
</head>
<body>
    <?php require_once __DIR__ . '/layouts/menu.php'; ?>
    <div class="page">
        <header>
            <h1>Mi Perfil</h1>
            <p class="subtitle"><?= $forzarCambio ? 'Debes cambiar tu contraseña antes de continuar.' : 'Consulta y actualiza tus datos personales.' ?></p>
        </header>

        <section class="card profile-card">
            <?php if ($mensaje !== ''): ?>
                <div class="profile-message <?= htmlspecialchars($tipoMensaje) ?>" role="status">
                    <?= htmlspecialchars($mensaje) ?>
                </div>
            <?php endif; ?>

            <?php if ($forzarCambio): ?>
                <div class="profile-message warning" role="status">
                    Este es tu primer acceso. Debes cambiar tu contraseña para continuar usando el sistema.
                </div>
            <?php endif; ?>

            <form method="POST" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['perfil_csrf']) ?>">

                <div class="profile-grid">
                    <div class="profile-field">
                        <label for="nombre">Nombre</label>
                        <input type="text" id="nombre" name="nombre" maxlength="250" value="<?= htmlspecialchars($usuario['Nombre'] ?? '') ?>" required>
                    </div>

                    <div class="profile-field">
                        <label for="cedula">Cédula</label>
                        <input class="profile-readonly" type="text" id="cedula" value="<?= htmlspecialchars($usuario['Cedula'] ?? '') ?>" readonly>
                    </div>
                </div>

                <h2 class="profile-section-title"><?= $forzarCambio ? 'Cambiar contraseña obligatoria' : 'Cambiar contraseña' ?></h2>

                <div class="profile-grid">
                    <div class="profile-field full-width password-field">
                        <label for="contrasena_actual">Contraseña actual</label>
                        <input type="password" id="contrasena_actual" name="contrasena_actual" autocomplete="current-password">
                        <button type="button" class="password-toggle" data-target="contrasena_actual" aria-label="Mostrar contraseña actual"><i class="fa-solid fa-eye"></i></button>
                    </div>

                    <div class="profile-field password-field">
                        <label for="contrasena_nueva">Nueva contraseña</label>
                        <input type="password" id="contrasena_nueva" name="contrasena_nueva" autocomplete="new-password">
                        <button type="button" class="password-toggle" data-target="contrasena_nueva" aria-label="Mostrar nueva contraseña"><i class="fa-solid fa-eye"></i></button>
                    </div>

                    <div class="profile-field password-field">
                        <label for="confirmacion_contrasena">Confirmar contraseña</label>
                        <input type="password" id="confirmacion_contrasena" name="confirmacion_contrasena" autocomplete="new-password">
                        <button type="button" class="password-toggle" data-target="confirmacion_contrasena" aria-label="Mostrar confirmación de contraseña"><i class="fa-solid fa-eye"></i></button>
                    </div>
                </div>

                <div class="button-group">
                    <button type="submit"><?= $forzarCambio ? 'Guardar nueva contraseña' : 'Guardar cambios' ?></button>
                </div>
            </form>
        </section>
    </div>

    <script src="../public/js/menu.js"></script>
    <script src="../public/js/perfil.js"></script>
</body>
</html>
