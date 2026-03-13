<?php

declare(strict_types=1);

class IngresoArticulo
{
    private PDO $conn;
    public string $table = 'ingreso_articulo';

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    /**
     * Obtiene todos los ingresos de artículos disponibles en stock:
     * - Ingresos manuales (sin pedido asociado) siempre se muestran.
     * - Ingresos de pedidos solo si el pedido está en estado 'recibido'.
     */
    public function getAll(): array
    {
        $sql = "SELECT ia.id, ia.fecha_ingreso, ia.vencimiento, ia.es_ajuste, ia.cantidad, 
                       ia.id_articulo, ia.precio_unitario, ia.total, ia.es_perecedero,
                       ia.id_pedido_proveedor,
                       a.nombre AS articulo_nombre, a.url_imagen,
                       COALESCE(SUM(avia.cantidad), 0) AS cantidad_vendida
                FROM {$this->table} ia
                INNER JOIN articulo a ON ia.id_articulo = a.id
                LEFT JOIN pedido_proveedor pp ON ia.id_pedido_proveedor = pp.id_pedido_proveedor
                LEFT JOIN articulo_venta_ingreso_articulo avia ON ia.id = avia.ingreso_articulo_id
                WHERE (ia.id_pedido_proveedor IS NULL OR pp.estado = 'recibido')
                GROUP BY ia.id
                ORDER BY ia.fecha_ingreso DESC, ia.id DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene un ingreso de artículo por ID
     */
    public function getById(int $id): ?array
    {
        $sql = "SELECT ia.id, ia.fecha_ingreso, ia.vencimiento, ia.es_ajuste, ia.cantidad, 
                       ia.id_articulo, ia.precio_unitario, ia.total, ia.es_perecedero,
                       a.nombre AS articulo_nombre
                FROM {$this->table} ia
                INNER JOIN articulo a ON ia.id_articulo = a.id
                WHERE ia.id = :id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Obtiene ingresos en stock por artículo (manuales o de pedidos recibidos).
     */
    public function getByArticulo(int $idArticulo): array
    {
        $sql = "SELECT ia.id, ia.fecha_ingreso, ia.vencimiento, ia.es_ajuste, ia.cantidad, 
                       ia.id_articulo, ia.precio_unitario, ia.total, ia.es_perecedero,
                       a.nombre AS articulo_nombre
                FROM {$this->table} ia
                INNER JOIN articulo a ON ia.id_articulo = a.id
                LEFT JOIN pedido_proveedor pp ON ia.id_pedido_proveedor = pp.id_pedido_proveedor
                WHERE ia.id_articulo = :id_articulo
                  AND (ia.id_pedido_proveedor IS NULL OR pp.estado = 'recibido')
                ORDER BY ia.fecha_ingreso DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id_articulo', $idArticulo, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene ingresos en stock por rango de fechas (manuales o de pedidos recibidos).
     */
    public function getByFechas(string $fechaDesde, string $fechaHasta): array
    {
        $sql = "SELECT ia.id, ia.fecha_ingreso, ia.vencimiento, ia.es_ajuste, ia.cantidad, 
                       ia.id_articulo, ia.precio_unitario, ia.total, ia.es_perecedero,
                       a.nombre AS articulo_nombre
                FROM {$this->table} ia
                INNER JOIN articulo a ON ia.id_articulo = a.id
                LEFT JOIN pedido_proveedor pp ON ia.id_pedido_proveedor = pp.id_pedido_proveedor
                WHERE ia.fecha_ingreso BETWEEN :fecha_desde AND :fecha_hasta
                  AND (ia.id_pedido_proveedor IS NULL OR pp.estado = 'recibido')
                ORDER BY ia.fecha_ingreso DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':fecha_desde', $fechaDesde);
        $stmt->bindValue(':fecha_hasta', $fechaHasta);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Crea un nuevo ingreso de artículo.
     * Los ingresos manuales ($idPedidoProveedor = null) siempre cuentan como stock.
     * Los ingresos de pedidos cuentan como stock cuando el pedido pase a 'recibido'.
     */
    public function create(string $fechaIngreso, ?string $vencimiento, bool $esAjuste, float $cantidad, int $idArticulo, float $precioUnitario, float $total, bool $esPerecedero, ?int $idPedidoProveedor = null): int|false
    {
        $sql = "INSERT INTO {$this->table}
                    (fecha_ingreso, vencimiento, es_ajuste, cantidad, id_articulo,
                     precio_unitario, total, es_perecedero, id_pedido_proveedor)
                VALUES
                    (:fecha_ingreso, :vencimiento, :es_ajuste, :cantidad, :id_articulo,
                     :precio_unitario, :total, :es_perecedero, :id_pedido_proveedor)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':fecha_ingreso', $fechaIngreso);
        $stmt->bindValue(':vencimiento', $vencimiento);
        $stmt->bindValue(':es_ajuste', $esAjuste ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':cantidad', $cantidad);
        $stmt->bindValue(':id_articulo', $idArticulo, PDO::PARAM_INT);
        $stmt->bindValue(':precio_unitario', $precioUnitario);
        $stmt->bindValue(':total', $total);
        $stmt->bindValue(':es_perecedero', $esPerecedero ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':id_pedido_proveedor', $idPedidoProveedor, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            return (int)$this->conn->lastInsertId();
        }
        return false;
    }

    /**
     * Actualiza un ingreso de artículo
     */
    public function update(int $id, string $fechaIngreso, ?string $vencimiento, bool $esAjuste, float $cantidad, int $idArticulo, float $precioUnitario, float $total, bool $esPerecedero): bool
    {
        $sql = "UPDATE {$this->table} 
                SET fecha_ingreso = :fecha_ingreso, 
                    vencimiento = :vencimiento, 
                    es_ajuste = :es_ajuste, 
                    cantidad = :cantidad, 
                    id_articulo = :id_articulo, 
                    precio_unitario = :precio_unitario, 
                    total = :total, 
                    es_perecedero = :es_perecedero 
                WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':fecha_ingreso', $fechaIngreso);
        $stmt->bindValue(':vencimiento', $vencimiento);
        $stmt->bindValue(':es_ajuste', $esAjuste ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':cantidad', $cantidad);
        $stmt->bindValue(':id_articulo', $idArticulo, PDO::PARAM_INT);
        $stmt->bindValue(':precio_unitario', $precioUnitario);
        $stmt->bindValue(':total', $total);
        $stmt->bindValue(':es_perecedero', $esPerecedero ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Elimina un ingreso de artículo
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
