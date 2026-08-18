<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['cedula'])) {
    header('Location: ../public/login.php');
    exit;
}

if (($_SESSION['rol'] ?? '') !== 'admin') {
    header('Location: ../views/dashboard.php');
    exit;
}

require_once __DIR__ . '/../models/UsuarioModel.php';

$model = new UsuarioModel();
$mensaje = '';
$tipoMensaje = '';

if (empty($_SESSION['usuarios_csrf'])) {
    $_SESSION['usuarios_csrf'] = bin2hex(random_bytes(32));
}

$rolesPermitidos = ['admin', 'operador'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = (string) ($_POST['csrf_token'] ?? '');
    $accion = (string) ($_POST['accion'] ?? '');

    if (!hash_equals($_SESSION['usuarios_csrf'], $token)) {
        $mensaje = 'La solicitud no es válida. Recarga la página e inténtalo nuevamente.';
        $tipoMensaje = 'error';
    } elseif ($accion === 'crear') {
        $cedula = trim((string) ($_POST['cedula'] ?? ''));
        $nombre = trim((string) ($_POST['nombre'] ?? ''));
        $rol = (string) ($_POST['rol'] ?? 'operador');
        $contrasena = (string) ($_POST['contrasena'] ?? '');
        $confirmacion = (string) ($_POST['confirmacion_contrasena'] ?? '');

        if ($cedula === '' || $nombre === '' || $contrasena === '') {
            $mensaje = 'Cédula, nombre y contraseña son obligatorios.';
            $tipoMensaje = 'error';
        } elseif (!ctype_digit($cedula) || strlen($cedula) > 13) {
            $mensaje = 'La cédula debe contener solo números (máximo 13 dígitos).';
            $tipoMensaje = 'error';
        } elseif (!in_array($rol, $rolesPermitidos, true)) {
            $mensaje = 'El rol seleccionado no es válido.';
            $tipoMensaje = 'error';
        } elseif (strlen($contrasena) < 8) {
            $mensaje = 'La contraseña debe tener al menos 8 caracteres.';
            $tipoMensaje = 'error';
        } elseif ($contrasena !== $confirmacion) {
            $mensaje = 'La confirmación de contraseña no coincide.';
            $tipoMensaje = 'error';
        } elseif ($model->cedulaExiste($cedula)) {
            $mensaje = 'Ya existe un usuario registrado con esa cédula.';
            $tipoMensaje = 'error';
        } elseif ($model->crear($cedula, $nombre, $contrasena, $rol, 1)) {
            $mensaje = 'Usuario creado correctamente.';
            $tipoMensaje = 'success';
        } else {
            $mensaje = 'No se pudo crear el usuario.';
            $tipoMensaje = 'error';
        }
    } elseif ($accion === 'editar') {
        $cedula = trim((string) ($_POST['cedula'] ?? ''));
        $nombre = trim((string) ($_POST['nombre'] ?? ''));
        $rol = (string) ($_POST['rol'] ?? 'operador');
        $estado = (int) ($_POST['estado'] ?? 1);
        $contrasenaNueva = (string) ($_POST['contrasena_nueva'] ?? '');

        if ($nombre === '' || !$model->buscarPorCedula($cedula)) {
            $mensaje = 'El usuario indicado no existe o el nombre es inválido.';
            $tipoMensaje = 'error';
        } elseif (!in_array($rol, $rolesPermitidos, true)) {
            $mensaje = 'El rol seleccionado no es válido.';
            $tipoMensaje = 'error';
        } elseif ($contrasenaNueva !== '' && strlen($contrasenaNueva) < 8) {
            $mensaje = 'La nueva contraseña debe tener al menos 8 caracteres.';
            $tipoMensaje = 'error';
        } elseif ($cedula === (string) $_SESSION['cedula'] && $estado === 0) {
            $mensaje = 'No puedes desactivar tu propio usuario.';
            $tipoMensaje = 'error';
        } else {
            $actualizado = $model->actualizar($cedula, $nombre, $rol, $estado);
            if ($actualizado && $contrasenaNueva !== '') {
                $actualizado = $model->actualizarContrasena($cedula, $contrasenaNueva);
            }

            if ($actualizado) {
                $mensaje = 'Usuario actualizado correctamente.';
                $tipoMensaje = 'success';
                if ($cedula === (string) $_SESSION['cedula']) {
                    $_SESSION['nombre'] = $nombre;
                    $_SESSION['rol'] = $rol;
                }
            } else {
                $mensaje = 'No se pudo actualizar el usuario.';
                $tipoMensaje = 'error';
            }
        }
    }
}

$usuarios = $model->listar();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración de Usuarios</title>
    <link rel="icon" type="image/png" href="https://sp-ao.shortpixel.ai/client/to_webp,q_glossy,ret_img,w_32,h_32/https://darwinandwolf.com/wp-content/uploads/2025/12/Fav-50x50.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../public/css/site.css">
    <link rel="stylesheet" href="../public/css/layout.css">
    <link rel="stylesheet" href="../public/css/sidebar.css">
    <style>
        .usuarios-message {
            padding: 0.8rem 1rem;
            margin-bottom: 1rem;
            border-radius: 0.6rem;
            font-weight: 600;
        }

        .usuarios-message.success {
            background: #dcfce7;
            color: #166534;
        }

        .usuarios-message.error {
            background: #fee2e2;
            color: #991b1b;
        }

        .usuarios-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
        }

        .usuarios-field.full-width {
            grid-column: 1 / -1;
        }

        .badge {
            display: inline-block;
            padding: 0.25rem 0.6rem;
            border-radius: 999px;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .badge-admin {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .badge-operador {
            background: #e5e7eb;
            color: #374151;
        }

        .badge-activo {
            background: #dcfce7;
            color: #166534;
        }

        .badge-inactivo {
            background: #fee2e2;
            color: #991b1b;
        }

        .acciones {
            display: flex;
            gap: 0.5rem;
            justify-content: flex-start;
        }

        .btn-editar {
            width: auto;
            padding: 0.5rem 0.9rem;
            background: #0d6efd;
        }

        .btn-editar:hover {
            background: #000000;
        }

        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.4);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            padding: 1rem;
        }

        .modal-overlay.show {
            display: flex;
        }

        .modal-content {
            width: 100%;
            max-width: 520px;
            background: #fff;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .btn-close-modal {
            width: 40px;
            height: 40px;
            border: none;
            background: #dc2626;
            cursor: pointer;
            color: #ffffff;
            border-radius: 10px;
        }

        .btn-close-modal:hover {
            background: #000000;
        }

        @media (max-width: 600px) {
            .usuarios-grid {
                grid-template-columns: 1fr;
            }

            .usuarios-field.full-width {
                grid-column: auto;
            }
        }
    </style>
</head>

<body>
    <?php require_once __DIR__ . '/layouts/menu.php'; ?>
    <div class="page">
        <header>
            <h1>Administración de Usuarios</h1>
            <p class="subtitle">Crea y gestiona los accesos al sistema.</p>
        </header>

        <?php if ($mensaje !== ''): ?>
            <div class="usuarios-message <?= htmlspecialchars($tipoMensaje) ?>" role="status">
                <?= htmlspecialchars($mensaje) ?>
            </div>
        <?php endif; ?>

        <section class="card">
            <h2 class="profile-section-title" style="margin-top:0;">Crear nuevo acceso</h2>
            <form method="POST" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['usuarios_csrf']) ?>">
                <input type="hidden" name="accion" value="crear">

                <div class="usuarios-grid">
                    <div class="usuarios-field">
                        <label for="cedula">Cédula</label>
                        <input type="text" id="cedula" name="cedula" maxlength="13" pattern="\d+" required>
                    </div>

                    <div class="usuarios-field">
                        <label for="nombre">Nombre</label>
                        <input type="text" id="nombre" name="nombre" maxlength="250" required>
                    </div>

                    <div class="usuarios-field">
                        <label for="rol">Rol</label>
                        <select id="rol" name="rol" required>
                            <option value="operador">Operador</option>
                            <option value="admin">Administrador</option>
                        </select>
                    </div>

                    <div class="usuarios-field">
                        <label for="contrasena">Contraseña</label>
                        <input type="password" id="contrasena" name="contrasena" autocomplete="new-password" required>
                    </div>

                    <div class="usuarios-field full-width">
                        <label for="confirmacion_contrasena">Confirmar contraseña</label>
                        <input type="password" id="confirmacion_contrasena" name="confirmacion_contrasena" autocomplete="new-password" required>
                    </div>
                </div>

                <div class="button-group">
                    <button type="submit">Crear usuario</button>
                </div>
            </form>
        </section>

        <section class="card">
            <h2 class="profile-section-title" style="margin-top:0;">Usuarios registrados</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Cédula</th>
                            <th>Nombre</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($usuarios)): ?>
                            <tr>
                                <td colspan="5" class="empty-state">No hay usuarios registrados.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($usuarios as $usuario): ?>
                                <tr>
                                    <td><?= htmlspecialchars($usuario['Cedula']) ?></td>
                                    <td><?= htmlspecialchars($usuario['Nombre']) ?></td>
                                    <td>
                                        <span class="badge badge-<?= htmlspecialchars($usuario['Rol']) ?>">
                                            <?= $usuario['Rol'] === 'admin' ? 'Administrador' : 'Operador' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?= $usuario['Estado'] == 1 ? 'badge-activo' : 'badge-inactivo' ?>">
                                            <?= $usuario['Estado'] == 1 ? 'Activo' : 'Inactivo' ?>
                                        </span>
                                    </td>
                                    <td class="acciones">
                                        <button type="button" class="btn-editar btn-editar-usuario"
                                            data-cedula="<?= htmlspecialchars($usuario['Cedula']) ?>"
                                            data-nombre="<?= htmlspecialchars($usuario['Nombre']) ?>"
                                            data-rol="<?= htmlspecialchars($usuario['Rol']) ?>"
                                            data-estado="<?= (int) $usuario['Estado'] ?>">
                                            <i class="fa-solid fa-pen"></i> Editar
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <div class="modal-overlay" id="editarModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Editar usuario</h2>
                <button type="button" class="btn-close-modal" id="cerrarEditarModal"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <form method="POST" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['usuarios_csrf']) ?>">
                <input type="hidden" name="accion" value="editar">
                <input type="hidden" name="cedula" id="edit_cedula">

                <div class="usuarios-field">
                    <label for="edit_cedula_display">Cédula</label>
                    <input class="profile-readonly" type="text" id="edit_cedula_display" readonly>
                </div>

                <div class="usuarios-field">
                    <label for="edit_nombre">Nombre</label>
                    <input type="text" id="edit_nombre" name="nombre" maxlength="250" required>
                </div>

                <div class="usuarios-field">
                    <label for="edit_rol">Rol</label>
                    <select id="edit_rol" name="rol" required>
                        <option value="operador">Operador</option>
                        <option value="admin">Administrador</option>
                    </select>
                </div>

                <div class="usuarios-field">
                    <label for="edit_estado">Estado</label>
                    <select id="edit_estado" name="estado" required>
                        <option value="1">Activo</option>
                        <option value="0">Inactivo</option>
                    </select>
                </div>

                <div class="usuarios-field">
                    <label for="edit_contrasena_nueva">Nueva contraseña (opcional)</label>
                    <input type="password" id="edit_contrasena_nueva" name="contrasena_nueva" autocomplete="new-password">
                </div>

                <div class="button-group">
                    <button type="submit">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../public/js/menu.js"></script>
    <script>
        const editarModal = document.getElementById('editarModal');

        document.querySelectorAll('.btn-editar-usuario').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.getElementById('edit_cedula').value = this.dataset.cedula;
                document.getElementById('edit_cedula_display').value = this.dataset.cedula;
                document.getElementById('edit_nombre').value = this.dataset.nombre;
                document.getElementById('edit_rol').value = this.dataset.rol;
                document.getElementById('edit_estado').value = this.dataset.estado;
                document.getElementById('edit_contrasena_nueva').value = '';
                editarModal.classList.add('show');
            });
        });

        document.getElementById('cerrarEditarModal').addEventListener('click', function () {
            editarModal.classList.remove('show');
        });

        editarModal.addEventListener('click', function (event) {
            if (event.target === editarModal) {
                editarModal.classList.remove('show');
            }
        });
    </script>
</body>

</html>
