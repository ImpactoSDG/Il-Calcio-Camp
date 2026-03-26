<?php

declare(strict_types=1);

class MedioCobro
{
    private PDO $conn;
    public string $table = 'medio_cobro';

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    /**
     * Obtiene todos los medios de cobro
     */
    public function getAll(): array
    {
        $sql = "SELECT id, descripcion, activo FROM {$this->table} ORDER BY descripcion ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene solo los medios de cobro activos
     */
    public function getActivos(): array
    {
        $sql = "SELECT id, descripcion, activo FROM {$this->table} WHERE activo = 1 ORDER BY descripcion ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene un medio de cobro por su ID
     */
    public function getById(int $id): ?array
    {
        $sql = "SELECT id, descripcion, activo FROM {$this->table} WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Crea un nuevo medio de cobro
     */
    public function create(int $id, string $descripcion, bool $activo = true): bool
    {
        $sql = "INSERT INTO {$this->table} (id, descripcion, activo) VALUES (:id, :descripcion, :activo)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':descripcion', $descripcion);
        $stmt->bindValue(':activo', $activo ? 1 : 0, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Actualiza un medio de cobro
     */
    public function update(int $id, string $descripcion, bool $activo): bool
    {
        $sql = "UPDATE {$this->table} SET descripcion = :descripcion, activo = :activo WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':descripcion', $descripcion);
        $stmt->bindValue(':activo', $activo ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Elimina un medio de cobro
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
