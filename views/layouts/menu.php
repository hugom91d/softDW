<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<header class="topbar">
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
            <a href="#">
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
        <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a></li>
        <li><a href="../views/botones.php"><i class="fas fa-sync-alt"></i><span>Sincronizar</span></a></li>
        <li><a href="../views/facturas.php"><i class="fas fa-file-invoice"></i><span>Facturas</span></a></li>
        <li><a href="inventario.php"><i class="fas fa-boxes-stacked"></i><span>Inventario</span></a></li>
        <li><a href="../views/reposiciones.php"><i class="fas fa-rotate"></i><span>Reposiciones</span></a></li>
        <li><a href="reportes.php"><i class="fas fa-chart-column"></i><span>Reportes</span></a></li>
    </ul>
    <div class="sidebar-footer">
        <button class="toggle-btn toggle-btn-bottom" onclick="toggleMenu()"><i id="arrowIcon" class="fas fa-angle-left"></i></button>
    </div>
</div>
