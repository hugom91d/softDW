<?php
session_start();
if (!isset($_SESSION['cedula'])) {
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Botones de Acción</title>
    <link rel="icon" type="image/png" href="https://sp-ao.shortpixel.ai/client/to_webp,q_glossy,ret_img,w_32,h_32/https://darwinandwolf.com/wp-content/uploads/2025/12/Fav-50x50.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../public/css/site.css">
    <link rel="stylesheet" href="../public/css/layout.css">
    <link rel="stylesheet" href="../public/css/sidebar.css">
</head>

<body>
    <?php require_once __DIR__ . "/layouts/menu.php"; ?>

    <div class="page">
        <header>
            <h1>Botones de Acción</h1>
            <p class="subtitle">Acceso rápido a funciones principales</p>
        </header>

        <section class="card">
            <div class="sync-actions">
                <div class="sync-quick-actions">
                    <button class="action-button" id="btnFacturas" title="Sincronizar Facturas">
                        <div class="action-icon"><i class="fa-solid fa-file-invoice fa-2x"></i></div>
                        <div class="action-label">Facturas</div>
                    </button>

                </div>
            </div>
        </section>
    </div>

    <script src="../public/js/menu.js"></script>
    <style>
        .sync-quick-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-start;
            align-items: stretch;
            flex-wrap: wrap;
            padding: 1rem 0;
        }

        .action-button {
            width: 150px;
            height: 150px;
            border-radius: 12px;
            border: 1px solid #e6e6e6;
            background: #ffffff;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            cursor: pointer;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.06);
            transition: transform .12s ease, box-shadow .12s ease;
        }

        .action-button:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12);    
        }

        .action-icon {
            width: 64px;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: #f3f4f6;
        }

        .action-label {
            font-weight: 700;
            color: #0f172a;
            text-align: center;
        }

        @media (max-width: 768px) {
            .sync-quick-actions {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 0.75rem;
            }

            .action-button {
                width: 100%;
                height: 130px;
            }

            .action-icon {
                width: 52px;
                height: 52px;
            }
        }

        @media (max-width: 380px) {
            .sync-quick-actions {
                grid-template-columns: 1fr;
            }

            .action-button {
                height: 112px;
            }
        }
    </style>

    <script>    
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('btnFacturas')?.addEventListener('click', function() {
                window.location.href = '../views/sincronizar.php';
            });

        });
    </script>
</body>

</html>