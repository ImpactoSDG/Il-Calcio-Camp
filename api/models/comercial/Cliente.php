<?php

declare(strict_types=1);

class Cliente
{
    private PDO $conn;
    public string $table = 'cliente';

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    /**
     * Obtiene todos los clientes con información relacionada y saldo
     */
    public function getAll(): array
    {
        $sql = "SELECT c.id, c.nombre_cliente, c.cuit_dni, c.condicion_iva, c.id_condicion_iva_receptor, 
                       c.direccion, c.id_provinica,
                       cir.descripcion_condicion AS condicion_iva_descripcion,
                       p.provincia AS provincia_nombre,
                       (SELECT e.id FROM cliente_equipo ce INNER JOIN equipo e ON ce.id_equipo = e.id WHERE ce.id_cliente = c.id LIMIT 1) AS id_equipo,
                       (SELECT e.nombre FROM cliente_equipo ce INNER JOIN equipo e ON ce.id_equipo = e.id WHERE ce.id_cliente = c.id LIMIT 1) AS equipo_nombre,
                       COALESCE((SELECT SUM(av.total) FROM articulo_venta av INNER JOIN venta v ON av.id_venta = v.id WHERE v.id_cliente = c.id), 0) -
                       COALESCE((SELECT SUM(vc.monto) FROM venta_cobro vc INNER JOIN venta v ON vc.id_venta = v.id WHERE v.id_cliente = c.id), 0) AS saldo_pendiente
                FROM {$this->table} c
                LEFT JOIN condicion_iva_receptor cir ON c.id_condicion_iva_receptor = cir.id
                LEFT JOIN provincia p ON c.id_provinica = p.id
                ORDER BY c.nombre_cliente ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene el detalle de ventas y cobros de un cliente
     */
    public function getMovimientos(int $idCliente): array
    {
        $sql = "SELECT 'VENTA' as tipo, v.id as id_mov, v.fecha, 
                       COALESCE((SELECT SUM(av.total) FROM articulo_venta av WHERE av.id_venta = v.id), 0) as monto,
                       v.descripcion_cliente as descripcion
                FROM venta v
                WHERE v.id_cliente = :id_cliente
                
                UNION ALL
                
                SELECT 'COBRO' as tipo, c.id as id_mov, c.fecha,
                       COALESCE((SELECT SUM(vc.monto) FROM venta_cobro vc WHERE vc.id_cobro = c.id), 0) as monto,
                       'Cobro registrado' as descripcion
                FROM cobro c
                WHERE c.cliente_id = :id_cliente
                
                ORDER BY fecha DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id_cliente', $idCliente, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene un cliente por ID
     */
    public function getById(int $id): ?array
    {
        $sql = "SELECT c.id, c.nombre_cliente, c.cuit_dni, c.condicion_iva, c.id_condicion_iva_receptor, 
                       c.direccion, c.id_provinica,
                       cir.descripcion_condicion AS condicion_iva_descripcion,
                       p.provincia AS provincia_nombre,
                       COALESCE((SELECT SUM(av.total) FROM articulo_venta av INNER JOIN venta v ON av.id_venta = v.id WHERE v.id_cliente = c.id), 0) -
                       COALESCE((SELECT SUM(vc.monto) FROM venta_cobro vc INNER JOIN venta v ON vc.id_venta = v.id WHERE v.id_cliente = c.id), 0) AS saldo_pendiente
                FROM {$this->table} c
                LEFT JOIN condicion_iva_receptor cir ON c.id_condicion_iva_receptor = cir.id
                LEFT JOIN provincia p ON c.id_provinica = p.id
                WHERE c.id = :id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Obtiene clientes por provincia
     */
    public function getByProvincia(int $idProvincia): array
    {
        $sql = "SELECT c.id, c.nombre_cliente, c.cuit_dni, c.condicion_iva, c.id_condicion_iva_receptor, 
                       c.direccion, c.id_provinica,
                       cir.descripcion_condicion AS condicion_iva_descripcion,
                       p.provincia AS provincia_nombre
                FROM {$this->table} c
                LEFT JOIN condicion_iva_receptor cir ON c.id_condicion_iva_receptor = cir.id
                LEFT JOIN provincia p ON c.id_provinica = p.id
                WHERE c.id_provinica = :id_provincia
                ORDER BY c.nombre_cliente ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id_provincia', $idProvincia, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene los equipos asociados a un cliente
     */
    public function getEquipos(int $idCliente): array
    {
        $sql = "SELECT e.id, e.nombre, e.disciplina, e.activo
                FROM cliente_equipo ce
                INNER JOIN equipo e ON ce.id_equipo = e.id
                WHERE ce.id_cliente = :id_cliente
                ORDER BY e.nombre ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id_cliente', $idCliente, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Crea un nuevo cliente
     */
    public function create(?int $id, string $nombreCliente, ?string $condicionIva, ?int $idCondicionIvaReceptor, ?string $direccion, ?int $idProvincia): int|bool
    {
        $sql = "INSERT INTO {$this->table} (id, nombre_cliente, condicion_iva, id_condicion_iva_receptor, direccion, id_provinica) 
                VALUES (:id, :nombre_cliente, :condicion_iva, :id_condicion_iva_receptor, :direccion, :id_provinica)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, $id ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':nombre_cliente', $nombreCliente);
        $stmt->bindValue(':condicion_iva', $condicionIva);
        $stmt->bindValue(':id_condicion_iva_receptor', $idCondicionIvaReceptor, $idCondicionIvaReceptor ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':direccion', $direccion);
        $stmt->bindValue(':id_provinica', $idProvincia, $idProvincia ? PDO::PARAM_INT : PDO::PARAM_NULL);
        
        if ($stmt->execute()) {
            return (int)$this->conn->lastInsertId();
        }
        return false;
    }

    /**
     * Actualiza un cliente
     */
    public function update(int $id, string $nombreCliente, ?string $condicionIva, ?int $idCondicionIvaReceptor, ?string $direccion, ?int $idProvincia): bool
    {
        $sql = "UPDATE {$this->table} 
                SET nombre_cliente = :nombre_cliente, 
                    condicion_iva = :condicion_iva, 
                    id_condicion_iva_receptor = :id_condicion_iva_receptor, 
                    direccion = :direccion, 
                    id_provinica = :id_provinica 
                WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':nombre_cliente', $nombreCliente);
        $stmt->bindValue(':condicion_iva', $condicionIva);
        $stmt->bindValue(':id_condicion_iva_receptor', $idCondicionIvaReceptor, PDO::PARAM_INT);
        $stmt->bindValue(':direccion', $direccion);
        $stmt->bindValue(':id_provinica', $idProvincia, PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Elimina un cliente
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
