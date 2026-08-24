<?php
http_response_code(403);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso denegado</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #0b0b0b;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            color: #fff;
            padding: 20px;
        }
        .card {
            width: 100%;
            max-width: 480px;
            background: #161616;
            border: 1px solid #ff4d4d;
            border-radius: 18px;
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.35);
            padding: 32px 26px;
            text-align: center;
        }
        .icon {
            font-size: 52px;
            margin-bottom: 12px;
        }
        h1 {
            margin: 0 0 12px;
            color: #ff9d9d;
            font-size: 2rem;
        }
        p {
            margin: 0;
            color: #f1f5f9;
            line-height: 1.6;
        }
        .badge {
            display: inline-block;
            margin-top: 18px;
            padding: 10px 18px;
            border-radius: 10px;
            background: rgba(255, 77, 77, 0.12);
            border: 1px solid #ff4d4d;
            color: #ffb7b7;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">⛔</div>
        <h1>Acceso denegado</h1>
        <p>No tienes permisos para ingresar desde esta dirección IP.</p>
        <div class="badge">403 Forbidden</div>
    </div>
</body>
</html>
