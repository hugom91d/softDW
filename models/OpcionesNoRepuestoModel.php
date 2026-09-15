<?php

class OpcionesNoRepuestoModel
{
    public function obtenerTodas(): array
    {
        $conn = $this->getConnection();
        $result = $conn->query('SELECT id, texto FROM opciones_no_repuesto ORDER BY orden, id');
        $opciones = [];

        if (!$result) {
            return $opciones;
        }

        while ($opcion = $result->fetch_assoc()) {
            $opciones[] = [
                'id' => (int) $opcion['id'],
                'texto' => (string) $opcion['texto'],
            ];
        }

        return $opciones;
    }

    public function obtenerActivas(): array
    {
        $conn = $this->getConnection();
        $result = $conn->query('SELECT texto FROM opciones_no_repuesto WHERE activo = 1 ORDER BY orden, id');
        $opciones = [];

        if (!$result) {
            return $opciones;
        }

        while ($opcion = $result->fetch_assoc()) {
            $opciones[] = (string) $opcion['texto'];
        }

        return $opciones;
    }

    public function guardar(array $textos): bool
    {
        $conn = $this->getConnection();
        $normalizados = [];

        foreach ($textos as $texto) {
            $texto = trim((string) $texto);
            if ($texto !== '' && !in_array($texto, $normalizados, true)) {
                $normalizados[] = $texto;
            }
        }

        if ($normalizados === []) {
            return false;
        }

        $conn->begin_transaction();
        $eliminado = $conn->query('DELETE FROM opciones_no_repuesto');
        $stmt = $conn->prepare('INSERT INTO opciones_no_repuesto (texto, activo, orden) VALUES (?, 1, ?)');

        if (!$eliminado || !$stmt) {
            $conn->rollback();
            return false;
        }

        foreach ($normalizados as $indice => $texto) {
            $orden = $indice + 1;
            $stmt->bind_param('si', $texto, $orden);
            if (!$stmt->execute()) {
                $stmt->close();
                $conn->rollback();
                return false;
            }
        }

        $stmt->close();
        return $conn->commit();
    }

    private function getConnection(): mysqli
    {
        require __DIR__ . '/../config/database.php';
        return $conn;
    }
}