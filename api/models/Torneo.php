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
                       d.nombre AS disciplina_nombre,
                       et.descripcion AS estado_torneo_descripcion
                FROM {$this->table} t
                LEFT JOIN disciplina d ON t.id_disciplina = d.id
                LEFT JOIN estado_torneo et ON t.id_estado_torneo = et.id
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
                       d.nombre AS disciplina_nombre,
                       et.descripcion AS estado_torneo_descripcion
                FROM {$this->table} t
                LEFT JOIN disciplina d ON t.id_disciplina = d.id
                LEFT JOIN estado_torneo et ON t.id_estado_torneo = et.id
                WHERE t.id = :id
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }
}