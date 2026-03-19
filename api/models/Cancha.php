<?php

declare(strict_types=1);

class Cancha
{
    private PDO $conn;
    public string $table = 'cancha';

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function getAll(): array
    {
        $sql = "SELECT c.id,
                   c.nombre,
                   c.descripcion,
                   c.id_disciplina,
                   d.nombre AS disciplina_nombre,
                   c.activo
            FROM {$this->table} c
            LEFT JOIN disciplina d ON d.id = c.id_disciplina
            ORDER BY c.nombre ASC, c.id ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $sql = "SELECT c.id,
                   c.nombre,
                   c.descripcion,
                   c.id_disciplina,
                   d.nombre AS disciplina_nombre,
                   c.activo
            FROM {$this->table} c
            LEFT JOIN disciplina d ON d.id = c.id_disciplina
            WHERE c.id = :id
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

        public function create(string $nombre, ?string $descripcion, int $idDisciplina, bool $activo = true): int|false
    {
        $sql = "INSERT INTO {$this->table} (nombre, descripcion, id_disciplina, activo)
            VALUES (:nombre, :descripcion, :id_disciplina, :activo)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':nombre', $nombre);
        $stmt->bindValue(':descripcion', $descripcion);
        $stmt->bindValue(':id_disciplina', $idDisciplina, PDO::PARAM_INT);
        $stmt->bindValue(':activo', $activo ? 1 : 0, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return (int)$this->conn->lastInsertId();
        }

        return false;
    }

    public function update(int $id, string $nombre, ?string $descripcion, int $idDisciplina, bool $activo): bool
    {
        $sql = "UPDATE {$this->table}
                SET nombre = :nombre,
                    descripcion = :descripcion,
                    id_disciplina = :id_disciplina,
                    activo = :activo
                WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':nombre', $nombre);
        $stmt->bindValue(':descripcion', $descripcion);
        $stmt->bindValue(':id_disciplina', $idDisciplina, PDO::PARAM_INT);
        $stmt->bindValue(':activo', $activo ? 1 : 0, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function delete(int $id): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}