<?php

declare(strict_types=1);

class TipoEventoPartido
{
    private PDO $conn;
    public string $table = 'tipo_evento_partido';

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function getAll(): array
    {
        $sql = "SELECT id, descripcion, activo
                FROM {$this->table}
                ORDER BY descripcion ASC, id ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}