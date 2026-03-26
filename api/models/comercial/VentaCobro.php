<?php

declare(strict_types=1);

class VentaCobro
{
    private PDO $conn;
    public string $table = 'venta_cobro';

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    /**
     * Obtiene todos los registros de venta-cobro
     */
    public function getAll(): array
    {
        $sql = "SELECT vc.id_venta_cobro, vc.id_venta, vc.id_cobro, vc.id_medio_pago, vc.monto,
                       v.fecha AS venta_fecha, v.descripcion_cliente,
                       mc.descripcion AS medio_pago_descripcion
                FROM {$this->table} vc
                INNER JOIN venta v ON vc.id_venta = v.id
                INNER JOIN medio_cobro mc ON vc.id_medio_pago = mc.id
                ORDER BY vc.id_venta_cobro DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene un registro de venta-cobro por ID
     */
    public function getById(int $id): ?array
    {
        $sql = "SELECT vc.id_venta_cobro, vc.id_venta, vc.id_cobro, vc.id_medio_pago, vc.monto,
                       v.fecha AS venta_fecha, v.descripcion_cliente,
                       mc.descripcion AS medio_pago_descripcion
                FROM {$this->table} vc
                INNER JOIN venta v ON vc.id_venta = v.id
                INNER JOIN medio_cobro mc ON vc.id_medio_pago = mc.id
                WHERE vc.id_venta_cobro = :id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Obtiene registros por venta
     */
    public function getByVenta(int $idVenta): array
    {
        $sql = "SELECT vc.id_venta_cobro, vc.id_venta, vc.id_cobro, vc.id_medio_pago, vc.monto,
                       mc.descripcion AS medio_pago_descripcion
                FROM {$this->table} vc
                INNER JOIN medio_cobro mc ON vc.id_medio_pago = mc.id
                WHERE vc.id_venta = :id_venta
                ORDER BY vc.id_venta_cobro ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id_venta', $idVenta, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene registros por cobro
     */
    public function getByCobro(int $idCobro): array
    {
        $sql = "SELECT vc.id_venta_cobro, vc.id_venta, vc.id_cobro, vc.id_medio_pago, vc.monto,
                       v.fecha AS venta_fecha,
                       mc.descripcion AS medio_pago_descripcion
                FROM {$this->table} vc
                INNER JOIN venta v ON vc.id_venta = v.id
                INNER JOIN medio_cobro mc ON vc.id_medio_pago = mc.id
                WHERE vc.id_cobro = :id_cobro
                ORDER BY vc.id_venta_cobro ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id_cobro', $idCobro, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Crea un nuevo registro de venta-cobro
     */
    public function create(int $id, int $idVenta, int $idCobro, int $idMedioPago, float $monto): bool
    {
        $sql = "INSERT INTO {$this->table} (id_venta_cobro, id_venta, id_cobro, id_medio_pago, monto) 
                VALUES (:id, :id_venta, :id_cobro, :id_medio_pago, :monto)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':id_venta', $idVenta, PDO::PARAM_INT);
        $stmt->bindValue(':id_cobro', $idCobro, PDO::PARAM_INT);
        $stmt->bindValue(':id_medio_pago', $idMedioPago, PDO::PARAM_INT);
        $stmt->bindValue(':monto', $monto);
        return $stmt->execute();
    }

    /**
     * Actualiza un registro de venta-cobro
     */
    public function update(int $id, int $idVenta, int $idCobro, int $idMedioPago, float $monto): bool
    {
        $sql = "UPDATE {$this->table} 
                SET id_venta = :id_venta, 
                    id_cobro = :id_cobro, 
                    id_medio_pago = :id_medio_pago, 
                    monto = :monto 
                WHERE id_venta_cobro = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id_venta', $idVenta, PDO::PARAM_INT);
        $stmt->bindValue(':id_cobro', $idCobro, PDO::PARAM_INT);
        $stmt->bindValue(':id_medio_pago', $idMedioPago, PDO::PARAM_INT);
        $stmt->bindValue(':monto', $monto);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Elimina un registro de venta-cobro
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE id_venta_cobro = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
