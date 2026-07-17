<?php

declare(strict_types=1);

class InscripcionEquipo
{
    private PDO $conn;
    public string $table = 'inscripcion_equipo';

    // IDs de estado_inscripcion usados por el flujo del portal
    public const ESTADO_PENDIENTE          = 1;
    public const ESTADO_PENDIENTE_PAGO     = 2;
    public const ESTADO_PAGO_EN_REVISION   = 3;
    public const ESTADO_RECHAZADA          = 5;
    public const ESTADO_OBSERVADA          = 7;
    public const ESTADO_APROBADA           = 8;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function getAll(?int $idTorneo = null, ?int $idEstado = null): array
    {
        $where = [];
        if ($idTorneo !== null) {
            $where[] = 'ie.id_torneo = :id_torneo';
        }
        if ($idEstado !== null) {
            $where[] = 'ie.id_estado = :id_estado';
        }
        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT ie.id,
                       ie.id_torneo,
                       t.nombre AS torneo_nombre,
                       ie.nombre_equipo,
                       ie.categoria,
                       (SELECT CONCAT(ij2.nombre, ' ', ij2.apellido)
                        FROM inscripcion_jugador ij2
                        WHERE ij2.id_inscripcion_equipo = ie.id AND ij2.es_capitan = 1
                        LIMIT 1) AS nombre_capitan,
                       (SELECT ij2.telefono
                        FROM inscripcion_jugador ij2
                        WHERE ij2.id_inscripcion_equipo = ie.id AND ij2.es_capitan = 1
                        LIMIT 1) AS telefono_capitan,
                       (SELECT ij2.email
                        FROM inscripcion_jugador ij2
                        WHERE ij2.id_inscripcion_equipo = ie.id AND ij2.es_capitan = 1
                        LIMIT 1) AS email_capitan,
                       ie.id_estado,
                       ei.descripcion AS estado,
                       ie.id_usuario_web_solicitante,
                       uw.email AS email_solicitante,
                       ie.observacion_admin,
                       ie.comprobante_pago,
                       ie.fecha_actualizacion_comprobante_pago,
                       ie.fecha_creacion,
                       ie.fecha_actualizacion,
                       ie.fecha_actualizacion_estado,
                       EXISTS (
                           SELECT 1 FROM inscripcion_jugador ij
                           WHERE ij.id_inscripcion_equipo = ie.id
                             AND ij.fecha_actualizacion_documentacion IS NOT NULL
                             AND ie.fecha_actualizacion_estado IS NOT NULL
                             AND ij.fecha_actualizacion_documentacion > ie.fecha_actualizacion_estado
                       ) AS tiene_docs_nuevas
                FROM {$this->table} ie
                LEFT JOIN torneo t ON t.id = ie.id_torneo
                LEFT JOIN estado_inscripcion ei ON ei.id = ie.id_estado
                LEFT JOIN usuario_web uw ON uw.id = ie.id_usuario_web_solicitante
                {$whereClause}
                ORDER BY ie.fecha_creacion DESC";

        $stmt = $this->conn->prepare($sql);
        if ($idTorneo !== null) {
            $stmt->bindValue(':id_torneo', $idTorneo, PDO::PARAM_INT);
        }
        if ($idEstado !== null) {
            $stmt->bindValue(':id_estado', $idEstado, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $sql = "SELECT ie.id,
                       ie.id_torneo,
                       t.nombre AS torneo_nombre,
                       ie.nombre_equipo,
                       ie.categoria,
                       (SELECT CONCAT(ij2.nombre, ' ', ij2.apellido)
                        FROM inscripcion_jugador ij2
                        WHERE ij2.id_inscripcion_equipo = ie.id AND ij2.es_capitan = 1
                        LIMIT 1) AS nombre_capitan,
                       (SELECT ij2.telefono
                        FROM inscripcion_jugador ij2
                        WHERE ij2.id_inscripcion_equipo = ie.id AND ij2.es_capitan = 1
                        LIMIT 1) AS telefono_capitan,
                       (SELECT ij2.email
                        FROM inscripcion_jugador ij2
                        WHERE ij2.id_inscripcion_equipo = ie.id AND ij2.es_capitan = 1
                        LIMIT 1) AS email_capitan,
                       ie.id_estado,
                       ei.descripcion AS estado,
                       ie.id_usuario_web_solicitante,
                       uw.email AS email_solicitante,
                       ie.observacion_admin,
                       ie.comprobante_pago,
                       ie.fecha_actualizacion_comprobante_pago,
                       ie.fecha_creacion,
                       ie.fecha_actualizacion,
                       ie.fecha_actualizacion_estado
                FROM {$this->table} ie
                LEFT JOIN torneo t ON t.id = ie.id_torneo
                LEFT JOIN estado_inscripcion ei ON ei.id = ie.id_estado
                LEFT JOIN usuario_web uw ON uw.id = ie.id_usuario_web_solicitante
                WHERE ie.id = :id
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function create(int $idTorneo, string $nombreEquipo, int $idUsuarioWebSolicitante): int|false
    {
        $sql = "INSERT INTO {$this->table} (id_torneo, nombre_equipo, id_estado, id_usuario_web_solicitante, fecha_creacion)
                VALUES (:id_torneo, :nombre_equipo, :id_estado, :id_usuario_web_solicitante, NOW())";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id_torneo', $idTorneo, PDO::PARAM_INT);
        $stmt->bindValue(':nombre_equipo', $nombreEquipo);
        $stmt->bindValue(':id_estado', self::ESTADO_PENDIENTE, PDO::PARAM_INT);
        $stmt->bindValue(':id_usuario_web_solicitante', $idUsuarioWebSolicitante, PDO::PARAM_INT);
        if ($stmt->execute()) {
            return (int)$this->conn->lastInsertId();
        }
        return false;
    }

    public function update(int $id, string $nombreEquipo): bool
    {
        $sql = "UPDATE {$this->table}
                SET nombre_equipo = :nombre_equipo,
                    fecha_actualizacion = NOW()
                WHERE id = :id AND id_estado IN (:pendiente, :observada)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':nombre_equipo', $nombreEquipo);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':pendiente', self::ESTADO_PENDIENTE, PDO::PARAM_INT);
        $stmt->bindValue(':observada', self::ESTADO_OBSERVADA, PDO::PARAM_INT);
        return $stmt->execute() && $stmt->rowCount() > 0;
    }

    public function cambiarEstado(int $id, int $idEstado, ?string $observacion = null): bool
    {
        $sql = "UPDATE {$this->table}
                SET id_estado = :id_estado,
                    observacion_admin = :observacion,
                    fecha_actualizacion = NOW(),
                    fecha_actualizacion_estado = NOW()
                WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id_estado', $idEstado, PDO::PARAM_INT);
        $stmt->bindValue(':observacion', $observacion);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
