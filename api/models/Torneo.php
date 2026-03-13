<?php

declare(strict_types=1);

class Torneo
{
    private PDO $conn;
    public string $table = 'torneo';

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function getAll(): array
    {
        $sql = "SELECT t.id, t.nombre, t.descripcion, t.id_disciplina, t.id_estado_torneo,
                       t.fecha_inicio, t.fecha_fin, t.fecha_fin_planificada, t.cupo_equipos,
                       t.valor_inscripcion, t.formato_manual, t.configuracion_json,
                       COALESCE(t.activo, 1) AS activo,
                       d.nombre AS disciplina_nombre,
                       et.descripcion AS estado_torneo_descripcion
                FROM {$this->table} t
                LEFT JOIN disciplina d ON t.id_disciplina = d.id
                LEFT JOIN estado_torneo et ON t.id_estado_torneo = et.id
                WHERE COALESCE(t.activo, 1) = 1
                ORDER BY t.nombre ASC, t.id ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $sql = "SELECT t.id, t.nombre, t.descripcion, t.id_disciplina, t.id_estado_torneo,
                       t.fecha_inicio, t.fecha_fin, t.fecha_fin_planificada, t.cupo_equipos,
                       t.valor_inscripcion, t.formato_manual, t.configuracion_json,
                       COALESCE(t.activo, 1) AS activo,
                       d.nombre AS disciplina_nombre,
                       et.descripcion AS estado_torneo_descripcion
                FROM {$this->table} t
                LEFT JOIN disciplina d ON t.id_disciplina = d.id
                LEFT JOIN estado_torneo et ON t.id_estado_torneo = et.id
                WHERE t.id = :id
                  AND COALESCE(t.activo, 1) = 1
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function exists(int $id, bool $onlyActive = true): bool
    {
        $sql = "SELECT 1 FROM {$this->table} WHERE id = :id";
        if ($onlyActive) {
            $sql .= " AND COALESCE(activo, 1) = 1";
        }
        $sql .= " LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return (bool)$stmt->fetchColumn();
    }

    public function softDelete(int $idTorneo, ?int $deletedBy = null, ?string $motivo = null): bool
    {
        $sql = "UPDATE {$this->table}
                SET activo = 0,
                    deleted_at = NOW(),
                    deleted_by = :deleted_by,
                    motivo_baja = :motivo_baja
                WHERE id = :id
                  AND COALESCE(activo, 1) = 1";
        $stmt = $this->conn->prepare($sql);
        if ($deletedBy !== null) {
            $stmt->bindValue(':deleted_by', $deletedBy, PDO::PARAM_INT);
        } else {
            $stmt->bindValue(':deleted_by', null, PDO::PARAM_NULL);
        }
        if ($motivo !== null && trim($motivo) !== '') {
            $stmt->bindValue(':motivo_baja', trim($motivo));
        } else {
            $stmt->bindValue(':motivo_baja', null, PDO::PARAM_NULL);
        }
        $stmt->bindValue(':id', $idTorneo, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    public function deleteCascade(int $idTorneo): array
    {
        $this->conn->beginTransaction();
        try {
            $idsFase = $this->getIdsFase($idTorneo);
            $idsGrupo = $this->getIdsGrupo($idsFase);
            $idsEvento = $this->getIdsEvento($idTorneo);

            $deleted = [
                'evento_partido' => 0,
                'estado_evento_hist' => 0,
                'cruce_torneo' => 0,
                'grupo_torneo_equipo' => 0,
                'grupo_torneo' => 0,
                'fase_torneo' => 0,
                'evento' => 0,
                'equipo_torneo' => 0,
                'estado_torneo_hist' => 0,
                'generacion_fixture' => 0,
                'torneo' => 0,
            ];

            if (!empty($idsEvento)) {
                $deleted['evento_partido'] = $this->deleteIn('evento_partido', 'id_evento', $idsEvento);
                $deleted['estado_evento_hist'] = $this->deleteIn('estado_evento_hist', 'id_evento', $idsEvento);
            }

            if (!empty($idsFase) || !empty($idsEvento)) {
                $deleted['cruce_torneo'] = $this->deleteCruces($idsFase, $idsEvento);
            }

            if (!empty($idsGrupo)) {
                $deleted['grupo_torneo_equipo'] = $this->deleteIn('grupo_torneo_equipo', 'id_grupo_torneo', $idsGrupo);
                $deleted['grupo_torneo'] = $this->deleteIn('grupo_torneo', 'id', $idsGrupo);
            }

            if (!empty($idsFase)) {
                $deleted['fase_torneo'] = $this->deleteIn('fase_torneo', 'id', $idsFase);
            }

            $deleted['evento'] = $this->deleteByTournament('evento', $idTorneo);
            $deleted['equipo_torneo'] = $this->deleteByTournament('equipo_torneo', $idTorneo);
            $deleted['estado_torneo_hist'] = $this->deleteByTournament('estado_torneo_hist', $idTorneo);
            $deleted['generacion_fixture'] = $this->deleteByTournament('generacion_fixture', $idTorneo);
            $deleted['torneo'] = $this->deleteByTournament($this->table, $idTorneo);

            $this->conn->commit();
            return $deleted;
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $e;
        }
    }

    private function getIdsFase(int $idTorneo): array
    {
        $stmt = $this->conn->prepare('SELECT id FROM fase_torneo WHERE id_torneo = :id_torneo');
        $stmt->bindValue(':id_torneo', $idTorneo, PDO::PARAM_INT);
        $stmt->execute();
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    private function getIdsGrupo(array $idsFase): array
    {
        if (empty($idsFase)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($idsFase), '?'));
        $stmt = $this->conn->prepare("SELECT id FROM grupo_torneo WHERE id_fase_torneo IN ($placeholders)");
        foreach ($idsFase as $i => $idFase) {
            $stmt->bindValue($i + 1, $idFase, PDO::PARAM_INT);
        }
        $stmt->execute();
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    private function getIdsEvento(int $idTorneo): array
    {
        $stmt = $this->conn->prepare('SELECT id FROM evento WHERE id_torneo = :id_torneo');
        $stmt->bindValue(':id_torneo', $idTorneo, PDO::PARAM_INT);
        $stmt->execute();
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    private function deleteByTournament(string $table, int $idTorneo): int
    {
        $stmt = $this->conn->prepare("DELETE FROM {$table} WHERE id_torneo = :id_torneo");
        $stmt->bindValue(':id_torneo', $idTorneo, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount();
    }

    private function deleteIn(string $table, string $column, array $ids): int
    {
        if (empty($ids)) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->conn->prepare("DELETE FROM {$table} WHERE {$column} IN ($placeholders)");
        foreach ($ids as $i => $id) {
            $stmt->bindValue($i + 1, $id, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->rowCount();
    }

    private function deleteCruces(array $idsFase, array $idsEvento): int
    {
        $conditions = [];
        $params = [];

        if (!empty($idsFase)) {
            $conditions[] = 'id_fase_torneo IN (' . implode(',', array_fill(0, count($idsFase), '?')) . ')';
            $params = array_merge($params, $idsFase);
        }

        if (!empty($idsEvento)) {
            $conditions[] = 'id_evento IN (' . implode(',', array_fill(0, count($idsEvento), '?')) . ')';
            $params = array_merge($params, $idsEvento);
        }

        if (empty($conditions)) {
            return 0;
        }

        $sql = 'DELETE FROM cruce_torneo WHERE ' . implode(' OR ', $conditions);
        $stmt = $this->conn->prepare($sql);
        foreach ($params as $i => $param) {
            $stmt->bindValue($i + 1, $param, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->rowCount();
    }
}