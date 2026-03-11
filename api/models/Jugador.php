<?php

declare(strict_types=1);

class Jugador
{
    private PDO $conn;
    public string $table = 'jugador';

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    /**
     * Obtiene todos los jugadores
     */
    public function getAll(): array
    {
        $sql = "SELECT j.id, j.nombre, j.apellido, j.dni, j.fecha_nac, j.fecha_alta, j.activo,
                   je.id_equipo AS id_equipo_actual,
                   e.nombre AS equipo_nombre,
                   e.id_disciplina AS equipo_id_disciplina
            FROM {$this->table} j
            LEFT JOIN jugador_equipo je ON je.id_jugador = j.id AND je.fecha_hasta IS NULL
            LEFT JOIN equipo e ON je.id_equipo = e.id
            ORDER BY j.apellido ASC, j.nombre ASC, j.id ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene solo los jugadores activos
     */
    public function getActivos(): array
    {
        $sql = "SELECT j.id, j.nombre, j.apellido, j.dni, j.fecha_nac, j.fecha_alta, j.activo,
                   je.id_equipo AS id_equipo_actual,
                   e.nombre AS equipo_nombre,
                   e.id_disciplina AS equipo_id_disciplina
            FROM {$this->table} j
            LEFT JOIN jugador_equipo je ON je.id_jugador = j.id AND je.fecha_hasta IS NULL
            LEFT JOIN equipo e ON je.id_equipo = e.id
            WHERE j.activo = 1
            ORDER BY j.apellido ASC, j.nombre ASC, j.id ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene un jugador por ID
     */
    public function getById(int $id): ?array
    {
        $sql = "SELECT j.id, j.nombre, j.apellido, j.dni, j.fecha_nac, j.fecha_alta, j.activo,
                   je.id_equipo AS id_equipo_actual,
                   e.nombre AS equipo_nombre,
                   e.id_disciplina AS equipo_id_disciplina
            FROM {$this->table} j
            LEFT JOIN jugador_equipo je ON je.id_jugador = j.id AND je.fecha_hasta IS NULL
            LEFT JOIN equipo e ON je.id_equipo = e.id
            WHERE j.id = :id
            LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Obtiene un jugador por DNI
     */
    public function getByDni(string $dni): ?array
    {
        $sql = "SELECT j.id, j.nombre, j.apellido, j.dni, j.fecha_nac, j.fecha_alta, j.activo,
                   je.id_equipo AS id_equipo_actual,
                   e.nombre AS equipo_nombre,
                   e.id_disciplina AS equipo_id_disciplina
            FROM {$this->table} j
            LEFT JOIN jugador_equipo je ON je.id_jugador = j.id AND je.fecha_hasta IS NULL
            LEFT JOIN equipo e ON je.id_equipo = e.id
            WHERE j.dni = :dni
            LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':dni', $dni);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Crea un nuevo jugador
     */
    public function create(string $nombre, string $apellido, ?string $dni, ?string $fechaNac, ?string $fechaAlta, bool $activo = true, ?int $idEquipoActual = null): int|false
    {
        try {
            $this->conn->beginTransaction();

            $sql = "INSERT INTO {$this->table} (nombre, apellido, dni, fecha_nac, fecha_alta, activo)
                    VALUES (:nombre, :apellido, :dni, :fecha_nac, :fecha_alta, :activo)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':nombre', $nombre);
            $stmt->bindValue(':apellido', $apellido);
            $stmt->bindValue(':dni', $dni);
            $stmt->bindValue(':fecha_nac', $fechaNac);
            $stmt->bindValue(':fecha_alta', $fechaAlta);
            $stmt->bindValue(':activo', $activo ? 1 : 0, PDO::PARAM_INT);

            if (!$stmt->execute()) {
                $this->conn->rollBack();
                return false;
            }

            $idJugador = (int)$this->conn->lastInsertId();

            if ($idEquipoActual) {
                $this->assignEquipo($idJugador, $idEquipoActual, $fechaAlta ?: date('Y-m-d'));
            }

            $this->conn->commit();
            return $idJugador;
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Actualiza un jugador
     */
    public function update(int $id, string $nombre, string $apellido, ?string $dni, ?string $fechaNac, ?string $fechaAlta, bool $activo, ?int $idEquipoActual = null): bool
    {
        try {
            $this->conn->beginTransaction();

            $sql = "UPDATE {$this->table}
                    SET nombre = :nombre,
                        apellido = :apellido,
                        dni = :dni,
                        fecha_nac = :fecha_nac,
                        fecha_alta = :fecha_alta,
                        activo = :activo
                    WHERE id = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':nombre', $nombre);
            $stmt->bindValue(':apellido', $apellido);
            $stmt->bindValue(':dni', $dni);
            $stmt->bindValue(':fecha_nac', $fechaNac);
            $stmt->bindValue(':fecha_alta', $fechaAlta);
            $stmt->bindValue(':activo', $activo ? 1 : 0, PDO::PARAM_INT);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);

            if (!$stmt->execute()) {
                $this->conn->rollBack();
                return false;
            }

            $this->syncActiveEquipo($id, $idEquipoActual, $fechaAlta ?: date('Y-m-d'));

            $this->conn->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Elimina un jugador
     */
    public function delete(int $id): bool
    {
        try {
            $this->conn->beginTransaction();

            $sqlHist = "DELETE FROM jugador_equipo_hist WHERE id_jugador = :id_jugador";
            $stmtHist = $this->conn->prepare($sqlHist);
            $stmtHist->bindValue(':id_jugador', $id, PDO::PARAM_INT);
            $stmtHist->execute();

            $sqlRel = "DELETE FROM jugador_equipo WHERE id_jugador = :id_jugador";
            $stmtRel = $this->conn->prepare($sqlRel);
            $stmtRel->bindValue(':id_jugador', $id, PDO::PARAM_INT);
            $stmtRel->execute();

            $sql = "DELETE FROM {$this->table} WHERE id = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $deleted = $stmt->execute();

            if ($deleted) {
                $this->conn->commit();
                return true;
            }

            $this->conn->rollBack();
            return false;
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $e;
        }
    }

    private function getActiveRelation(int $idJugador): ?array
    {
        $sql = "SELECT id, id_jugador, id_equipo, fecha_desde, fecha_hasta
                FROM jugador_equipo
                WHERE id_jugador = :id_jugador AND fecha_hasta IS NULL
                ORDER BY id DESC
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id_jugador', $idJugador, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    private function syncActiveEquipo(int $idJugador, ?int $idEquipoActual, string $fechaReferencia): void
    {
        $actual = $this->getActiveRelation($idJugador);

        if (!$idEquipoActual) {
            if ($actual) {
                $this->closeActiveRelation((int)$actual['id'], $idJugador, (int)$actual['id_equipo'], $fechaReferencia);
            }
            return;
        }

        if ($actual && (int)$actual['id_equipo'] === $idEquipoActual) {
            return;
        }

        if ($actual) {
            $this->closeActiveRelation((int)$actual['id'], $idJugador, (int)$actual['id_equipo'], $fechaReferencia);
        }

        $this->assignEquipo($idJugador, $idEquipoActual, $fechaReferencia, $actual ? 'ACTUALIZACION' : 'ALTA');
    }

    private function assignEquipo(int $idJugador, int $idEquipo, string $fechaDesde, string $accion = 'ALTA'): void
    {
        $sql = "INSERT INTO jugador_equipo (id_jugador, id_equipo, fecha_desde, fecha_hasta)
                VALUES (:id_jugador, :id_equipo, :fecha_desde, NULL)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id_jugador', $idJugador, PDO::PARAM_INT);
        $stmt->bindValue(':id_equipo', $idEquipo, PDO::PARAM_INT);
        $stmt->bindValue(':fecha_desde', $fechaDesde);
        $stmt->execute();

        $idJugadorEquipo = (int)$this->conn->lastInsertId();
        $this->insertRelationHistory($idJugadorEquipo, $idJugador, $idEquipo, $fechaDesde, null, $accion);
    }

    private function closeActiveRelation(int $idJugadorEquipo, int $idJugador, int $idEquipo, string $fechaHasta): void
    {
        $sql = "UPDATE jugador_equipo
                SET fecha_hasta = :fecha_hasta
                WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':fecha_hasta', $fechaHasta);
        $stmt->bindValue(':id', $idJugadorEquipo, PDO::PARAM_INT);
        $stmt->execute();

        $sqlFechaDesde = "SELECT fecha_desde FROM jugador_equipo WHERE id = :id LIMIT 1";
        $stmtFechaDesde = $this->conn->prepare($sqlFechaDesde);
        $stmtFechaDesde->bindValue(':id', $idJugadorEquipo, PDO::PARAM_INT);
        $stmtFechaDesde->execute();
        $fechaDesde = $stmtFechaDesde->fetchColumn() ?: null;

        $this->insertRelationHistory($idJugadorEquipo, $idJugador, $idEquipo, $fechaDesde, $fechaHasta, 'BAJA');
    }

    private function insertRelationHistory(int $idJugadorEquipo, int $idJugador, int $idEquipo, ?string $fechaDesde, ?string $fechaHasta, string $accion): void
    {
        $sql = "INSERT INTO jugador_equipo_hist (id_jugador_equipo, id_jugador, id_equipo, fecha_desde, fecha_hasta, fecha_cambio, accion)
                VALUES (:id_jugador_equipo, :id_jugador, :id_equipo, :fecha_desde, :fecha_hasta, NOW(), :accion)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id_jugador_equipo', $idJugadorEquipo, PDO::PARAM_INT);
        $stmt->bindValue(':id_jugador', $idJugador, PDO::PARAM_INT);
        $stmt->bindValue(':id_equipo', $idEquipo, PDO::PARAM_INT);
        $stmt->bindValue(':fecha_desde', $fechaDesde);
        $stmt->bindValue(':fecha_hasta', $fechaHasta);
        $stmt->bindValue(':accion', $accion);
        $stmt->execute();
    }
}