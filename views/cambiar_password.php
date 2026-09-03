<?php
require_once __DIR__ . '/../config/session.php';

if (!isset($_SESSION['cedula'])) {
    header('Location: ../public/login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$mensaje = '';
$tipoMensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = (string) ($_POST['csrf_token'] ?? '');
    $nueva = (string) ($_POST['nueva_password'] ?? '');
    $confirmacion = (string) ($_POST['confirmacion_password'] ?? '');

    if (!isset($_SESSION['perfil_csrf'])) {
        $_SESSION['perfil_csrf'] = bin2hex(random_bytes(32));
    }

    if (!hash_equals($_SESSION['perfil_csrf'], $token)) {
        $mensaje = 'La solicitud no es válida. Recarga la página e inténtalo nuevamente.';
        $tipoMensaje = 'error';
    } elseif ($nueva === '' || $confirmacion === '') {
        $mensaje = 'Debes ingresar la nueva contraseña y confirmarla.';
        $tipoMensaje = 'error';
    } elseif (strlen($nueva) < 8) {
        $mensaje = 'La contraseña debe tener al menos 8 caracteres.';
        $tipoMensaje = 'error';
    } elseif ($nueva !== $confirmacion) {
        $mensaje = 'La confirmación de contraseña no coincide.';
        $tipoMensaje = 'error';
    } else {
        $hash = password_hash($nueva, PASSWORD_DEFAULT);
        $stmt = $conn->prepare('UPDATE usuarios SET Contrasena = ?, DebeCambiarContrasena = 0 WHERE Cedula = ?');

        if ($stmt) {
            $stmt->bind_param('ss', $hash, $_SESSION['cedula']);
            $ok = $stmt->execute();
            $stmt->close();

            if ($ok) {
                $_SESSION['fuerza_cambio_password'] = false;
                $_SESSION['perfil_csrf'] = bin2hex(random_bytes(32));
                header('Location: ../views/facturas.php');
                exit;
            }
        }

        $mensaje = 'No se pudo actualizar la contraseña. Inténtalo nuevamente.';
        $tipoMensaje = 'error';
    }
}

if (empty($_SESSION['perfil_csrf'])) {
    $_SESSION['perfil_csrf'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar contraseña</title>
    <link rel="icon" type="image/png" href="https://sp-ao.shortpixel.ai/client/to_webp,q_glossy,ret_img,w_32,h_32/https://darwinandwolf.com/wp-content/uploads/2025/12/Fav-50x50.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../public/css/site.css">
    <link rel="stylesheet" href="../public/css/layout.css">
    <link rel="stylesheet" href="../public/css/sidebar.css">
    <link rel="stylesheet" href="../public/css/perfil.css">
</head>
<body>
    <div class="page">
        <header>
            <h1>Cambiar contraseña</h1>
            <p class="subtitle">Debes actualizar tu contraseña antes de continuar.</p>
        </header>

        <section class="card profile-card">
            <?php if ($mensaje !== ''): ?>
                <div class="profile-message <?= htmlspecialchars($tipoMensaje) ?>" role="status">
                    <?= htmlspecialchars($mensaje) ?>
                </div>
            <?php endif; ?>

            <div class="profile-message warning" role="status">
                Este es tu primer acceso. Debes cambiar tu contraseña para continuar usando el sistema.
            </div>

            <form method="POST" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['perfil_csrf']) ?>">

                <div class="profile-grid">
                    <div class="profile-field full-width password-field">
                        <label for="nueva_password">Nueva contraseña</label>
                        <input type="password" id="nueva_password" name="nueva_password" autocomplete="new-password" required>
                        <button type="button" class="password-toggle" data-target="nueva_password" aria-label="Mostrar nueva contraseña"><i class="fa-solid fa-eye"></i></button>
                    </div>

                    <div class="profile-field full-width password-field">
                        <label for="confirmacion_password">Confirmar contraseña</label>
                        <input type="password" id="confirmacion_password" name="confirmacion_password" autocomplete="new-password" required>
                        <button type="button" class="password-toggle" data-target="confirmacion_password" aria-label="Mostrar confirmación de contraseña"><i class="fa-solid fa-eye"></i></button>
                    </div>
                </div>

                <div class="button-group">
                    <button type="submit">Guardar nueva contraseña</button>
                </div>
            </form>
        </section>
    </div>

    <script src="../public/js/perfil.js"></script>
</body>
</html>
