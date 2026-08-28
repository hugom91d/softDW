<?php
session_start();
$error = isset($_GET['error']) && $_GET['error'] === '1';
$forceChange = isset($_GET['force_change']) && $_GET['force_change'] === '1' && !empty($_SESSION['cedula']) && !empty($_SESSION['fuerza_cambio_password']);
$resetError = $_SESSION['reset_error'] ?? '';
unset($_SESSION['reset_error']);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="https://sp-ao.shortpixel.ai/client/to_webp,q_glossy,ret_img,w_32,h_32/https://darwinandwolf.com/wp-content/uploads/2025/12/Fav-50x50.png">
    <title>Login GPS - DW</title>
    <link rel="stylesheet" href="../public/css/login.css">
</head>

<body>
    <?php if ($error): ?>
        <div class="modal-backdrop" id="errorModal">
            <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
                <div class="modal-icon">⚠️</div>
                <h3 class="modal-title" id="modalTitle">Error de acceso</h3>
                <p class="modal-message">Usuario o contraseña incorrectos.</p>
                <button type="button" class="modal-button" onclick="document.getElementById('errorModal').style.display='none';">Cerrar</button>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($forceChange): ?>
        <div class="modal-backdrop" id="forcePasswordModal">
            <div class="modal modal-password" role="dialog" aria-modal="true" aria-labelledby="forcePasswordTitle">
                <div class="modal-icon">🔐</div>
                <h3 class="modal-title" id="forcePasswordTitle">Cambio obligatorio</h3>
                <p class="modal-message">Debes actualizar tu contraseña antes de continuar.</p>

                <?php if ($resetError !== ''): ?>
                    <p class="modal-error"><?= htmlspecialchars($resetError) ?></p>
                <?php endif; ?>

                <form action="../services/ValidarLogin.php" method="POST" autocomplete="off">
                    <input type="hidden" name="action" value="change_password">
                    <div class="form-group modal-form-group">
                        <label for="nueva_password_modal">Nueva contraseña:</label>
                        <input type="password" id="nueva_password_modal" name="nueva_password" required>
                    </div>
                    <div class="form-group modal-form-group">
                        <label for="confirmacion_password_modal">Confirmar contraseña:</label>
                        <input type="password" id="confirmacion_password_modal" name="confirmacion_password" required>
                    </div>
                    <button type="submit" class="modal-button">Guardar y entrar</button>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <div class="card">
        <img class="login-logo" src="../public/img/logo.png" alt="Darwin and Wolf">
        <p class="subtitle">Ingrese sus credenciales para continuar</p>

        <form id="loginForm" action="../services/ValidarLogin.php" method="POST">
            <div class="form-group">
                <label for="cedula">Cédula:</label><br>
                <input type="text" id="cedula" name="cedula" required>
            </div>

            <br>

            <div class="form-group">
                <label for="password">Contraseña:</label><br>
                <input type="password" id="password" name="password" required>
            </div>

            <br>

            <button type="submit">Ingresar</button>
        </form>
    </div>
</body>

</html>