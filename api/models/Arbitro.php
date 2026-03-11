<?php

declare(strict_types=1);

class Arbitro
{
    private PDO $conn;
    public string $table = 'arbitro';

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function getAll(): array
    {
        $sql = "SELECT id, nombre, apellido, dni, telefono, email, activo
                FROM {$this->table}
                ORDER BY apellido ASC, nombre ASC, id ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getActivos(): array
    {
        $sql = "SELECT id, nombre, apellido, dni, telefono, email, activo
                FROM {$this->table}
                WHERE activo = 1
                ORDER BY apellido ASC, nombre ASC, id ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $sql = "SELECT id, nombre, apellido, dni, telefono, email, activo
                FROM {$this->table}
                WHERE id = :id
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function getByDni(string $dni): ?array
    {
        $sql = "SELECT id, nombre, apellido, dni, telefono, email, activo
                FROM {$this->table}
                WHERE dni = :dni
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':dni', $dni);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function create(string $nombre, string $apellido, ?string $dni, ?string $telefono, ?string $email, bool $activo = true): int|false
    {
        $sql = "INSERT INTO {$this->table} (nombre, apellido, dni, telefono, email, activo)
                VALUES (:nombre, :apellido, :dni, :telefono, :email, :activo)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':nombre', $nombre);
        $stmt->bindValue(':apellido', $apellido);
        $stmt->bindValue(':dni', $dni);
        $stmt->bindValue(':telefono', $telefono);
        $stmt->bindValue(':email', $email);
        $stmt->bindValue(':activo', $activo ? 1 : 0, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return (int)$this->conn->lastInsertId();
        }

        return false;
    }

    public function update(int $id, string $nombre, string $apellido, ?string $dni, ?string $telefono, ?string $email, bool $activo): bool
    {
        $sql = "UPDATE {$this->table}
                SET nombre = :nombre,
                    apellido = :apellido,
                    dni = :dni,
                    telefono = :telefono,
                    email = :email,
                    activo = :activo
                WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':nombre', $nombre);
        $stmt->bindValue(':apellido', $apellido);
        $stmt->bindValue(':dni', $dni);
        $stmt->bindValue(':telefono', $telefono);
        $stmt->bindValue(':email', $email);
        $stmt->bindValue(':activo', $activo ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
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