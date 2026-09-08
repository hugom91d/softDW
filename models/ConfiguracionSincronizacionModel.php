<?php

class ConfiguracionSincronizacionModel
{
    private const VALORES_PREDETERMINADOS = [
        'limite_codigos' => 0,
        'limite_stock' => 5,
    ];

    public function obtener(): array
    {
        $conn = $this->getConnection();
        $result = $conn->query('SELECT limite_codigos, limite_stock FROM configuracion_sincronizacion WHERE id = 1');
        $configuracion = $result ? $result->fetch_assoc() : null;

        if (!$configuracion) {
            return self::VALORES_PREDETERMINADOS;
        }

        return [
            'limite_codigos' => (int) ($configuracion['limite_codigos'] ?? self::VALORES_PREDETERMINADOS['limite_codigos']),
            'limite_stock' => (int) ($configuracion['limite_stock'] ?? self::VALORES_PREDETERMINADOS['limite_stock']),
        ];
    }

    public function guardar(array $configuracion): bool
    {
        $conn = $this->getConnection();
        $stmt = $conn->prepare(
            'INSERT INTO configuracion_sincronizacion (id, limite_codigos, limite_stock)
             VALUES (1, ?, ?)
             ON DUPLICATE KEY UPDATE limite_codigos = VALUES(limite_codigos), limite_stock = VALUES(limite_stock)'
        );

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param(
            'ii',
            $configuracion['limite_codigos'],
            $configuracion['limite_stock']
        );
        $guardado = $stmt->execute();
        $stmt->close();

        return $guardado;
    }

    private function getConnection(): mysqli
    {
        require __DIR__ . '/../config/database.php';
        return $conn;
    }
}
