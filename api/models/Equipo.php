<?php

declare(strict_types=1);

class Equipo
{
    private PDO $conn;
    public string $table = 'equipo';

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    /**
     * Obtiene todos los equipos
     */
    public function getAll(): array
    {
        $sql = "SELECT id, activo, disciplina, nombre FROM {$this->table} ORDER BY nombre ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene solo los equipos activos
     */
    public function getActivos(): array
    {
        $sql = "SELECT id, activo, disciplina, nombre FROM {$this->table} WHERE activo = 1 ORDER BY nombre ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene un equipo por su ID
     */
    public function getById(int $id): ?array
    {
        $sql = "SELECT id, activo, disciplina, nombre FROM {$this->table} WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Obtiene equipos por disciplina
     */
    public function getByDisciplina(string $disciplina): array
    {
        $sql = "SELECT id, activo, disciplina, nombre FROM {$this->table} WHERE disciplina = :disciplina ORDER BY nombre ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':disciplina', $disciplina);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Crea un nuevo equipo
     */
    public function create(int $id, string $nombre, string $disciplina, bool $activo = true): bool
    {
        $sql = "INSERT INTO {$this->table} (id, nombre, disciplina, activo) VALUES (:id, :nombre, :disciplina, :activo)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':nombre', $nombre);
        $stmt->bindValue(':disciplina', $disciplina);
        $stmt->bindValue(':activo', $activo ? 1 : 0, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Actualiza un equipo
     */
    public function update(int $id, string $nombre, string $disciplina, bool $activo): bool
    {
        $sql = "UPDATE {$this->table} SET nombre = :nombre, disciplina = :disciplina, activo = :activo WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':nombre', $nombre);
        $stmt->bindValue(':disciplina', $disciplina);
        $stmt->bindValue(':activo', $activo ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Elimina un equipo
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
