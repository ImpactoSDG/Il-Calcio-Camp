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
     * Obtiene todos los ingresos de artículos con información del artículo
     */
    public function getAll(): array
    {
        $sql = "SELECT ia.id, ia.fecha_ingreso, ia.vencimiento, ia.es_ajuste, ia.cantidad, 
                       ia.id_articulo, ia.precio_unitario, ia.total, ia.es_perecedero,
                       a.nombre AS articulo_nombre
                FROM {$this->table} ia
                INNER JOIN articulo a ON ia.id_articulo = a.id
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
     * Obtiene ingresos por artículo
     */
    public function getByArticulo(int $idArticulo): array
    {
        $sql = "SELECT ia.id, ia.fecha_ingreso, ia.vencimiento, ia.es_ajuste, ia.cantidad, 
                       ia.id_articulo, ia.precio_unitario, ia.total, ia.es_perecedero,
                       a.nombre AS articulo_nombre
                FROM {$this->table} ia
                INNER JOIN articulo a ON ia.id_articulo = a.id
                WHERE ia.id_articulo = :id_articulo
                ORDER BY ia.fecha_ingreso DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id_articulo', $idArticulo, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene ingresos por rango de fechas
     */
    public function getByFechas(string $fechaDesde, string $fechaHasta): array
    {
        $sql = "SELECT ia.id, ia.fecha_ingreso, ia.vencimiento, ia.es_ajuste, ia.cantidad, 
                       ia.id_articulo, ia.precio_unitario, ia.total, ia.es_perecedero,
                       a.nombre AS articulo_nombre
                FROM {$this->table} ia
                INNER JOIN articulo a ON ia.id_articulo = a.id
                WHERE ia.fecha_ingreso BETWEEN :fecha_desde AND :fecha_hasta
                ORDER BY ia.fecha_ingreso DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':fecha_desde', $fechaDesde);
        $stmt->bindValue(':fecha_hasta', $fechaHasta);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Crea un nuevo ingreso de artículo
     */
    public function create(string $fechaIngreso, ?string $vencimiento, bool $esAjuste, float $cantidad, int $idArticulo, float $precioUnitario, float $total, bool $esPerecedero): int|false
    {
        $sql = "INSERT INTO {$this->table} (fecha_ingreso, vencimiento, es_ajuste, cantidad, id_articulo, precio_unitario, total, es_perecedero) 
                VALUES (:fecha_ingreso, :vencimiento, :es_ajuste, :cantidad, :id_articulo, :precio_unitario, :total, :es_perecedero)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':fecha_ingreso', $fechaIngreso);
        $stmt->bindValue(':vencimiento', $vencimiento);
        $stmt->bindValue(':es_ajuste', $esAjuste ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':cantidad', $cantidad);
        $stmt->bindValue(':id_articulo', $idArticulo, PDO::PARAM_INT);
        $stmt->bindValue(':precio_unitario', $precioUnitario);
        $stmt->bindValue(':total', $total);
        $stmt->bindValue(':es_perecedero', $esPerecedero ? 1 : 0, PDO::PARAM_INT);
        
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
