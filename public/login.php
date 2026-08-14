<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="https://sp-ao.shortpixel.ai/client/to_webp,q_glossy,ret_img,w_32,h_32/https://darwinandwolf.com/wp-content/uploads/2025/12/Fav-50x50.png">
    <title>Login Básico</title>
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

        #mensaje {
            margin-top: 15px;
            text-align: center;
            color: #ff6b6b;
        }
    </style>
</head>

<body>
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

        <p id="mensaje"></p>
    </div>

</body>

</html>