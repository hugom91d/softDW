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
    <style>
        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: #f4f5f7;
            color: #fff;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            box-sizing: border-box;
            padding: 16px;
        }

        *,
        *::before,
        *::after {
            box-sizing: inherit;
        }

        .card {
            width: 100%;
            max-width: 420px;
            background: #0b0b0b;
            border-radius: 18px;
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.25);
            padding: 32px;
        }

        @media (max-width: 480px) {
            .card {
                padding: 24px 18px;
                border-radius: 14px;
            }
        }

        .login-logo {
            display: block;
            width: min(220px, 80%);
            height: auto;
            margin: 0 auto 22px;
        }

        .subtitle {
            text-align: center;
            color: #d1d5db;
            margin: 0 0 25px;
        }

        h2 {
            margin-top: 0;
            text-align: center;
            color: #fff;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            font-size: 0.95rem;
            color: #fff;
            margin-bottom: 8px;
        }

        input {
            width: 100%;
            box-sizing: border-box;
            padding: 12px 14px;
            border: 1px solid #cfd3d8;
            border-radius: 10px;
            font-size: 0.95rem;
            background: #fff;
            color: #111827;
        }

        input:focus {
            outline: none;
            border-color: #e60000;
            box-shadow: 0 0 0 3px rgba(230, 0, 0, 0.2);
        }

        button {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 10px;
            background: #e60000;
            color: #fff;
            font-size: 1rem;
            font-weight: 800;
            cursor: pointer;
            transition: background 0.2s ease;
        }

        button:hover {
            background: #b80000;
            color: #fff;
        }

        .modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.55);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 999;
        }

        .modal {
            width: min(92vw, 360px);
            background: #1a1a1a;
            border: 1px solid #ff6b6b;
            border-radius: 16px;
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.4);
            padding: 24px 20px 18px;
            text-align: center;
            color: #fff;
        }

        .modal-icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .modal-title {
            margin: 0 0 8px;
            color: #ffb4b4;
            font-size: 1.2rem;
            font-weight: 700;
        }

        .modal-message {
            margin: 0 0 18px;
            color: #f3f4f6;
            line-height: 1.5;
        }

        .modal-button {
            border: none;
            background: #e60000;
            color: #fff;
            padding: 10px 18px;
            border-radius: 10px;
            font-weight: 700;
            cursor: pointer;
        }

        .modal-button:hover {
            background: #b80000;
        }
    </style>
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