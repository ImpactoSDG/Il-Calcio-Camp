<?php

declare(strict_types=1);

class Disciplina
{
    private PDO $conn;
    public string $table = 'disciplina';

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function getAll(): array
    {
        $sql = "SELECT id, nombre FROM {$this->table} ORDER BY nombre ASC, id ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
