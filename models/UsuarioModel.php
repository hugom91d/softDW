<?php

class UsuarioModel
{
    public function __construct()
    {
        $this->asegurarColumnaDebeCambiarContrasena();
    }

    public function listar(): array
    {
        $conn = $this->getConnection();
        $result = $conn->query('SELECT Cedula, Nombre, Rol, Estado FROM usuarios ORDER BY Nombre ASC');
        $usuarios = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

        return is_array($usuarios) ? $usuarios : [];
    }

    public function listarPaginado(int $pagina, int $porPagina, string $rol = ''): array
    {
        $conn = $this->getConnection();
        $pagina = max(1, $pagina);
        $porPagina = max(1, $porPagina);
        $offset = ($pagina - 1) * $porPagina;
        $filtroRol = in_array($rol, ['admin', 'operador'], true) ? $rol : '';
        $where = $filtroRol !== ''
            ? " WHERE LOWER(TRIM(Rol)) = '" . $conn->real_escape_string($filtroRol) . "'"
            : '';

        $totalResult = $conn->query('SELECT COUNT(*) AS total FROM usuarios' . $where);
        $total = $totalResult ? (int) ($totalResult->fetch_assoc()['total'] ?? 0) : 0;
        $result = $conn->query(
            'SELECT Cedula, Nombre, Rol, Estado FROM usuarios' . $where
            . ' ORDER BY Nombre ASC LIMIT ' . $porPagina . ' OFFSET ' . $offset
        );
        $usuarios = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

        return [
            'items' => is_array($usuarios) ? $usuarios : [],
            'total' => $total,
            'total_paginas' => (int) ceil($total / $porPagina),
        ];
    }

    public function buscarPorCedula(string $cedula): ?array
    {
        $conn = $this->getConnection();
        $stmt = $conn->prepare('SELECT Cedula, Nombre, Rol, Estado FROM usuarios WHERE Cedula = ? LIMIT 1');
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param('s', $cedula);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $usuario = $resultado ? $resultado->fetch_assoc() : null;
        $stmt->close();

        return $usuario ?: null;
    }

    public function cedulaExiste(string $cedula): bool
    {
        $conn = $this->getConnection();
        $stmt = $conn->prepare('SELECT 1 FROM usuarios WHERE Cedula = ? LIMIT 1');
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('s', $cedula);
        $stmt->execute();
        $stmt->store_result();
        $existe = $stmt->num_rows > 0;
        $stmt->close();

        return $existe;
    }

    public function crear(string $cedula, string $nombre, string $contrasena, string $rol, int $estado): bool
    {
        $hash = password_hash($contrasena, PASSWORD_DEFAULT);

        $conn = $this->getConnection();
        $stmt = $conn->prepare('INSERT INTO usuarios (Cedula, Nombre, Rol, Contrasena, Estado, DebeCambiarContrasena) VALUES (?, ?, ?, ?, ?, 1)');
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('ssssi', $cedula, $nombre, $rol, $hash, $estado);
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }

    public function actualizar(string $cedula, string $nombre, string $rol, int $estado): bool
    {
        $conn = $this->getConnection();
        $stmt = $conn->prepare('UPDATE usuarios SET Nombre = ?, Rol = ?, Estado = ? WHERE Cedula = ?');
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('ssis', $nombre, $rol, $estado, $cedula);
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }

    public function actualizarEstado(string $cedula, int $estado): bool
    {
        $conn = $this->getConnection();
        $stmt = $conn->prepare('UPDATE usuarios SET Estado = ? WHERE Cedula = ?');
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('is', $estado, $cedula);
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }

    public function actualizarContrasena(string $cedula, string $contrasena): bool
    {
        $hash = password_hash($contrasena, PASSWORD_DEFAULT);

        $conn = $this->getConnection();
        $stmt = $conn->prepare('UPDATE usuarios SET Contrasena = ?, DebeCambiarContrasena = 0 WHERE Cedula = ?');
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('ss', $hash, $cedula);
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }

    public function debeCambiarContrasena(string $cedula): bool
    {
        $conn = $this->getConnection();
        $stmt = $conn->prepare('SELECT DebeCambiarContrasena FROM usuarios WHERE Cedula = ? LIMIT 1');
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('s', $cedula);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $valor = $resultado && $resultado->num_rows > 0 ? $resultado->fetch_assoc()['DebeCambiarContrasena'] ?? 0 : 0;
        $stmt->close();

        return (int) $valor === 1;
    }

    private function asegurarColumnaDebeCambiarContrasena(): void
    {
        $conn = $this->getConnection();
        $resultado = $conn->query("SHOW COLUMNS FROM usuarios LIKE 'DebeCambiarContrasena'");

        if ($resultado && $resultado->num_rows === 0) {
            $conn->query("ALTER TABLE usuarios ADD COLUMN DebeCambiarContrasena TINYINT(1) NOT NULL DEFAULT 1 AFTER Contrasena");
        }
    }

    private function getConnection(): mysqli
    {
        require __DIR__ . '/../config/database.php';
        return $conn;
    }
}
