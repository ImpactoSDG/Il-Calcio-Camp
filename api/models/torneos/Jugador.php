<?php

declare(strict_types=1);

class Jugador
{
    private PDO $conn;
    public string $table = 'jugador';
    private bool $capitanColumnRelacionResolved = false;
    private bool $capitanColumnHistResolved = false;
    private ?string $capitanColumnInRelacion = null;
    private ?string $capitanColumnInHist = null;
    private bool $arqueroColumnRelacionResolved = false;
    private bool $arqueroColumnHistResolved = false;
    private ?string $arqueroColumnInRelacion = null;
    private ?string $arqueroColumnInHist = null;

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
                   j.email, j.telefono,
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
                   j.email, j.telefono,
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
                   j.email, j.telefono,
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
                   j.email, j.telefono,
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
     * Obtiene jugadores activos de un equipo desde la relacion jugador_equipo
     */
    public function getByEquipo(int $idEquipo): array
    {
        $capitanColumn = $this->getCapitanColumnInRelacion();
        $arqueroColumn = $this->getArqueroColumnInRelacion();
        $capitanSelect = $capitanColumn
            ? ", COALESCE(je.{$capitanColumn}, 0) AS capitan"
            : ', 0 AS capitan';
        $arqueroSelect = $arqueroColumn
            ? ", COALESCE(je.{$arqueroColumn}, 0) AS arquero"
            : ', 0 AS arquero';
        $orderBy = $capitanColumn
            ? "ORDER BY COALESCE(je.{$capitanColumn}, 0) DESC, j.apellido ASC, j.nombre ASC, j.id ASC"
            : 'ORDER BY j.apellido ASC, j.nombre ASC, j.id ASC';

        $sql = "SELECT j.id, j.nombre, j.apellido, j.dni, j.fecha_nac, j.fecha_alta, j.activo,
                       je.id AS id_jugador_equipo,
                       je.id_equipo AS id_equipo_actual,
                       e.nombre AS equipo_nombre,
                       e.id_disciplina AS equipo_id_disciplina,
                       je.fecha_desde,
                       je.fecha_hasta
                      {$capitanSelect}
                      {$arqueroSelect}
                FROM {$this->table} j
                INNER JOIN jugador_equipo je ON je.id_jugador = j.id AND je.fecha_hasta IS NULL
                INNER JOIN equipo e ON je.id_equipo = e.id
                WHERE je.id_equipo = :id_equipo
                {$orderBy}";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id_equipo', $idEquipo, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Crea un nuevo jugador
     */
    public function create(string $nombre, string $apellido, ?string $dni, ?string $fechaNac, ?string $fechaAlta, bool $activo = true, ?int $idEquipoActual = null, bool $capitan = false, bool $arquero = false, ?string $email = null, ?string $telefono = null): int|false
    {
        try {
            $this->conn->beginTransaction();

            $sql = "INSERT INTO {$this->table} (nombre, apellido, dni, fecha_nac, fecha_alta, activo, email, telefono)
                    VALUES (:nombre, :apellido, :dni, :fecha_nac, :fecha_alta, :activo, :email, :telefono)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':nombre', $nombre);
            $stmt->bindValue(':apellido', $apellido);
            $stmt->bindValue(':dni', $dni);
            $stmt->bindValue(':fecha_nac', $fechaNac);
            $stmt->bindValue(':fecha_alta', $fechaAlta);
            $stmt->bindValue(':activo', $activo ? 1 : 0, PDO::PARAM_INT);
            $stmt->bindValue(':email', $email);
            $stmt->bindValue(':telefono', $telefono);

            if (!$stmt->execute()) {
                $this->conn->rollBack();
                return false;
            }

            $idJugador = (int)$this->conn->lastInsertId();

            if ($idEquipoActual) {
                $this->assignEquipo($idJugador, $idEquipoActual, $fechaAlta ?: date('Y-m-d'), 'ALTA', $capitan, $arquero);
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
    public function update(int $id, string $nombre, string $apellido, ?string $dni, ?string $fechaNac, ?string $fechaAlta, bool $activo, ?int $idEquipoActual = null, bool $capitan = false, bool $arquero = false, ?string $email = null, ?string $telefono = null): bool
    {
        try {
            $this->conn->beginTransaction();

            $sql = "UPDATE {$this->table}
                    SET nombre = :nombre,
                        apellido = :apellido,
                        dni = :dni,
                        fecha_nac = :fecha_nac,
                        fecha_alta = :fecha_alta,
                        activo = :activo,
                        email = :email,
                        telefono = :telefono
                    WHERE id = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':nombre', $nombre);
            $stmt->bindValue(':apellido', $apellido);
            $stmt->bindValue(':dni', $dni);
            $stmt->bindValue(':fecha_nac', $fechaNac);
            $stmt->bindValue(':fecha_alta', $fechaAlta);
            $stmt->bindValue(':activo', $activo ? 1 : 0, PDO::PARAM_INT);
            $stmt->bindValue(':email', $email);
            $stmt->bindValue(':telefono', $telefono);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);

            if (!$stmt->execute()) {
                $this->conn->rollBack();
                return false;
            }

            $this->syncActiveEquipo($id, $idEquipoActual, $fechaAlta ?: date('Y-m-d'), $capitan, $arquero);

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
        $capitanColumn = $this->getCapitanColumnInRelacion();
        $arqueroColumn = $this->getArqueroColumnInRelacion();
        $capitanSelect = $capitanColumn ? ", COALESCE({$capitanColumn}, 0) AS capitan" : ', 0 AS capitan';
        $arqueroSelect = $arqueroColumn ? ", COALESCE({$arqueroColumn}, 0) AS arquero" : ', 0 AS arquero';

        $sql = "SELECT id, id_jugador, id_equipo, fecha_desde, fecha_hasta
                   {$capitanSelect}
                   {$arqueroSelect}
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

    private function syncActiveEquipo(int $idJugador, ?int $idEquipoActual, string $fechaReferencia, bool $capitan = false, bool $arquero = false): void
    {
        $actual = $this->getActiveRelation($idJugador);

        if (!$idEquipoActual) {
            if ($actual) {
                $this->closeActiveRelation((int)$actual['id'], $idJugador, (int)$actual['id_equipo'], $fechaReferencia);
            }
            return;
        }

        if ($actual && (int)$actual['id_equipo'] === $idEquipoActual) {
            $this->syncRolesOnActiveRelation($actual, $capitan, $arquero);
            return;
        }

        if ($actual) {
            $this->closeActiveRelation((int)$actual['id'], $idJugador, (int)$actual['id_equipo'], $fechaReferencia);
        }

        $this->assignEquipo($idJugador, $idEquipoActual, $fechaReferencia, $actual ? 'ACTUALIZACION' : 'ALTA', $capitan, $arquero);
    }

    private function assignEquipo(int $idJugador, int $idEquipo, string $fechaDesde, string $accion = 'ALTA', bool $capitan = false, bool $arquero = false): void
    {
        $capitanColumn = $this->getCapitanColumnInRelacion();
        $arqueroColumn = $this->getArqueroColumnInRelacion();

        $columns = ['id_jugador', 'id_equipo', 'fecha_desde', 'fecha_hasta'];
        $values = [':id_jugador', ':id_equipo', ':fecha_desde', 'NULL'];

        if ($capitanColumn) {
            $columns[] = $capitanColumn;
            $values[] = ':capitan';
        }
        if ($arqueroColumn) {
            $columns[] = $arqueroColumn;
            $values[] = ':arquero';
        }

        $sql = 'INSERT INTO jugador_equipo (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $values) . ')';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id_jugador', $idJugador, PDO::PARAM_INT);
        $stmt->bindValue(':id_equipo', $idEquipo, PDO::PARAM_INT);
        $stmt->bindValue(':fecha_desde', $fechaDesde);
        if ($capitanColumn) {
            $stmt->bindValue(':capitan', $capitan ? 1 : 0, PDO::PARAM_INT);
        }
        if ($arqueroColumn) {
            $stmt->bindValue(':arquero', $arquero ? 1 : 0, PDO::PARAM_INT);
        }
        $stmt->execute();

        $idJugadorEquipo = (int)$this->conn->lastInsertId();
        $this->insertRelationHistory($idJugadorEquipo, $idJugador, $idEquipo, $fechaDesde, null, $accion, $capitan, $arquero);
    }

    private function closeActiveRelation(int $idJugadorEquipo, int $idJugador, int $idEquipo, string $fechaHasta): void
    {
        $actual = $this->getRelationById($idJugadorEquipo);

        $sql = "UPDATE jugador_equipo
                SET fecha_hasta = :fecha_hasta
                WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':fecha_hasta', $fechaHasta);
        $stmt->bindValue(':id', $idJugadorEquipo, PDO::PARAM_INT);
        $stmt->execute();

        $this->insertRelationHistory(
            $idJugadorEquipo,
            $idJugador,
            $idEquipo,
            $actual['fecha_desde'] ?? null,
            $fechaHasta,
            'BAJA',
            isset($actual['capitan']) ? (bool)$actual['capitan'] : false,
            isset($actual['arquero']) ? (bool)$actual['arquero'] : false
        );
    }

    private function syncRolesOnActiveRelation(array $actual, bool $capitan, bool $arquero): void
    {
        $capitanColumn = $this->getCapitanColumnInRelacion();
        $arqueroColumn = $this->getArqueroColumnInRelacion();
        if (!$capitanColumn && !$arqueroColumn) {
            return;
        }

        $currentCaptain = isset($actual['capitan']) ? (bool)$actual['capitan'] : false;
        $currentArquero = isset($actual['arquero']) ? (bool)$actual['arquero'] : false;
        if ($currentCaptain === $capitan && $currentArquero === $arquero) {
            return;
        }

        $sets = [];
        if ($capitanColumn) {
            $sets[] = "{$capitanColumn} = :capitan";
        }
        if ($arqueroColumn) {
            $sets[] = "{$arqueroColumn} = :arquero";
        }

        $sql = 'UPDATE jugador_equipo SET ' . implode(', ', $sets) . ' WHERE id = :id';
        $stmt = $this->conn->prepare($sql);
        if ($capitanColumn) {
            $stmt->bindValue(':capitan', $capitan ? 1 : 0, PDO::PARAM_INT);
        }
        if ($arqueroColumn) {
            $stmt->bindValue(':arquero', $arquero ? 1 : 0, PDO::PARAM_INT);
        }
        $stmt->bindValue(':id', (int)$actual['id'], PDO::PARAM_INT);
        $stmt->execute();

        $this->insertRelationHistory(
            (int)$actual['id'],
            (int)$actual['id_jugador'],
            (int)$actual['id_equipo'],
            $actual['fecha_desde'] ?? null,
            $actual['fecha_hasta'] ?? null,
            'ACTUALIZACION_ROLES',
            $capitan,
            $arquero
        );
    }

    private function insertRelationHistory(int $idJugadorEquipo, int $idJugador, int $idEquipo, ?string $fechaDesde, ?string $fechaHasta, string $accion, bool $capitan = false, bool $arquero = false): void
    {
        $capitanColumn = $this->getCapitanColumnInHist();
        $arqueroColumn = $this->getArqueroColumnInHist();

        $columns = ['id_jugador_equipo', 'id_jugador', 'id_equipo', 'fecha_desde', 'fecha_hasta', 'fecha_cambio', 'accion'];
        $values = [':id_jugador_equipo', ':id_jugador', ':id_equipo', ':fecha_desde', ':fecha_hasta', 'NOW()', ':accion'];

        if ($capitanColumn) {
            $columns[] = $capitanColumn;
            $values[] = ':capitan';
        }
        if ($arqueroColumn) {
            $columns[] = $arqueroColumn;
            $values[] = ':arquero';
        }

        $sql = 'INSERT INTO jugador_equipo_hist (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $values) . ')';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id_jugador_equipo', $idJugadorEquipo, PDO::PARAM_INT);
        $stmt->bindValue(':id_jugador', $idJugador, PDO::PARAM_INT);
        $stmt->bindValue(':id_equipo', $idEquipo, PDO::PARAM_INT);
        $stmt->bindValue(':fecha_desde', $fechaDesde);
        $stmt->bindValue(':fecha_hasta', $fechaHasta);
        $stmt->bindValue(':accion', $accion);
        if ($capitanColumn) {
            $stmt->bindValue(':capitan', $capitan ? 1 : 0, PDO::PARAM_INT);
        }
        if ($arqueroColumn) {
            $stmt->bindValue(':arquero', $arquero ? 1 : 0, PDO::PARAM_INT);
        }
        $stmt->execute();
    }

    private function getRelationById(int $idJugadorEquipo): ?array
    {
        $capitanColumn = $this->getCapitanColumnInRelacion();
        $arqueroColumn = $this->getArqueroColumnInRelacion();
        $capitanSelect = $capitanColumn ? ", COALESCE({$capitanColumn}, 0) AS capitan" : ', 0 AS capitan';
        $arqueroSelect = $arqueroColumn ? ", COALESCE({$arqueroColumn}, 0) AS arquero" : ', 0 AS arquero';

        $sql = "SELECT id, id_jugador, id_equipo, fecha_desde, fecha_hasta
                       {$capitanSelect}
                       {$arqueroSelect}
                FROM jugador_equipo
                WHERE id = :id
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $idJugadorEquipo, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    private function hasCapitanColumnInRelacion(): bool
    {
        return $this->getCapitanColumnInRelacion() !== null;
    }

    private function hasCapitanColumnInHist(): bool
    {
        return $this->getCapitanColumnInHist() !== null;
    }

    private function hasArqueroColumnInRelacion(): bool
    {
        return $this->getArqueroColumnInRelacion() !== null;
    }

    private function hasArqueroColumnInHist(): bool
    {
        return $this->getArqueroColumnInHist() !== null;
    }

    private function getCapitanColumnInRelacion(): ?string
    {
        if ($this->capitanColumnRelacionResolved) {
            return $this->capitanColumnInRelacion;
        }

        $this->capitanColumnRelacionResolved = true;
        $this->capitanColumnInRelacion = $this->resolveCaptainColumnName('jugador_equipo');

        return $this->capitanColumnInRelacion;
    }

    private function getCapitanColumnInHist(): ?string
    {
        if ($this->capitanColumnHistResolved) {
            return $this->capitanColumnInHist;
        }

        $this->capitanColumnHistResolved = true;
        $this->capitanColumnInHist = $this->resolveCaptainColumnName('jugador_equipo_hist');

        return $this->capitanColumnInHist;
    }

    private function getArqueroColumnInRelacion(): ?string
    {
        if ($this->arqueroColumnRelacionResolved) {
            return $this->arqueroColumnInRelacion;
        }

        $this->arqueroColumnRelacionResolved = true;
        $this->arqueroColumnInRelacion = $this->resolveArqueroColumnName('jugador_equipo');

        return $this->arqueroColumnInRelacion;
    }

    private function getArqueroColumnInHist(): ?string
    {
        if ($this->arqueroColumnHistResolved) {
            return $this->arqueroColumnInHist;
        }

        $this->arqueroColumnHistResolved = true;
        $this->arqueroColumnInHist = $this->resolveArqueroColumnName('jugador_equipo_hist');

        return $this->arqueroColumnInHist;
    }

    private function resolveCaptainColumnName(string $tableName): ?string
    {
        try {
            foreach (['capitan', 'es_capitan'] as $candidate) {
                $stmt = $this->conn->query("SHOW COLUMNS FROM {$tableName} LIKE '{$candidate}'");
                if ($stmt !== false && (bool)$stmt->fetch(PDO::FETCH_ASSOC)) {
                    return $candidate;
                }
            }
        } catch (Throwable) {
            return null;
        }

        return null;
    }

    private function resolveArqueroColumnName(string $tableName): ?string
    {
        try {
            foreach (['arquero', 'es_arquero'] as $candidate) {
                $stmt = $this->conn->query("SHOW COLUMNS FROM {$tableName} LIKE '{$candidate}'");
                if ($stmt !== false && (bool)$stmt->fetch(PDO::FETCH_ASSOC)) {
                    return $candidate;
                }
            }
        } catch (Throwable) {
            return null;
        }

        return null;
    }
}