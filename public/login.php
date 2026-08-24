<?php
$error = isset($_GET['error']) && $_GET['error'] === '1';
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