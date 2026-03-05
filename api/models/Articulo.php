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
     * Obtiene solo los artículos activos
     */
    public function getActivos(): array
    {
        $sql = "SELECT a.id, a.nombre, a.precio_actual, a.costo_actual, a.cod_barra, 
                       a.id_categoria_articulo, a.activo,
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
}
