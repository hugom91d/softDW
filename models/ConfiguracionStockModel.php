<?php

class ConfiguracionStockModel
{
    private const VALORES_PREDETERMINADOS = [
        'limite_critico' => 5,
        'limite_advertencia' => 10,
        'color_critico' => '#dc2626',
        'color_advertencia' => '#d97706',
        'color_disponible' => '#16a34a',
    ];

    public function obtener(): array
    {
        $conn = $this->getConnection();
        $result = $conn->query('SELECT limite_critico, limite_advertencia, color_critico, color_advertencia, color_disponible FROM configuracion_stock WHERE id = 1');
        $configuracion = $result ? $result->fetch_assoc() : null;

        if (!$configuracion) {
            return self::VALORES_PREDETERMINADOS;
        }

        return [
            'limite_critico' => (float) ($configuracion['limite_critico'] ?? self::VALORES_PREDETERMINADOS['limite_critico']),
            'limite_advertencia' => (float) ($configuracion['limite_advertencia'] ?? self::VALORES_PREDETERMINADOS['limite_advertencia']),
            'color_critico' => $this->colorValido($configuracion['color_critico'] ?? '') ? $configuracion['color_critico'] : self::VALORES_PREDETERMINADOS['color_critico'],
            'color_advertencia' => $this->colorValido($configuracion['color_advertencia'] ?? '') ? $configuracion['color_advertencia'] : self::VALORES_PREDETERMINADOS['color_advertencia'],
            'color_disponible' => $this->colorValido($configuracion['color_disponible'] ?? '') ? $configuracion['color_disponible'] : self::VALORES_PREDETERMINADOS['color_disponible'],
        ];
    }

    public function guardar(array $configuracion): bool
    {
        $conn = $this->getConnection();
        $stmt = $conn->prepare(
            'INSERT INTO configuracion_stock (id, limite_critico, limite_advertencia, color_critico, color_advertencia, color_disponible)
             VALUES (1, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE limite_critico = VALUES(limite_critico), limite_advertencia = VALUES(limite_advertencia), color_critico = VALUES(color_critico), color_advertencia = VALUES(color_advertencia), color_disponible = VALUES(color_disponible)'
        );

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param(
            'ddsss',
            $configuracion['limite_critico'],
            $configuracion['limite_advertencia'],
            $configuracion['color_critico'],
            $configuracion['color_advertencia'],
            $configuracion['color_disponible']
        );
        $guardado = $stmt->execute();
        $stmt->close();

        return $guardado;
    }

    public function colorValido(string $color): bool
    {
        return (bool) preg_match('/^#[0-9a-fA-F]{6}$/', $color);
    }

    private function getConnection(): mysqli
    {
        require __DIR__ . '/../config/database.php';
        return $conn;
    }
}