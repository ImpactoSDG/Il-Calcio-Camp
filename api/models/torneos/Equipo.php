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
     * Obtiene todos los equipos (incluyendo pendientes de confirmacion)
     */
    public function getAll(): array
    {
        $sql = "SELECT e.id,
                       e.activo,
                       e.id_disciplina,
                       COALESCE(d.nombre, e.disciplina) AS disciplina,
                       COALESCE(d.nombre, e.disciplina) AS disciplina_nombre,
                       e.nombre,
                       e.escudo,
                       e.confirmar
                FROM {$this->table} e
                LEFT JOIN disciplina d ON d.id = e.id_disciplina
                ORDER BY e.confirmar ASC, e.nombre ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene solo los equipos activos y confirmados (para uso interno del sistema)
     */
    public function getActivos(): array
    {
        $sql = "SELECT e.id,
                       e.activo,
                       e.id_disciplina,
                       COALESCE(d.nombre, e.disciplina) AS disciplina,
                       COALESCE(d.nombre, e.disciplina) AS disciplina_nombre,
                       e.nombre,
                       e.escudo,
                       e.confirmar
                FROM {$this->table} e
                LEFT JOIN disciplina d ON d.id = e.id_disciplina
                WHERE e.activo = 1 AND e.confirmar = 1
                ORDER BY e.nombre ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene equipos pendientes de confirmacion
     */
    public function getPendientesConfirmacion(): array
    {
        $sql = "SELECT e.id,
                       e.activo,
                       e.id_disciplina,
                       COALESCE(d.nombre, e.disciplina) AS disciplina,
                       COALESCE(d.nombre, e.disciplina) AS disciplina_nombre,
                       e.nombre,
                       e.escudo,
                       e.confirmar
                FROM {$this->table} e
                LEFT JOIN disciplina d ON d.id = e.id_disciplina
                WHERE e.confirmar = 0
                ORDER BY e.nombre ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene un equipo por su ID
     */
    public function getById(int $id): ?array
    {
        $sql = "SELECT e.id,
                       e.activo,
                       e.id_disciplina,
                       COALESCE(d.nombre, e.disciplina) AS disciplina,
                       COALESCE(d.nombre, e.disciplina) AS disciplina_nombre,
                       e.nombre,
                       e.escudo,
                       e.confirmar
                FROM {$this->table} e
                LEFT JOIN disciplina d ON d.id = e.id_disciplina
                WHERE e.id = :id
                LIMIT 1";
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
        $sql = "SELECT e.id,
                       e.activo,
                       e.id_disciplina,
                       COALESCE(d.nombre, e.disciplina) AS disciplina,
                       COALESCE(d.nombre, e.disciplina) AS disciplina_nombre,
                       e.nombre,
                       e.escudo,
                       e.confirmar
                FROM {$this->table} e
                LEFT JOIN disciplina d ON d.id = e.id_disciplina
                WHERE COALESCE(d.nombre, e.disciplina) = :disciplina
                  AND e.confirmar = 1
                ORDER BY e.nombre ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':disciplina', $disciplina);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene equipos por id de disciplina (solo confirmados)
     */
    public function getByDisciplinaId(int $idDisciplina): array
    {
        $sql = "SELECT e.id,
                       e.activo,
                       e.id_disciplina,
                       COALESCE(d.nombre, e.disciplina) AS disciplina,
                       COALESCE(d.nombre, e.disciplina) AS disciplina_nombre,
                       e.nombre,
                       e.escudo,
                       e.confirmar
                FROM {$this->table} e
                LEFT JOIN disciplina d ON d.id = e.id_disciplina
                WHERE e.id_disciplina = :id_disciplina
                  AND e.confirmar = 1
                ORDER BY e.nombre ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id_disciplina', $idDisciplina, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Crea un nuevo equipo
     */
    public function create(string $nombre, int $idDisciplina, bool $activo = true, ?string $escudo = null, int $confirmar = 1): int|false
    {
        $sql = "INSERT INTO {$this->table} (nombre, disciplina, id_disciplina, activo, escudo, confirmar)
                VALUES (
                  :nombre,
                  (SELECT d.nombre FROM disciplina d WHERE d.id = :id_disciplina),
                  :id_disciplina,
                  :activo,
                  :escudo,
                  :confirmar
                )";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':nombre', $nombre);
        $stmt->bindValue(':id_disciplina', $idDisciplina, PDO::PARAM_INT);
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
    public function update(int $id, string $nombre, int $idDisciplina, bool $activo, ?string $escudo = null): bool
    {
        $sql = "UPDATE {$this->table}
                SET nombre = :nombre,
                    id_disciplina = :id_disciplina,
                    disciplina = (SELECT d.nombre FROM disciplina d WHERE d.id = :id_disciplina),
                    activo = :activo,
                    escudo = :escudo
                WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':nombre', $nombre);
        $stmt->bindValue(':id_disciplina', $idDisciplina, PDO::PARAM_INT);
        $stmt->bindValue(':activo', $activo ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':escudo', $escudo);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Actualiza solo el estado activo
     */
    public function setActivo(int $id, bool $activo): bool
    {
        $sql = "UPDATE {$this->table} SET activo = :activo WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':activo', $activo ? 1 : 0, PDO::PARAM_INT);
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
