<?php

declare(strict_types=1);

class Cobro
{
    private PDO $conn;
    public string $table = 'cobro';

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    /**
     * Obtiene todos los cobros con información del cliente
     */
    public function getAll(): array
    {
        $sql = "SELECT c.id, c.cliente_id,
                       cl.nombre_cliente AS cliente_nombre
                FROM {$this->table} c
                INNER JOIN cliente cl ON c.cliente_id = cl.id
                ORDER BY c.id DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene un cobro por ID
     */
    public function getById(int $id): ?array
    {
        $sql = "SELECT c.id, c.cliente_id,
                       cl.nombre_cliente AS cliente_nombre
                FROM {$this->table} c
                INNER JOIN cliente cl ON c.cliente_id = cl.id
                WHERE c.id = :id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Obtiene cobros por cliente
     */
    public function getByCliente(int $idCliente): array
    {
        $sql = "SELECT id, cliente_id FROM {$this->table} WHERE cliente_id = :cliente_id ORDER BY id DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':cliente_id', $idCliente, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene las ventas asociadas a un cobro
     */
    public function getVentas(int $idCobro): array
    {
        $sql = "SELECT vc.id_venta_cobro, vc.id_venta, vc.id_medio_pago, vc.monto,
                       v.fecha AS venta_fecha, v.descripcion_cliente,
                       mc.descripcion AS medio_pago_descripcion
                FROM venta_cobro vc
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
     * Crea un nuevo cobro
     */
    public function create(int $id, int $clienteId): bool
    {
        $sql = "INSERT INTO {$this->table} (id, cliente_id) VALUES (:id, :cliente_id)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':cliente_id', $clienteId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Actualiza un cobro
     */
    public function update(int $id, int $clienteId): bool
    {
        $sql = "UPDATE {$this->table} SET cliente_id = :cliente_id WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':cliente_id', $clienteId, PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Elimina un cobro
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
