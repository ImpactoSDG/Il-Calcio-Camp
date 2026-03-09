<?php

declare(strict_types=1);

class Articulo
{
    private PDO $conn;
    public string $table = 'articulo';

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    /**
     * Obtiene todos los artículos con información de categoría
     */
    public function getAll(): array
    {
        $sql = "SELECT a.id, a.nombre, a.precio_actual, a.costo_actual, a.cod_barra, 
                       a.id_categoria_articulo, a.activo,
                       ca.descripcion AS categoria_descripcion
                FROM {$this->table} a
                LEFT JOIN categoria_articulo ca ON a.id_categoria_articulo = ca.id
                ORDER BY a.nombre ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene solo los artículos activos con su stock disponible actual
     */
    public function getActivos(): array
    {
        $sql = "SELECT a.id, a.nombre, a.precio_actual, a.costo_actual, a.cod_barra, 
                       a.id_categoria_articulo, a.activo,
                       ca.descripcion AS categoria_descripcion,
                       (SELECT COALESCE(SUM(ia.cantidad), 0) FROM ingreso_articulo ia WHERE ia.id_articulo = a.id) - 
                       (SELECT COALESCE(SUM(avia.cantidad), 0) 
                        FROM articulo_venta_ingreso_articulo avia 
                        JOIN ingreso_articulo ia2 ON avia.ingreso_articulo_id = ia2.id
                        WHERE ia2.id_articulo = a.id) as stock_actual
                FROM {$this->table} a
                LEFT JOIN categoria_articulo ca ON a.id_categoria_articulo = ca.id
                WHERE a.activo = 1
                ORDER BY a.nombre ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene los lotes (ingresos) disponibles con stock para un artículo específico (FIFO)
     */
    public function getLotesDisponibles(int $idArticulo): array
    {
        $sql = "SELECT ia.id, ia.cantidad, ia.fecha_ingreso, ia.vencimiento,
                       (ia.cantidad - COALESCE((SELECT SUM(avia.cantidad) 
                                                FROM articulo_venta_ingreso_articulo avia 
                                                WHERE avia.ingreso_articulo_id = ia.id), 0)) as disponible
                FROM ingreso_articulo ia
                WHERE ia.id_articulo = :id_articulo
                HAVING disponible > 0
                ORDER BY ia.fecha_ingreso ASC, ia.id ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id_articulo', $idArticulo, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene un artículo por ID
     */
    public function getById(int $id): ?array
    {
        $sql = "SELECT a.id, a.nombre, a.precio_actual, a.costo_actual, a.cod_barra, 
                       a.id_categoria_articulo, a.activo,
                       ca.descripcion AS categoria_descripcion
                FROM {$this->table} a
                LEFT JOIN categoria_articulo ca ON a.id_categoria_articulo = ca.id
                WHERE a.id = :id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Obtiene artículos por categoría
     */
    public function getByCategoria(int $idCategoria): array
    {
        $sql = "SELECT a.id, a.nombre, a.precio_actual, a.costo_actual, a.cod_barra, 
                       a.id_categoria_articulo, a.activo,
                       ca.descripcion AS categoria_descripcion
                FROM {$this->table} a
                LEFT JOIN categoria_articulo ca ON a.id_categoria_articulo = ca.id
                WHERE a.id_categoria_articulo = :id_categoria
                ORDER BY a.nombre ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id_categoria', $idCategoria, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca un artículo por código de barras
     */
    public function getByCodBarra(string $codBarra): ?array
    {
        $sql = "SELECT a.id, a.nombre, a.precio_actual, a.costo_actual, a.cod_barra, 
                       a.id_categoria_articulo, a.activo,
                       ca.descripcion AS categoria_descripcion
                FROM {$this->table} a
                LEFT JOIN categoria_articulo ca ON a.id_categoria_articulo = ca.id
                WHERE a.cod_barra = :cod_barra LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':cod_barra', $codBarra);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Crea un nuevo artículo
     */
    public function create(string $nombre, ?float $precioActual, ?float $costoActual, ?string $codBarra, ?int $idCategoriaArticulo, bool $activo = true): int|false
    {
        $sql = "INSERT INTO {$this->table} (nombre, precio_actual, costo_actual, cod_barra, id_categoria_articulo, activo) 
                VALUES (:nombre, :precio_actual, :costo_actual, :cod_barra, :id_categoria_articulo, :activo)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':nombre', $nombre);
        $stmt->bindValue(':precio_actual', $precioActual);
        $stmt->bindValue(':costo_actual', $costoActual);
        $stmt->bindValue(':cod_barra', $codBarra);
        $stmt->bindValue(':id_categoria_articulo', $idCategoriaArticulo, PDO::PARAM_INT);
        $stmt->bindValue(':activo', $activo ? 1 : 0, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            return (int)$this->conn->lastInsertId();
        }
        return false;
    }

    /**
     * Actualiza un artículo
     */
    public function update(int $id, string $nombre, ?float $precioActual, ?float $costoActual, ?string $codBarra, ?int $idCategoriaArticulo, bool $activo): bool
    {
        $sql = "UPDATE {$this->table} 
                SET nombre = :nombre, 
                    precio_actual = :precio_actual, 
                    costo_actual = :costo_actual, 
                    cod_barra = :cod_barra, 
                    id_categoria_articulo = :id_categoria_articulo, 
                    activo = :activo 
                WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':nombre', $nombre);
        $stmt->bindValue(':precio_actual', $precioActual);
        $stmt->bindValue(':costo_actual', $costoActual);
        $stmt->bindValue(':cod_barra', $codBarra);
        $stmt->bindValue(':id_categoria_articulo', $idCategoriaArticulo, PDO::PARAM_INT);
        $stmt->bindValue(':activo', $activo ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Elimina un artículo
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Actualización masiva de precio_actual o costo_actual en una lista de IDs.
     * $campo debe ser 'precio_actual' o 'costo_actual' (validado en el controlador).
     */
    public function bulkUpdatePrecios(array $ids, string $campo, array $precios): int
    {
        if (empty($ids)) return 0;

        $affected = 0;
        $this->conn->beginTransaction();
        try {
            $sql = "UPDATE {$this->table} SET {$campo} = :precio WHERE id = :id";
            $stmt = $this->conn->prepare($sql);
            foreach ($ids as $id) {
                if (!isset($precios[$id])) continue;
                $stmt->bindValue(':precio', $precios[$id]);
                $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
                $stmt->execute();
                $affected += $stmt->rowCount();
            }
            $this->conn->commit();
        } catch (Throwable $e) {
            $this->conn->rollBack();
            throw $e;
        }
        return $affected;
    }

    /**
     * Actualización masiva de estado (activar/desactivar) en una lista de IDs.
     */
    public function bulkUpdateStatus(array $ids, bool $activo): int
    {
        if (empty($ids)) return 0;

        $in = implode(',', array_fill(0, count($ids), '?'));
        $sql = "UPDATE {$this->table} SET activo = ? WHERE id IN ({$in})";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(1, $activo ? 1 : 0, PDO::PARAM_INT);
        foreach ($ids as $k => $id) {
            $stmt->bindValue($k + 2, (int)$id, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->rowCount();
    }
}
