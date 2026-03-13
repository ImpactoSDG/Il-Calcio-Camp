<?php

declare(strict_types=1);

class Proveedor
{
    private PDO $conn;
    public string $table = 'proveedor';

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function getAll(): array
    {
        $sql = "SELECT id_proveedor, nombre, apellido, nombre_fantasia, telefono, direccion, activo
                FROM {$this->table}
                ORDER BY nombre ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getActivos(): array
    {
        $sql = "SELECT id_proveedor, nombre, apellido, nombre_fantasia, telefono, direccion, activo
                FROM {$this->table}
                WHERE activo = 1
                ORDER BY nombre ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $sql = "SELECT id_proveedor, nombre, apellido, nombre_fantasia, telefono, direccion, activo
                FROM {$this->table}
                WHERE id_proveedor = :id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function create(string $nombre, ?string $apellido, ?string $nombreFantasia, ?string $telefono, ?string $direccion): int|false
    {
        $sql = "INSERT INTO {$this->table} (nombre, apellido, nombre_fantasia, telefono, direccion, activo)
                VALUES (:nombre, :apellido, :nombre_fantasia, :telefono, :direccion, 1)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':nombre', $nombre);
        $stmt->bindValue(':apellido', $apellido);
        $stmt->bindValue(':nombre_fantasia', $nombreFantasia);
        $stmt->bindValue(':telefono', $telefono);
        $stmt->bindValue(':direccion', $direccion);
        if ($stmt->execute()) {
            return (int)$this->conn->lastInsertId();
        }
        return false;
    }

    public function update(int $id, string $nombre, ?string $apellido, ?string $nombreFantasia, ?string $telefono, ?string $direccion, bool $activo): bool
    {
        $sql = "UPDATE {$this->table}
                SET nombre = :nombre,
                    apellido = :apellido,
                    nombre_fantasia = :nombre_fantasia,
                    telefono = :telefono,
                    direccion = :direccion,
                    activo = :activo
                WHERE id_proveedor = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':nombre', $nombre);
        $stmt->bindValue(':apellido', $apellido);
        $stmt->bindValue(':nombre_fantasia', $nombreFantasia);
        $stmt->bindValue(':telefono', $telefono);
        $stmt->bindValue(':direccion', $direccion);
        $stmt->bindValue(':activo', $activo ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Eliminación lógica: desactiva el proveedor en lugar de borrarlo.
     */
    public function delete(int $id): bool
    {
        $sql = "UPDATE {$this->table} SET activo = 0 WHERE id_proveedor = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
