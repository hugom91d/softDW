<nav class="configuracion-tabs" aria-label="Secciones de configuración">
    <a class="<?= ($configuracionActiva ?? '') === 'usuarios' ? 'active' : '' ?>" href="usuarios.php">
        <i class="fas fa-users-cog"></i>
        <span>Usuarios</span>
    </a>
    <a class="<?= ($configuracionActiva ?? '') === 'stock' ? 'active' : '' ?>" href="configuracion_stock.php">
        <i class="fas fa-gear"></i>
        <span>Stock</span>
    </a>
    <a class="<?= ($configuracionActiva ?? '') === 'sincronizacion' ? 'active' : '' ?>" href="configuracion_sincronizacion.php">
        <i class="fas fa-rotate"></i>
        <span>Sincronización</span>
    </a>
    <a class="<?= ($configuracionActiva ?? '') === 'no_repuesto' ? 'active' : '' ?>" href="configuracion_no_repuesto.php">
        <i class="fas fa-list-check"></i>
        <span>No repuesto</span>
    </a>
</nav>