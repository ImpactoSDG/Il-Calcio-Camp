<?php

declare(strict_types=1);

class Provincia
{
    private PDO $conn;
    public string $table = 'provincia';

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    /**
     * Obtiene todas las provincias
     */
    public function getAll(): array
    {
        $sql = "SELECT id, provincia FROM {$this->table} ORDER BY provincia ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene una provincia por su ID
     */
    public function getById(int $id): ?array
    {
        $sql = "SELECT id, provincia FROM {$this->table} WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Crea una nueva provincia
     */
    public function create(int $id, string $provincia): bool
    {
        $sql = "INSERT INTO {$this->table} (id, provincia) VALUES (:id, :provincia)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':provincia', $provincia);
        return $stmt->execute();
    }

    /**
     * Actualiza una provincia
     */
    public function update(int $id, string $provincia): bool
    {
        $sql = "UPDATE {$this->table} SET provincia = :provincia WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':provincia', $provincia);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Elimina una provincia
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
