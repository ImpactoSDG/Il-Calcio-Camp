<?php

declare(strict_types=1);

class Venta
{
    private PDO $conn;
    public string $table = 'venta';

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    /**
     * Obtiene todas las ventas con información relacionada
     */
    public function getAll(): array
    {
        $sql = "SELECT v.id, v.fecha, v.id_equipo, v.descripcion_cliente, v.id_estado_venta, 
                       v.simbolo, v.id_cliente, v.tipo_vta,
                       ev.descripcion AS estado_descripcion,
                       c.nombre_cliente AS cliente_nombre,
                       e.nombre AS equipo_nombre
                FROM {$this->table} v
                LEFT JOIN estado_venta ev ON v.id_estado_venta = ev.id
                LEFT JOIN cliente c ON v.id_cliente = c.id
                LEFT JOIN equipo e ON v.id_equipo = e.id
                ORDER BY v.fecha DESC, v.id DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene una venta por ID con detalles completos
     */
    public function getById(int $id): ?array
    {
        $sql = "SELECT v.id, v.fecha, v.id_equipo, v.descripcion_cliente, v.id_estado_venta, 
                       v.simbolo, v.id_cliente, v.tipo_vta,
                       ev.descripcion AS estado_descripcion,
                       c.nombre_cliente AS cliente_nombre,
                       e.nombre AS equipo_nombre
                FROM {$this->table} v
                LEFT JOIN estado_venta ev ON v.id_estado_venta = ev.id
                LEFT JOIN cliente c ON v.id_cliente = c.id
                LEFT JOIN equipo e ON v.id_equipo = e.id
                WHERE v.id = :id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Obtiene ventas por cliente
     */
    public function getByCliente(int $idCliente): array
    {
        $sql = "SELECT v.id, v.fecha, v.id_equipo, v.descripcion_cliente, v.id_estado_venta, 
                       v.simbolo, v.id_cliente, v.tipo_vta,
                       ev.descripcion AS estado_descripcion
                FROM {$this->table} v
                LEFT JOIN estado_venta ev ON v.id_estado_venta = ev.id
                WHERE v.id_cliente = :id_cliente
                ORDER BY v.fecha DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id_cliente', $idCliente, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene ventas por estado
     */
    public function getByEstado(int $idEstado): array
    {
        $sql = "SELECT v.id, v.fecha, v.id_equipo, v.descripcion_cliente, v.id_estado_venta, 
                       v.simbolo, v.id_cliente, v.tipo_vta,
                       c.nombre_cliente AS cliente_nombre
                FROM {$this->table} v
                LEFT JOIN cliente c ON v.id_cliente = c.id
                WHERE v.id_estado_venta = :id_estado
                ORDER BY v.fecha DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id_estado', $idEstado, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene ventas por rango de fechas
     */
    public function getByFechas(string $fechaDesde, string $fechaHasta): array
    {
        $sql = "SELECT v.id, v.fecha, v.id_equipo, v.descripcion_cliente, v.id_estado_venta, 
                       v.simbolo, v.id_cliente, v.tipo_vta,
                       ev.descripcion AS estado_descripcion,
                       c.nombre_cliente AS cliente_nombre
                FROM {$this->table} v
                LEFT JOIN estado_venta ev ON v.id_estado_venta = ev.id
                LEFT JOIN cliente c ON v.id_cliente = c.id
                WHERE v.fecha BETWEEN :fecha_desde AND :fecha_hasta
                ORDER BY v.fecha DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':fecha_desde', $fechaDesde);
        $stmt->bindValue(':fecha_hasta', $fechaHasta);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene artículos de una venta
     */
    public function getArticulos(int $idVenta): array
    {
        $sql = "SELECT av.id_articulo_venta, av.id_articulo, av.cantidad, av.precio_unitario, av.total,
                       a.nombre AS articulo_nombre, a.cod_barra
                FROM articulo_venta av
                INNER JOIN articulo a ON av.id_articulo = a.id
                WHERE av.id_venta = :id_venta
                ORDER BY av.id_articulo_venta ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id_venta', $idVenta, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Crea una nueva venta
     */
    public function create(string $fecha, ?int $idEquipo, ?string $descripcionCliente, int $idEstadoVenta, string $simbolo, ?int $idCliente, ?string $tipoVta): int|false
    {
        $sql = "INSERT INTO {$this->table} (fecha, id_equipo, descripcion_cliente, id_estado_venta, simbolo, id_cliente, tipo_vta) 
                VALUES (:fecha, :id_equipo, :descripcion_cliente, :id_estado_venta, :simbolo, :id_cliente, :tipo_vta)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':fecha', $fecha);
        $stmt->bindValue(':id_equipo', $idEquipo, PDO::PARAM_INT);
        $stmt->bindValue(':descripcion_cliente', $descripcionCliente);
        $stmt->bindValue(':id_estado_venta', $idEstadoVenta, PDO::PARAM_INT);
        $stmt->bindValue(':simbolo', $simbolo);
        $stmt->bindValue(':id_cliente', $idCliente, PDO::PARAM_INT);
        $stmt->bindValue(':tipo_vta', $tipoVta);
        
        if ($stmt->execute()) {
            return (int)$this->conn->lastInsertId();
        }
        return false;
    }

    /**
     * Actualiza una venta
     */
    public function update(int $id, string $fecha, ?int $idEquipo, ?string $descripcionCliente, int $idEstadoVenta, string $simbolo, ?int $idCliente, ?string $tipoVta): bool
    {
        $sql = "UPDATE {$this->table} 
                SET fecha = :fecha, 
                    id_equipo = :id_equipo, 
                    descripcion_cliente = :descripcion_cliente, 
                    id_estado_venta = :id_estado_venta, 
                    simbolo = :simbolo, 
                    id_cliente = :id_cliente, 
                    tipo_vta = :tipo_vta 
                WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':fecha', $fecha);
        $stmt->bindValue(':id_equipo', $idEquipo, PDO::PARAM_INT);
        $stmt->bindValue(':descripcion_cliente', $descripcionCliente);
        $stmt->bindValue(':id_estado_venta', $idEstadoVenta, PDO::PARAM_INT);
        $stmt->bindValue(':simbolo', $simbolo);
        $stmt->bindValue(':id_cliente', $idCliente, PDO::PARAM_INT);
        $stmt->bindValue(':tipo_vta', $tipoVta);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Elimina una venta
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
