<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Básico</title>
    <style>
        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: #f4f5f7;
            color: #222;
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
            background: #ffffff;
            border-radius: 18px;
            box-shadow: 0 10px 30px rgba(18, 38, 63, 0.08);
            padding: 32px;
        }

        @media (max-width: 480px) {
            .card {
                padding: 24px 18px;
                border-radius: 14px;
            }
        }

        h2 {
            margin-top: 0;
            text-align: center;
            color: #0f172a;
        }

        .subtitle {
            text-align: center;
            color: #64748b;
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            font-size: 0.95rem;
            color: #444;
            margin-bottom: 8px;
        }

        input {
            width: 100%;
            box-sizing: border-box;
            padding: 12px 14px;
            border: 1px solid #d5d7dc;
            border-radius: 10px;
            font-size: 0.95rem;
        }



        input:focus {
            outline: none;
            border-color: #2563eb;
        }

        button {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 10px;
            background: #2563eb;
            color: white;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.2s ease;
        }

        button:hover {
            background: #1d4ed8;
        }

        #mensaje {
            margin-top: 15px;
            text-align: center;
            color: #dc2626;
        }
    </style>
</head>

<body>
    <div class="card">
        <h2>Iniciar Sesión</h2>
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