<?php

declare(strict_types=1);

class ArticuloVenta
{
    private PDO $conn;
    public string $table = 'articulo_venta';

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    /**
     * Obtiene todos los artículos de ventas
     */
    public function getAll(): array
    {
        $sql = "SELECT av.id_articulo_venta, av.id_articulo, av.id_venta, av.cantidad, 
                       av.precio_unitario, av.total,
                       a.nombre AS articulo_nombre,
                       v.fecha AS venta_fecha
                FROM {$this->table} av
                INNER JOIN articulo a ON av.id_articulo = a.id
                INNER JOIN venta v ON av.id_venta = v.id
                ORDER BY v.fecha DESC, av.id_articulo_venta DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene un artículo de venta por ID
     */
    public function getById(int $id): ?array
    {
        $sql = "SELECT av.id_articulo_venta, av.id_articulo, av.id_venta, av.cantidad, 
                       av.precio_unitario, av.total,
                       a.nombre AS articulo_nombre,
                       v.fecha AS venta_fecha
                FROM {$this->table} av
                INNER JOIN articulo a ON av.id_articulo = a.id
                INNER JOIN venta v ON av.id_venta = v.id
                WHERE av.id_articulo_venta = :id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Obtiene artículos por venta
     */
    public function getByVenta(int $idVenta): array
    {
        $sql = "SELECT av.id_articulo_venta, av.id_articulo, av.id_venta, av.cantidad, 
                       av.precio_unitario, av.total,
                       a.nombre AS articulo_nombre
                FROM {$this->table} av
                INNER JOIN articulo a ON av.id_articulo = a.id
                WHERE av.id_venta = :id_venta
                ORDER BY av.id_articulo_venta ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id_venta', $idVenta, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Crea un nuevo artículo de venta
     */
    public function create(int $idArticulo, int $idVenta, float $cantidad, float $precioUnitario, float $total): int|false
    {
        $sql = "INSERT INTO {$this->table} (id_articulo, id_venta, cantidad, precio_unitario, total) 
                VALUES (:id_articulo, :id_venta, :cantidad, :precio_unitario, :total)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id_articulo', $idArticulo, PDO::PARAM_INT);
        $stmt->bindValue(':id_venta', $idVenta, PDO::PARAM_INT);
        $stmt->bindValue(':cantidad', $cantidad);
        $stmt->bindValue(':precio_unitario', $precioUnitario);
        $stmt->bindValue(':total', $total);
        
        if ($stmt->execute()) {
            return (int)$this->conn->lastInsertId();
        }
        return false;
    }

    /**
     * Actualiza un artículo de venta
     */
    public function update(int $id, int $idArticulo, int $idVenta, float $cantidad, float $precioUnitario, float $total): bool
    {
        $sql = "UPDATE {$this->table} 
                SET id_articulo = :id_articulo, 
                    id_venta = :id_venta, 
                    cantidad = :cantidad, 
                    precio_unitario = :precio_unitario, 
                    total = :total 
                WHERE id_articulo_venta = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id_articulo', $idArticulo, PDO::PARAM_INT);
        $stmt->bindValue(':id_venta', $idVenta, PDO::PARAM_INT);
        $stmt->bindValue(':cantidad', $cantidad);
        $stmt->bindValue(':precio_unitario', $precioUnitario);
        $stmt->bindValue(':total', $total);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Elimina un artículo de venta
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE id_articulo_venta = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
