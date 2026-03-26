<?php

declare(strict_types=1);

class ArticuloVentaIngresoArticulo
{
    private PDO $conn;
    public string $table = 'articulo_venta_ingreso_articulo';

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    /**
     * Obtiene todas las relaciones artículo_venta - ingreso_articulo
     */
    public function getAll(): array
    {
        $sql = "SELECT avia.id_articulo_venta_ingreso_articulo, avia.articulo_venta_id_articulo_venta, 
                       avia.ingreso_articulo_id, avia.cantidad,
                       av.id_venta, av.id_articulo,
                       a.nombre AS articulo_nombre,
                       ia.fecha_ingreso
                FROM {$this->table} avia
                INNER JOIN articulo_venta av ON avia.articulo_venta_id_articulo_venta = av.id_articulo_venta
                INNER JOIN ingreso_articulo ia ON avia.ingreso_articulo_id = ia.id
                INNER JOIN articulo a ON av.id_articulo = a.id
                ORDER BY avia.id_articulo_venta_ingreso_articulo DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene una relación por ID
     */
    public function getById(string $id): ?array
    {
        $sql = "SELECT avia.id_articulo_venta_ingreso_articulo, avia.articulo_venta_id_articulo_venta, 
                       avia.ingreso_articulo_id, avia.cantidad,
                       av.id_venta, av.id_articulo,
                       a.nombre AS articulo_nombre,
                       ia.fecha_ingreso
                FROM {$this->table} avia
                INNER JOIN articulo_venta av ON avia.articulo_venta_id_articulo_venta = av.id_articulo_venta
                INNER JOIN ingreso_articulo ia ON avia.ingreso_articulo_id = ia.id
                INNER JOIN articulo a ON av.id_articulo = a.id
                WHERE avia.id_articulo_venta_ingreso_articulo = :id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Obtiene relaciones por artículo de venta
     */
    public function getByArticuloVenta(int $idArticuloVenta): array
    {
        $sql = "SELECT avia.id_articulo_venta_ingreso_articulo, avia.ingreso_articulo_id, avia.cantidad,
                       ia.fecha_ingreso, ia.vencimiento
                FROM {$this->table} avia
                INNER JOIN ingreso_articulo ia ON avia.ingreso_articulo_id = ia.id
                WHERE avia.articulo_venta_id_articulo_venta = :id_articulo_venta
                ORDER BY ia.fecha_ingreso ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id_articulo_venta', $idArticuloVenta, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Crea una nueva relación
     */
    public function create(string $id, int $articuloVentaId, int $ingresoArticuloId, string $cantidad): bool
    {
        $sql = "INSERT INTO {$this->table} (id_articulo_venta_ingreso_articulo, articulo_venta_id_articulo_venta, ingreso_articulo_id, cantidad) 
                VALUES (:id, :articulo_venta_id, :ingreso_articulo_id, :cantidad)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id);
        $stmt->bindValue(':articulo_venta_id', $articuloVentaId, PDO::PARAM_INT);
        $stmt->bindValue(':ingreso_articulo_id', $ingresoArticuloId, PDO::PARAM_INT);
        $stmt->bindValue(':cantidad', $cantidad);
        return $stmt->execute();
    }

    /**
     * Actualiza una relación
     */
    public function update(string $id, int $articuloVentaId, int $ingresoArticuloId, string $cantidad): bool
    {
        $sql = "UPDATE {$this->table} 
                SET articulo_venta_id_articulo_venta = :articulo_venta_id, 
                    ingreso_articulo_id = :ingreso_articulo_id, 
                    cantidad = :cantidad 
                WHERE id_articulo_venta_ingreso_articulo = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':articulo_venta_id', $articuloVentaId, PDO::PARAM_INT);
        $stmt->bindValue(':ingreso_articulo_id', $ingresoArticuloId, PDO::PARAM_INT);
        $stmt->bindValue(':cantidad', $cantidad);
        $stmt->bindValue(':id', $id);
        return $stmt->execute();
    }

    /**
     * Elimina una relación
     */
    public function delete(string $id): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE id_articulo_venta_ingreso_articulo = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id);
        return $stmt->execute();
    }
}
