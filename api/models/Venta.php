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
     * Crea una nueva venta y sus artículos asociados con descuento de stock (Transaccional)
     */
    public function createWithDetails(array $data, array $articulos): array
    {
        try {
            $this->conn->beginTransaction();

            // 1. Crear la cabecera de la venta
            $sql = "INSERT INTO {$this->table} (fecha, id_equipo, descripcion_cliente, id_estado_venta, simbolo, id_cliente, tipo_vta) 
                    VALUES (:fecha, :id_equipo, :descripcion_cliente, :id_estado_venta, :simbolo, :id_cliente, :tipo_vta)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':fecha', $data['fecha']);
            $stmt->bindValue(':id_equipo', $data['id_equipo'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':descripcion_cliente', $data['descripcion_cliente'] ?? null);
            $stmt->bindValue(':id_estado_venta', $data['id_estado_venta'], PDO::PARAM_INT);
            $stmt->bindValue(':simbolo', $data['simbolo']);
            $stmt->bindValue(':id_cliente', $data['id_cliente'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':tipo_vta', $data['tipo_vta'] ?? null);
            $stmt->execute();
            $idVenta = (int)$this->conn->lastInsertId();

            // 2. Procesar artículos
            require_once __DIR__ . '/Articulo.php';
            $articuloModel = new Articulo($this->conn);

            foreach ($articulos as $art) {
                // a. Crear el registro en articulo_venta
                $sqlAV = "INSERT INTO articulo_venta (id_articulo, id_venta, cantidad, precio_unitario, total) 
                         VALUES (:id_articulo, :id_venta, :cantidad, :precio_unitario, :total)";
                $stmtAV = $this->conn->prepare($sqlAV);
                $stmtAV->bindValue(':id_articulo', $art['id_articulo'], PDO::PARAM_INT);
                $stmtAV->bindValue(':id_venta', $idVenta, PDO::PARAM_INT);
                $stmtAV->bindValue(':cantidad', $art['cantidad']);
                $stmtAV->bindValue(':precio_unitario', $art['precio_unitario']);
                $stmtAV->bindValue(':total', $art['total']);
                $stmtAV->execute();
                $idArticuloVenta = (int)$this->conn->lastInsertId();

                // b. Lógica de Descuento de Stock (FIFO)
                $lotes = $articuloModel->getLotesDisponibles((int)$art['id_articulo']);
                $cantidadPendiente = (float)$art['cantidad'];

                // Verificar stock total antes de empezar
                $stockTotal = array_sum(array_column($lotes, 'disponible'));
                if ($stockTotal < $cantidadPendiente) {
                    throw new Exception("Stock insuficiente para el artículo {$art['id_articulo']}. Disponible: $stockTotal, Requerido: $cantidadPendiente");
                }

                foreach ($lotes as $lote) {
                    if ($cantidadPendiente <= 0) break;

                    $disponibleEnLote = (float)$lote['disponible'];
                    $aDescontar = min($cantidadPendiente, $disponibleEnLote);

                    // c. Crear relación articulo_venta_ingreso_articulo (NUEVO ID con UUID o simple)
                    $idAvia = uniqid('AVIA_'); // La base tiene VARCHAR(45) para este ID
                    $sqlAVIA = "INSERT INTO articulo_venta_ingreso_articulo (id_articulo_venta_ingreso_articulo, articulo_venta_id_articulo_venta, ingreso_articulo_id, cantidad) 
                               VALUES (:id, :id_av, :id_ingreso, :cant)";
                    $stmtAVIA = $this->conn->prepare($sqlAVIA);
                    $stmtAVIA->bindValue(':id', $idAvia);
                    $stmtAVIA->bindValue(':id_av', $idArticuloVenta, PDO::PARAM_INT);
                    $stmtAVIA->bindValue(':id_ingreso', $lote['id'], PDO::PARAM_INT);
                    $stmtAVIA->bindValue(':cant', $aDescontar);
                    $stmtAVIA->execute();

                    $cantidadPendiente -= $aDescontar;
                }
            }

            $this->conn->commit();
            return ['success' => true, 'id' => $idVenta];
        } catch (Exception $e) {
            $this->conn->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Crea una nueva venta (Método antiguo - mantenido para compatibilidad)
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
