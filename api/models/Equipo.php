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
     * Obtiene todos los equipos (incluyendo pendientes de confirmación)
     */
    public function getAll(): array
    {
        $sql = "SELECT id, activo, disciplina, nombre, escudo, confirmar FROM {$this->table} ORDER BY confirmar ASC, nombre ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene solo los equipos activos y confirmados (para uso interno del sistema)
     */
    public function getActivos(): array
    {
        $sql = "SELECT id, activo, disciplina, nombre, escudo, confirmar FROM {$this->table} WHERE activo = 1 AND confirmar = 1 ORDER BY nombre ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene equipos pendientes de confirmación
     */
    public function getPendientesConfirmacion(): array
    {
        $sql = "SELECT id, activo, disciplina, nombre, escudo, confirmar FROM {$this->table} WHERE confirmar = 0 ORDER BY nombre ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene un equipo por su ID
     */
    public function getById(int $id): ?array
    {
        $sql = "SELECT id, activo, disciplina, nombre, escudo, confirmar FROM {$this->table} WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Obtiene equipos por disciplina (solo confirmados)
     */
    public function getByDisciplina(string $disciplina): array
    {
        $sql = "SELECT id, activo, disciplina, nombre, escudo, confirmar FROM {$this->table} WHERE disciplina = :disciplina AND confirmar = 1 ORDER BY nombre ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':disciplina', $disciplina);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Crea un nuevo equipo
     */
    public function create(string $nombre, string $disciplina, bool $activo = true, ?string $escudo = null, int $confirmar = 1): int|false
    {
        $sql = "INSERT INTO {$this->table} (nombre, disciplina, activo, escudo, confirmar) VALUES (:nombre, :disciplina, :activo, :escudo, :confirmar)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':nombre', $nombre);
        $stmt->bindValue(':disciplina', $disciplina);
        $stmt->bindValue(':activo', $activo ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':escudo', $escudo);
        $stmt->bindValue(':confirmar', $confirmar, PDO::PARAM_INT);
        if ($stmt->execute()) {
            return (int)$this->conn->lastInsertId();
        }
        return false;
    }

    /**
     * Actualiza un equipo
     */
    public function update(int $id, string $nombre, string $disciplina, bool $activo, ?string $escudo = null): bool
    {
        $sql = "UPDATE {$this->table} SET nombre = :nombre, disciplina = :disciplina, activo = :activo, escudo = :escudo WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':nombre', $nombre);
        $stmt->bindValue(':disciplina', $disciplina);
        $stmt->bindValue(':activo', $activo ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':escudo', $escudo);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Confirma un equipo pendiente (cambia confirmar a 1)
     */
    public function confirmar(int $id): bool
    {
        $sql = "UPDATE {$this->table} SET confirmar = 1 WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
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
