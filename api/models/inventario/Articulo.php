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
                       a.id_categoria_articulo, a.activo, a.url_imagen, a.ROP,
                       ca.descripcion AS categoria_descripcion
                FROM {$this->table} a
                LEFT JOIN categoria_articulo ca ON a.id_categoria_articulo = ca.id
                WHERE a.activo = 1
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
                       a.id_categoria_articulo, a.activo, a.url_imagen, a.ROP,
                       ca.descripcion AS categoria_descripcion,
                       (SELECT COALESCE(SUM(ia.cantidad), 0)
                        FROM ingreso_articulo ia
                        LEFT JOIN pedido_proveedor pp ON ia.id_pedido_proveedor = pp.id_pedido_proveedor
                        WHERE ia.id_articulo = a.id
                          AND (ia.id_pedido_proveedor IS NULL OR pp.estado = 'recibido')) - 
                       (SELECT COALESCE(SUM(avia.cantidad), 0) 
                        FROM articulo_venta_ingreso_articulo avia 
                        JOIN ingreso_articulo ia2 ON avia.ingreso_articulo_id = ia2.id
                        LEFT JOIN pedido_proveedor pp2 ON ia2.id_pedido_proveedor = pp2.id_pedido_proveedor
                        WHERE ia2.id_articulo = a.id
                          AND (ia2.id_pedido_proveedor IS NULL OR pp2.estado = 'recibido')) as stock_actual
                FROM {$this->table} a
                LEFT JOIN categoria_articulo ca ON a.id_categoria_articulo = ca.id
                WHERE a.activo = 1
                ORDER BY a.nombre ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene los lotes (ingresos) disponibles con stock para un artículo específico (FEFO: First Expired, First Out)
     */
    public function getLotesDisponibles(int $idArticulo): array
    {
        // Prioridad 1: Fecha de vencimiento ascendente (los que vencen antes van primero).
        // Nota: Los que NO tienen fecha de vencimiento (NULL) se consideran de vencimiento infinito y van al final.
        // Prioridad 2: Fecha de ingreso ascendente (FIFO como criterio de desempate).
        $sql = "SELECT ia.id, ia.cantidad, ia.fecha_ingreso, ia.vencimiento,
                       (ia.cantidad - COALESCE((SELECT SUM(avia.cantidad) 
                                                FROM articulo_venta_ingreso_articulo avia 
                                                WHERE avia.ingreso_articulo_id = ia.id), 0)) as disponible
                FROM ingreso_articulo ia
                LEFT JOIN pedido_proveedor pp ON ia.id_pedido_proveedor = pp.id_pedido_proveedor
                WHERE ia.id_articulo = :id_articulo
                  AND (ia.id_pedido_proveedor IS NULL OR pp.estado = 'recibido')
                HAVING disponible > 0
                ORDER BY 
                    CASE WHEN ia.vencimiento IS NULL THEN 1 ELSE 0 END, 
                    ia.vencimiento ASC, 
                    ia.fecha_ingreso ASC, 
                    ia.id ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id_articulo', $idArticulo, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene el ingreso_articulo más reciente del artículo (sin importar stock disponible).
     * Se usa como fallback para registrar ventas cuando no hay lotes con stock positivo.
     */
    public function getUltimoLote(int $idArticulo): ?array
    {
        $sql = "SELECT ia.id FROM ingreso_articulo ia
                LEFT JOIN pedido_proveedor pp ON ia.id_pedido_proveedor = pp.id_pedido_proveedor
                WHERE ia.id_articulo = :id_articulo
                  AND (ia.id_pedido_proveedor IS NULL OR pp.estado = 'recibido')
                ORDER BY ia.id DESC
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id_articulo', $idArticulo, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Crea un ingreso de ajuste con cantidad=0 para artículos sin ningún lote previo.
     * Permite registrar ventas con stock negativo aunque nunca haya habido un ingreso.
     */
    public function crearIngresoAjuste(int $idArticulo): int
    {
        $sql = "INSERT INTO ingreso_articulo (id_articulo, fecha_ingreso, cantidad, precio_unitario, total, es_ajuste, es_perecedero)
                VALUES (:id_articulo, CURDATE(), 0, 0, 0, 1, 0)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id_articulo', $idArticulo, PDO::PARAM_INT);
        $stmt->execute();
        return (int)$this->conn->lastInsertId();
    }

    /**
     * Obtiene un artículo por ID
     */
    public function getById(int $id): ?array
    {
        $sql = "SELECT a.id, a.nombre, a.precio_actual, a.costo_actual, a.cod_barra, 
                       a.id_categoria_articulo, a.activo, a.url_imagen, a.ROP,
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
                       a.id_categoria_articulo, a.activo, a.url_imagen,
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
                       a.id_categoria_articulo, a.activo, a.url_imagen,
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
    public function create(string $nombre, ?float $precioActual, ?float $costoActual, ?string $codBarra, ?int $idCategoriaArticulo, bool $activo = true, ?string $urlImagen = null, ?int $ROP = 1): int|false
    {
        $sql = "INSERT INTO {$this->table} (nombre, precio_actual, costo_actual, cod_barra, id_categoria_articulo, activo, url_imagen, ROP) 
                VALUES (:nombre, :precio_actual, :costo_actual, :cod_barra, :id_categoria_articulo, :activo, :url_imagen, :ROP)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':nombre', $nombre);
        $stmt->bindValue(':precio_actual', $precioActual);
        $stmt->bindValue(':costo_actual', $costoActual);
        $stmt->bindValue(':cod_barra', $codBarra);
        $stmt->bindValue(':id_categoria_articulo', $idCategoriaArticulo, PDO::PARAM_INT);
        $stmt->bindValue(':activo', $activo ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':url_imagen', $urlImagen);
        $stmt->bindValue(':ROP', $ROP, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            return (int)$this->conn->lastInsertId();
        }
        return false;
    }

    /**
     * Actualiza un artículo
     */
    public function update(int $id, string $nombre, ?float $precioActual, ?float $costoActual, ?string $codBarra, ?int $idCategoriaArticulo, bool $activo, ?string $urlImagen = null, ?int $ROP = 1): bool
    {
        $sql = "UPDATE {$this->table} 
                SET nombre = :nombre, 
                    precio_actual = :precio_actual, 
                    costo_actual = :costo_actual, 
                    cod_barra = :cod_barra, 
                    id_categoria_articulo = :id_categoria_articulo, 
                    activo = :activo,
                    url_imagen = COALESCE(:url_imagen, url_imagen),
                    ROP = :ROP
                WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':nombre', $nombre);
        $stmt->bindValue(':precio_actual', $precioActual);
        $stmt->bindValue(':costo_actual', $costoActual);
        $stmt->bindValue(':cod_barra', $codBarra);
        $stmt->bindValue(':id_categoria_articulo', $idCategoriaArticulo, PDO::PARAM_INT);
        $stmt->bindValue(':activo', $activo ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':url_imagen', $urlImagen);
        $stmt->bindValue(':ROP', $ROP, PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Actualiza solo la imagen del artículo
     */
    public function updateImagen(int $id, string $urlImagen): bool
    {
        $sql = "UPDATE {$this->table} SET url_imagen = :url_imagen WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':url_imagen', $urlImagen);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Borrado lógico: desactiva el artículo en lugar de eliminarlo físicamente.
     * Preserva integridad referencial con ventas, ingresos, etc.
     */
    public function delete(int $id): bool
    {
        $sql = "UPDATE {$this->table} SET activo = 0 WHERE id = :id";
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
