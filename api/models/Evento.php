<?php

declare(strict_types=1);

class Evento
{
    private PDO $conn;
    public string $table = 'evento';

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function getAll(): array
    {
        $sql = "SELECT ev.id, ev.id_torneo, ev.id_estado_evento, ev.tipo_evento, ev.titulo, ev.descripcion,
                       ev.numero_fecha, ev.fecha_hora_inicio, ev.fecha_hora_fin, ev.id_cancha, ev.id_arbitro,
                       ev.id_equipo_local, ev.id_equipo_visitante, ev.resultado_local, ev.resultado_visitante,
                       ev.resultado_penales_local, ev.resultado_penales_visitante,
                       ee.descripcion AS estado_evento_descripcion,
                       a.nombre AS arbitro_nombre, a.apellido AS arbitro_apellido,
                       el.nombre AS equipo_local_nombre,
                       evt.nombre AS equipo_visitante_nombre,
                       c.nombre AS cancha_nombre,
                       t.nombre AS torneo_nombre
                FROM {$this->table} ev
                LEFT JOIN estado_evento ee ON ev.id_estado_evento = ee.id
                LEFT JOIN arbitro a ON ev.id_arbitro = a.id
                LEFT JOIN equipo el ON ev.id_equipo_local = el.id
                LEFT JOIN equipo evt ON ev.id_equipo_visitante = evt.id
                LEFT JOIN cancha c ON ev.id_cancha = c.id
                LEFT JOIN torneo t ON ev.id_torneo = t.id
                ORDER BY ev.fecha_hora_inicio DESC, ev.id DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $sql = "SELECT ev.id, ev.id_torneo, ev.id_estado_evento, ev.tipo_evento, ev.titulo, ev.descripcion,
                       ev.numero_fecha, ev.fecha_hora_inicio, ev.fecha_hora_fin, ev.id_cancha, ev.id_arbitro,
                       ev.id_equipo_local, ev.id_equipo_visitante, ev.resultado_local, ev.resultado_visitante,
                       ev.resultado_penales_local, ev.resultado_penales_visitante,
                       ee.descripcion AS estado_evento_descripcion,
                       a.nombre AS arbitro_nombre, a.apellido AS arbitro_apellido,
                       el.nombre AS equipo_local_nombre,
                       evt.nombre AS equipo_visitante_nombre,
                       c.nombre AS cancha_nombre,
                       t.nombre AS torneo_nombre
                FROM {$this->table} ev
                LEFT JOIN estado_evento ee ON ev.id_estado_evento = ee.id
                LEFT JOIN arbitro a ON ev.id_arbitro = a.id
                LEFT JOIN equipo el ON ev.id_equipo_local = el.id
                LEFT JOIN equipo evt ON ev.id_equipo_visitante = evt.id
                LEFT JOIN cancha c ON ev.id_cancha = c.id
                LEFT JOIN torneo t ON ev.id_torneo = t.id
                WHERE ev.id = :id
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function getByTipo(string $tipoEvento): array
    {
        $sql = "SELECT id, id_torneo, id_estado_evento, tipo_evento, titulo, descripcion,
                       numero_fecha, fecha_hora_inicio, fecha_hora_fin, id_cancha, id_arbitro,
                       id_equipo_local, id_equipo_visitante, resultado_local, resultado_visitante,
                       resultado_penales_local, resultado_penales_visitante
                FROM {$this->table}
                WHERE tipo_evento = :tipo_evento
                ORDER BY fecha_hora_inicio DESC, id DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':tipo_evento', $tipoEvento);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): int|false
    {
        $sql = "INSERT INTO {$this->table} (
                    id_torneo, id_estado_evento, tipo_evento, titulo, descripcion, numero_fecha,
                    fecha_hora_inicio, fecha_hora_fin, id_cancha, id_arbitro, id_equipo_local,
                    id_equipo_visitante, resultado_local, resultado_visitante,
                    resultado_penales_local, resultado_penales_visitante
                ) VALUES (
                    :id_torneo, :id_estado_evento, :tipo_evento, :titulo, :descripcion, :numero_fecha,
                    :fecha_hora_inicio, :fecha_hora_fin, :id_cancha, :id_arbitro, :id_equipo_local,
                    :id_equipo_visitante, :resultado_local, :resultado_visitante,
                    :resultado_penales_local, :resultado_penales_visitante
                )";
        $stmt = $this->conn->prepare($sql);
        $this->bindData($stmt, $data);

        if ($stmt->execute()) {
            return (int)$this->conn->lastInsertId();
        }

        return false;
    }

    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE {$this->table}
                SET id_torneo = :id_torneo,
                    id_estado_evento = :id_estado_evento,
                    tipo_evento = :tipo_evento,
                    titulo = :titulo,
                    descripcion = :descripcion,
                    numero_fecha = :numero_fecha,
                    fecha_hora_inicio = :fecha_hora_inicio,
                    fecha_hora_fin = :fecha_hora_fin,
                    id_cancha = :id_cancha,
                    id_arbitro = :id_arbitro,
                    id_equipo_local = :id_equipo_local,
                    id_equipo_visitante = :id_equipo_visitante,
                    resultado_local = :resultado_local,
                    resultado_visitante = :resultado_visitante,
                    resultado_penales_local = :resultado_penales_local,
                    resultado_penales_visitante = :resultado_penales_visitante
                WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $this->bindData($stmt, $data);
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

    private function bindData(PDOStatement $stmt, array $data): void
    {
        $stmt->bindValue(':id_torneo', $data['id_torneo'], $data['id_torneo'] === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':id_estado_evento', $data['id_estado_evento'], PDO::PARAM_INT);
        $stmt->bindValue(':tipo_evento', $data['tipo_evento']);
        $stmt->bindValue(':titulo', $data['titulo']);
        $stmt->bindValue(':descripcion', $data['descripcion']);
        $stmt->bindValue(':numero_fecha', $data['numero_fecha'], $data['numero_fecha'] === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':fecha_hora_inicio', $data['fecha_hora_inicio']);
        $stmt->bindValue(':fecha_hora_fin', $data['fecha_hora_fin']);
        $stmt->bindValue(':id_cancha', $data['id_cancha'], $data['id_cancha'] === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':id_arbitro', $data['id_arbitro'], $data['id_arbitro'] === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':id_equipo_local', $data['id_equipo_local'], $data['id_equipo_local'] === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':id_equipo_visitante', $data['id_equipo_visitante'], $data['id_equipo_visitante'] === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':resultado_local', $data['resultado_local'], $data['resultado_local'] === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':resultado_visitante', $data['resultado_visitante'], $data['resultado_visitante'] === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':resultado_penales_local', $data['resultado_penales_local'], $data['resultado_penales_local'] === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':resultado_penales_visitante', $data['resultado_penales_visitante'], $data['resultado_penales_visitante'] === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    }
}