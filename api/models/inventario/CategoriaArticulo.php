<?php

declare(strict_types=1);

class CategoriaArticulo
{
    private PDO $conn;
    public string $table = 'categoria_articulo';

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    /**
     * Obtiene todas las categorías de artículos
     */
    public function getAll(): array
    {
        $sql = "SELECT id, descripcion FROM {$this->table} ORDER BY descripcion ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene una categoría por su ID
     */
    public function getById(int $id): ?array
    {
        $sql = "SELECT id, descripcion FROM {$this->table} WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Crea una nueva categoría de artículo
     */
    public function create(string $descripcion): int|false
    {
        $sql = "INSERT INTO {$this->table} (descripcion) VALUES (:descripcion)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':descripcion', $descripcion);
        
        if ($stmt->execute()) {
            return (int)$this->conn->lastInsertId();
        }
        return false;
    }

    /**
     * Actualiza una categoría de artículo
     */
    public function update(int $id, string $descripcion): bool
    {
        $sql = "UPDATE {$this->table} SET descripcion = :descripcion WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':descripcion', $descripcion);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Elimina una categoría de artículo
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
