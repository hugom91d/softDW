<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<header class="topbar">
    <button type="button" class="mobile-menu-button" id="mobileMenuButton" aria-label="Abrir menú" aria-controls="sidebar" aria-expanded="false">
        <i class="fa-solid fa-bars"></i>
    </button>
    <div class="logo">
        <img src="../public/img/logo.png" alt="Logo">
    </div>

    <div class="user-menu">
        <button type="button" class="user-button" id="userButton">
            <i class="fa-solid fa-user"></i>
        </button>

        <div class="dropdown-menu" id="dropdownMenu">
            <div class="dropdown-user" style="color: #222;">
                <?= htmlspecialchars($_SESSION['nombre'] ?? 'Usuario') ?>
            </div>
            <a href="../views/perfil.php">
                <i class="fa-solid fa-id-badge"></i>
                <span>Mi Perfil</span>
            </a>
            <a href="../public/logout.php">
                <i class="fa-solid fa-right-from-bracket"></i>
                <span>Cerrar Sesión</span>
            </a>
        </div>
    </div>
</header>
<div class="sidebar" id="sidebar">
    <ul>
        <?php if (($_SESSION['rol'] ?? '') !== 'operador'): ?>
        <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a></li>
        <?php endif; ?>
        <li><a href="../views/botones.php"><i class="fas fa-sync-alt"></i><span>Sincronizar</span></a></li>
        <?php if (($_SESSION['rol'] ?? '') !== 'operador'): ?>
        <li><a href="../views/facturas.php"><i class="fas fa-file-invoice"></i><span>Facturas</span></a></li>
        <?php endif; ?>
        <li><a href="../views/reposiciones.php"><i class="fas fa-right-left"></i><span>Reposiciones</span></a></li>
        <?php if (($_SESSION['rol'] ?? '') === 'admin'): ?>
        <li><a href="../views/usuarios.php"><i class="fas fa-users-cog"></i><span>Usuarios</span></a></li>
        <?php endif; ?>
    </ul>
    <div class="sidebar-footer">
        <button class="toggle-btn toggle-btn-bottom" onclick="toggleMenu()"><i id="arrowIcon" class="fas fa-angle-left"></i></button>
    </div>
</div>
