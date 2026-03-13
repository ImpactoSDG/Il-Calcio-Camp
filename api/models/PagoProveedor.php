<?php

declare(strict_types=1);

class PagoProveedor
{
    private PDO $conn;
    public string $table = 'pago_proveedor';

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function getAll(): array
    {
        $sql = "SELECT pp.id_pago_proveedor, pp.id_proveedor, pp.fecha_pago,
                       pp.monto, pp.id_medio_cobro, pp.observacion,
                       CONCAT_WS(' ', p.nombre, p.apellido) AS proveedor_nombre,
                       p.nombre_fantasia AS proveedor_fantasia,
                       mc.descripcion AS medio_cobro_descripcion
                FROM {$this->table} pp
                INNER JOIN proveedor p ON pp.id_proveedor = p.id_proveedor
                INNER JOIN medio_cobro mc ON pp.id_medio_cobro = mc.id
                ORDER BY pp.fecha_pago DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByProveedor(int $idProveedor): array
    {
        $sql = "SELECT pp.id_pago_proveedor, pp.id_proveedor, pp.fecha_pago,
                       pp.monto, pp.id_medio_cobro, pp.observacion,
                       mc.descripcion AS medio_cobro_descripcion
                FROM {$this->table} pp
                INNER JOIN medio_cobro mc ON pp.id_medio_cobro = mc.id
                WHERE pp.id_proveedor = :id_proveedor
                ORDER BY pp.fecha_pago DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id_proveedor', $idProveedor, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $sql = "SELECT pp.id_pago_proveedor, pp.id_proveedor, pp.fecha_pago,
                       pp.monto, pp.id_medio_cobro, pp.observacion,
                       CONCAT_WS(' ', p.nombre, p.apellido) AS proveedor_nombre,
                       p.nombre_fantasia AS proveedor_fantasia,
                       mc.descripcion AS medio_cobro_descripcion
                FROM {$this->table} pp
                INNER JOIN proveedor p ON pp.id_proveedor = p.id_proveedor
                INNER JOIN medio_cobro mc ON pp.id_medio_cobro = mc.id
                WHERE pp.id_pago_proveedor = :id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function create(int $idProveedor, float $monto, int $idMedioCobro, ?string $observacion): int|false
    {
        $sql = "INSERT INTO {$this->table} (id_proveedor, monto, id_medio_cobro, observacion)
                VALUES (:id_proveedor, :monto, :id_medio_cobro, :observacion)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id_proveedor', $idProveedor, PDO::PARAM_INT);
        $stmt->bindValue(':monto', $monto);
        $stmt->bindValue(':id_medio_cobro', $idMedioCobro, PDO::PARAM_INT);
        $stmt->bindValue(':observacion', $observacion);
        if ($stmt->execute()) {
            return (int)$this->conn->lastInsertId();
        }
        return false;
    }

    public function delete(int $id): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE id_pago_proveedor = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
