<?php

declare(strict_types=1);

class InscripcionJugador
{
    private PDO $conn;
    public string $table = 'inscripcion_jugador';

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function getByInscripcion(int $idInscripcionEquipo): array
    {
        $sql = "SELECT id, id_inscripcion_equipo, nombre, apellido, dni, fecha_nac,
                       email, telefono, archivo_documentacion, estado_documentacion,
                       fecha_actualizacion_documentacion, es_capitan, es_arquero
                FROM {$this->table}
                WHERE id_inscripcion_equipo = :id_inscripcion_equipo
                ORDER BY es_capitan DESC, apellido ASC, nombre ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id_inscripcion_equipo', $idInscripcionEquipo, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $sql = "SELECT id, id_inscripcion_equipo, nombre, apellido, dni, fecha_nac,
                       email, telefono, archivo_documentacion, estado_documentacion,
                       es_capitan, es_arquero
                FROM {$this->table}
                WHERE id = :id
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function create(int $idInscripcionEquipo, string $nombre, string $apellido, string $dni, ?string $fechaNac, ?string $email, ?string $telefono): int|false
    {
        $sql = "INSERT INTO {$this->table}
                    (id_inscripcion_equipo, nombre, apellido, dni, fecha_nac, email, telefono)
                VALUES
                    (:id_inscripcion_equipo, :nombre, :apellido, :dni, :fecha_nac, :email, :telefono)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id_inscripcion_equipo', $idInscripcionEquipo, PDO::PARAM_INT);
        $stmt->bindValue(':nombre', $nombre);
        $stmt->bindValue(':apellido', $apellido);
        $stmt->bindValue(':dni', $dni);
        $stmt->bindValue(':fecha_nac', $fechaNac);
        $stmt->bindValue(':email', $email);
        $stmt->bindValue(':telefono', $telefono);
        if ($stmt->execute()) {
            return (int)$this->conn->lastInsertId();
        }
        return false;
    }

    public function update(int $id, string $nombre, string $apellido, string $dni, ?string $fechaNac, ?string $email, ?string $telefono): bool
    {
        $sql = "UPDATE {$this->table}
                SET nombre = :nombre,
                    apellido = :apellido,
                    dni = :dni,
                    fecha_nac = :fecha_nac,
                    email = :email,
                    telefono = :telefono
                WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':nombre', $nombre);
        $stmt->bindValue(':apellido', $apellido);
        $stmt->bindValue(':dni', $dni);
        $stmt->bindValue(':fecha_nac', $fechaNac);
        $stmt->bindValue(':email', $email);
        $stmt->bindValue(':telefono', $telefono);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function setDocumentacion(int $id, string $ruta): bool
    {
        $sql = "UPDATE {$this->table} SET archivo_documentacion = :ruta WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':ruta', $ruta);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function setEstadoDocumentacion(int $id, string $estado): bool
    {
        $sql = "UPDATE {$this->table}
                SET estado_documentacion = :estado,
                    fecha_actualizacion_documentacion = NOW()
                WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':estado', $estado);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
