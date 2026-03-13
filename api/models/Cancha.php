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
        $sql = "SELECT id, nombre, descripcion, activo
                FROM {$this->table}
                ORDER BY nombre ASC, id ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $sql = "SELECT id, nombre, descripcion, activo
                FROM {$this->table}
                WHERE id = :id
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function create(string $nombre, ?string $descripcion, bool $activo = true): int|false
    {
        $sql = "INSERT INTO {$this->table} (nombre, descripcion, activo)
                VALUES (:nombre, :descripcion, :activo)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':nombre', $nombre);
        $stmt->bindValue(':descripcion', $descripcion);
        $stmt->bindValue(':activo', $activo ? 1 : 0, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return (int)$this->conn->lastInsertId();
        }

        return false;
    }

    public function update(int $id, string $nombre, ?string $descripcion, bool $activo): bool
    {
        $sql = "UPDATE {$this->table}
                SET nombre = :nombre,
                    descripcion = :descripcion,
                    activo = :activo
                WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':nombre', $nombre);
        $stmt->bindValue(':descripcion', $descripcion);
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