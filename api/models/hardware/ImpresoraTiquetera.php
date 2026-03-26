<?php

declare(strict_types=1);

class ImpresoraTiquetera
{
    private PDO $conn;
    private string $table = 'impresoras_tiquetera';

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function getAll(): array
    {
        $stmt = $this->conn->prepare(
            "SELECT id, nombre, comando_corte, lineas_avance, es_default, descripcion, fecha_modificacion
             FROM {$this->table}
             ORDER BY es_default DESC, nombre ASC"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->conn->prepare(
            "SELECT id, nombre, comando_corte, lineas_avance, es_default, descripcion, fecha_modificacion
             FROM {$this->table}
             WHERE id = :id"
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function getDefault(): ?array
    {
        $stmt = $this->conn->prepare(
            "SELECT id, nombre, comando_corte, lineas_avance, es_default, descripcion
             FROM {$this->table}
             WHERE es_default = 1
             LIMIT 1"
        );
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function create(array $data): bool
    {
        if (!empty($data['es_default'])) {
            $this->clearDefault();
        }

        $stmt = $this->conn->prepare(
            "INSERT INTO {$this->table} (nombre, comando_corte, lineas_avance, es_default, descripcion)
             VALUES (:nombre, :comando_corte, :lineas_avance, :es_default, :descripcion)"
        );
        $stmt->bindValue(':nombre',        $data['nombre'],                          PDO::PARAM_STR);
        $stmt->bindValue(':comando_corte', $data['comando_corte'],                   PDO::PARAM_STR);
        $stmt->bindValue(':lineas_avance', (int)($data['lineas_avance'] ?? 5),       PDO::PARAM_INT);
        $stmt->bindValue(':es_default',    empty($data['es_default']) ? 0 : 1,       PDO::PARAM_INT);
        $stmt->bindValue(':descripcion',   $data['descripcion'] ?? null,             PDO::PARAM_STR);
        return $stmt->execute();
    }

    public function update(int $id, array $data): bool
    {
        if (!empty($data['es_default'])) {
            $this->clearDefault();
        }

        $stmt = $this->conn->prepare(
            "UPDATE {$this->table}
             SET nombre         = :nombre,
                 comando_corte  = :comando_corte,
                 lineas_avance  = :lineas_avance,
                 es_default     = :es_default,
                 descripcion    = :descripcion
             WHERE id = :id"
        );
        $stmt->bindValue(':id',            $id,                                      PDO::PARAM_INT);
        $stmt->bindValue(':nombre',        $data['nombre'],                          PDO::PARAM_STR);
        $stmt->bindValue(':comando_corte', $data['comando_corte'],                   PDO::PARAM_STR);
        $stmt->bindValue(':lineas_avance', (int)($data['lineas_avance'] ?? 5),       PDO::PARAM_INT);
        $stmt->bindValue(':es_default',    empty($data['es_default']) ? 0 : 1,       PDO::PARAM_INT);
        $stmt->bindValue(':descripcion',   $data['descripcion'] ?? null,             PDO::PARAM_STR);
        return $stmt->execute();
    }

    public function delete(int $id): bool
    {
        $stmt = $this->conn->prepare("DELETE FROM {$this->table} WHERE id = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    private function clearDefault(): void
    {
        $this->conn->exec("UPDATE {$this->table} SET es_default = 0");
    }
}
