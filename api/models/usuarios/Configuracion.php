<?php

declare(strict_types=1);

class Configuracion
{
    private PDO $conn;
    private string $table = 'configuraciones';

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function getAll(): array
    {
        $sql = "SELECT id, clave, valor, descripcion, fecha_modificacion 
                FROM {$this->table} 
                ORDER BY clave ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $sql = "SELECT id, clave, valor, descripcion, fecha_modificacion 
                FROM {$this->table} 
                WHERE id = :id";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Obtiene el valor de una configuración directamente por su clave.
     */
    public function getValorPorClave(string $clave): ?string
    {
        $sql = "SELECT valor FROM {$this->table} WHERE clave = :clave";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':clave', $clave, PDO::PARAM_STR);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['valor'] : null;
    }

    public function create(string $clave, string $valor, ?string $descripcion): bool
    {
        $sql = "INSERT INTO {$this->table} (clave, valor, descripcion) 
                VALUES (:clave, :valor, :descripcion)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':clave', $clave, PDO::PARAM_STR);
        $stmt->bindValue(':valor', $valor, PDO::PARAM_STR);
        $stmt->bindValue(':descripcion', $descripcion, PDO::PARAM_STR);
        
        return $stmt->execute();
    }

    public function update(int $id, string $valor, ?string $descripcion): bool
    {
        $sql = "UPDATE {$this->table} 
                SET valor = :valor, descripcion = :descripcion 
                WHERE id = :id";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':valor', $valor, PDO::PARAM_STR);
        $stmt->bindValue(':descripcion', $descripcion, PDO::PARAM_STR);
        
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