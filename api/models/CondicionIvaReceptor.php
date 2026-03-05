<?php

declare(strict_types=1);

class CondicionIvaReceptor
{
    private PDO $conn;
    public string $table = 'condicion_iva_receptor';

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    /**
     * Obtiene todas las condiciones de IVA
     */
    public function getAll(): array
    {
        $sql = "SELECT id, descripcion_condicion FROM {$this->table} ORDER BY descripcion_condicion ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene una condición de IVA por su ID
     */
    public function getById(int $id): ?array
    {
        $sql = "SELECT id, descripcion_condicion FROM {$this->table} WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Crea una nueva condición de IVA
     */
    public function create(int $id, string $descripcionCondicion): bool
    {
        $sql = "INSERT INTO {$this->table} (id, descripcion_condicion) VALUES (:id, :descripcion_condicion)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':descripcion_condicion', $descripcionCondicion);
        return $stmt->execute();
    }

    /**
     * Actualiza una condición de IVA
     */
    public function update(int $id, string $descripcionCondicion): bool
    {
        $sql = "UPDATE {$this->table} SET descripcion_condicion = :descripcion_condicion WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':descripcion_condicion', $descripcionCondicion);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Elimina una condición de IVA
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
