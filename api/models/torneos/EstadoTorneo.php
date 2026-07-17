<?php

declare(strict_types=1);

class EstadoTorneo
{
    private PDO $conn;
    public string $table = 'estado_torneo';

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function getAll(): array
    {
        $sql = "SELECT id, descripcion, activo
                FROM {$this->table}
                ORDER BY id ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
