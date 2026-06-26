<?php

declare(strict_types=1);

class EventoPartido
{
    private PDO $conn;
    public string $table = 'evento_partido';

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function getAll(?int $idEvento = null, ?int $idTorneo = null): array
    {
        $sql = "SELECT ep.id, ep.id_evento, ep.id_tipo_evento_partido, ep.id_jugador, ep.id_equipo,
                       ep.minuto, ep.observacion,
                       tep.descripcion AS tipo_evento_partido_descripcion,
                       j.nombre AS jugador_nombre,
                       j.apellido AS jugador_apellido,
                       e.nombre AS equipo_nombre
                FROM {$this->table} ep
                INNER JOIN tipo_evento_partido tep ON ep.id_tipo_evento_partido = tep.id
                LEFT JOIN jugador j ON ep.id_jugador = j.id
                LEFT JOIN equipo e ON ep.id_equipo = e.id";

        $where = [];
        if ($idEvento !== null) {
            $where[] = "ep.id_evento = :id_evento";
        }
        if ($idTorneo !== null) {
            $sql .= " INNER JOIN evento ev ON ep.id_evento = ev.id";
            $where[] = "ev.id_torneo = :id_torneo";
        }
        if ($where) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        $sql .= " ORDER BY ep.id DESC";

        $stmt = $this->conn->prepare($sql);
        if ($idEvento !== null) {
            $stmt->bindValue(':id_evento', $idEvento, PDO::PARAM_INT);
        }
        if ($idTorneo !== null) {
            $stmt->bindValue(':id_torneo', $idTorneo, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $sql = "SELECT ep.id, ep.id_evento, ep.id_tipo_evento_partido, ep.id_jugador, ep.id_equipo,
                       ep.minuto, ep.observacion,
                       tep.descripcion AS tipo_evento_partido_descripcion,
                       j.nombre AS jugador_nombre,
                       j.apellido AS jugador_apellido,
                       e.nombre AS equipo_nombre
                FROM {$this->table} ep
                INNER JOIN tipo_evento_partido tep ON ep.id_tipo_evento_partido = tep.id
                LEFT JOIN jugador j ON ep.id_jugador = j.id
                LEFT JOIN equipo e ON ep.id_equipo = e.id
                WHERE ep.id = :id
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function create(array $data): int|false
    {
        $sql = "INSERT INTO {$this->table}
                (id_evento, id_tipo_evento_partido, id_jugador, id_equipo, minuto, observacion)
                VALUES
                (:id_evento, :id_tipo_evento_partido, :id_jugador, :id_equipo, :minuto, :observacion)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id_evento', $data['id_evento'], PDO::PARAM_INT);
        $stmt->bindValue(':id_tipo_evento_partido', $data['id_tipo_evento_partido'], PDO::PARAM_INT);
        $stmt->bindValue(':id_jugador', $data['id_jugador'], $data['id_jugador'] === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':id_equipo', $data['id_equipo'], $data['id_equipo'] === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':minuto', $data['minuto'], $data['minuto'] === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':observacion', $data['observacion']);

        if ($stmt->execute()) {
            return (int)$this->conn->lastInsertId();
        }
        return false;
    }

    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE {$this->table}
                SET id_evento = :id_evento,
                    id_tipo_evento_partido = :id_tipo_evento_partido,
                    id_jugador = :id_jugador,
                    id_equipo = :id_equipo,
                    minuto = :minuto,
                    observacion = :observacion
                WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id_evento', $data['id_evento'], PDO::PARAM_INT);
        $stmt->bindValue(':id_tipo_evento_partido', $data['id_tipo_evento_partido'], PDO::PARAM_INT);
        $stmt->bindValue(':id_jugador', $data['id_jugador'], $data['id_jugador'] === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':id_equipo', $data['id_equipo'], $data['id_equipo'] === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':minuto', $data['minuto'], $data['minuto'] === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':observacion', $data['observacion']);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
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