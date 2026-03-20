<?php

declare(strict_types=1);

class QzCertificado
{
    private PDO $conn;
    private string $table = 'qz_certificados';

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function getAll(): array
    {
        $stmt = $this->conn->prepare(
            "SELECT id, machine_id, nombre_maquina, cert_filename, pk_filename, fecha_modificacion
             FROM {$this->table}
             ORDER BY fecha_modificacion DESC"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByMachineId(string $machineId): ?array
    {
        $stmt = $this->conn->prepare(
            "SELECT id, machine_id, nombre_maquina, cert_filename, pk_filename
             FROM {$this->table}
             WHERE machine_id = :machine_id"
        );
        $stmt->bindValue(':machine_id', $machineId, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Inserta o actualiza el registro para la machine_id dada.
     */
    public function upsert(
        string $machineId,
        string $nombreMaquina,
        string $certFilename,
        string $pkFilename
    ): bool {
        $stmt = $this->conn->prepare(
            "INSERT INTO {$this->table} (machine_id, nombre_maquina, cert_filename, pk_filename)
             VALUES (:machine_id, :nombre_maquina, :cert_filename, :pk_filename)
             ON DUPLICATE KEY UPDATE
               nombre_maquina  = VALUES(nombre_maquina),
               cert_filename   = VALUES(cert_filename),
               pk_filename     = VALUES(pk_filename)"
        );
        $stmt->bindValue(':machine_id',     $machineId,     PDO::PARAM_STR);
        $stmt->bindValue(':nombre_maquina', $nombreMaquina, PDO::PARAM_STR);
        $stmt->bindValue(':cert_filename',  $certFilename,  PDO::PARAM_STR);
        $stmt->bindValue(':pk_filename',    $pkFilename,    PDO::PARAM_STR);
        return $stmt->execute();
    }

    /**
     * Elimina el registro y devuelve los nombres de archivos para borrarlos del disco.
     * Retorna null si no existe el id.
     */
    public function deleteById(int $id): ?array
    {
        $stmt = $this->conn->prepare(
            "SELECT cert_filename, pk_filename FROM {$this->table} WHERE id = :id"
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$record) {
            return null;
        }

        $del = $this->conn->prepare("DELETE FROM {$this->table} WHERE id = :id");
        $del->bindValue(':id', $id, PDO::PARAM_INT);
        $del->execute();

        return $record;
    }
}
