<?php

declare(strict_types=1);

class Cobro
{
    private PDO $conn;
    public string $table = 'cobro';

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    /**
     * Obtiene todos los cobros con información del cliente
     */
    public function getAll(): array
    {
        $sql = "SELECT c.id, c.cliente_id, c.fecha,
                       cl.nombre_cliente AS cliente_nombre
                FROM {$this->table} c
                LEFT JOIN cliente cl ON c.cliente_id = cl.id
                WHERE c.activo = 1
                ORDER BY c.fecha DESC, c.id DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene cobros SIN cliente asociado, agrupados con info de pagos.
     * Soporta filtros opcionales: fecha_desde, fecha_hasta, medio_pago_id.
     */
    public function getSinCliente(array $filtros = []): array
    {
        $conditions = ["c.cliente_id IS NULL", "c.activo = 1"];
        $params = [];

        if (!empty($filtros['fecha_desde'])) {
            $conditions[] = "DATE(c.fecha) >= :fecha_desde";
            $params[':fecha_desde'] = $filtros['fecha_desde'];
        }
        if (!empty($filtros['fecha_hasta'])) {
            $conditions[] = "DATE(c.fecha) <= :fecha_hasta";
            $params[':fecha_hasta'] = $filtros['fecha_hasta'];
        }
        if (!empty($filtros['medio_pago_id'])) {
            $conditions[] = "vc.id_medio_pago = :medio_pago_id";
            $params[':medio_pago_id'] = (int)$filtros['medio_pago_id'];
        }

        $where = implode(' AND ', $conditions);

        $sql = "SELECT c.id, c.fecha, c.cliente_id,
                       COALESCE(SUM(vc.monto), 0) AS monto_total,
                       GROUP_CONCAT(DISTINCT mc.descripcion ORDER BY mc.descripcion SEPARATOR ', ') AS medios_pago
                FROM {$this->table} c
                LEFT JOIN venta_cobro vc ON vc.id_cobro = c.id
                LEFT JOIN medio_cobro mc ON vc.id_medio_pago = mc.id
                WHERE {$where}
                GROUP BY c.id, c.fecha, c.cliente_id
                ORDER BY c.fecha DESC, c.id DESC";

        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene cobros CON cliente asociado, agrupados con info de pagos.
     * Soporta filtros opcionales: fecha_desde, fecha_hasta, medio_pago_id.
     */
    public function getConCliente(array $filtros = []): array
    {
        $conditions = ["c.cliente_id IS NOT NULL", "c.activo = 1"];
        $params = [];

        if (!empty($filtros['fecha_desde'])) {
            $conditions[] = "DATE(c.fecha) >= :fecha_desde";
            $params[':fecha_desde'] = $filtros['fecha_desde'];
        }
        if (!empty($filtros['fecha_hasta'])) {
            $conditions[] = "DATE(c.fecha) <= :fecha_hasta";
            $params[':fecha_hasta'] = $filtros['fecha_hasta'];
        }
        if (!empty($filtros['medio_pago_id'])) {
            $conditions[] = "vc.id_medio_pago = :medio_pago_id";
            $params[':medio_pago_id'] = (int)$filtros['medio_pago_id'];
        }

        $where = implode(' AND ', $conditions);

        $sql = "SELECT c.id, c.fecha, c.cliente_id,
                       cl.nombre_cliente AS cliente_nombre,
                       COALESCE(SUM(vc.monto), 0) AS monto_total,
                       GROUP_CONCAT(DISTINCT mc.descripcion ORDER BY mc.descripcion SEPARATOR ', ') AS medios_pago
                FROM {$this->table} c
                INNER JOIN cliente cl ON c.cliente_id = cl.id
                LEFT JOIN venta_cobro vc ON vc.id_cobro = c.id
                LEFT JOIN medio_cobro mc ON vc.id_medio_pago = mc.id
                WHERE {$where}
                GROUP BY c.id, c.fecha, c.cliente_id, cl.nombre_cliente
                ORDER BY c.fecha DESC, c.id DESC";

        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene ventas con saldo pendiente de cobro.
     * Soporta filtros opcionales: fecha_desde, fecha_hasta, medio_pago_id.
     */
    public function getVentasPendientes(array $filtros = []): array
    {
        $conditions = [];
        $params = [];

        if (!empty($filtros['fecha_desde'])) {
            $conditions[] = "DATE(v.fecha) >= :fecha_desde";
            $params[':fecha_desde'] = $filtros['fecha_desde'];
        }
        if (!empty($filtros['fecha_hasta'])) {
            $conditions[] = "DATE(v.fecha) <= :fecha_hasta";
            $params[':fecha_hasta'] = $filtros['fecha_hasta'];
        }
        if (!empty($filtros['medio_pago_id'])) {
            $conditions[] = "vc_filter.id_medio_pago = :medio_pago_id";
            $params[':medio_pago_id'] = (int)$filtros['medio_pago_id'];
        }

        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $havingMedioPago = !empty($filtros['medio_pago_id'])
            ? "HAVING saldo_pendiente > 0 AND COUNT(DISTINCT vc_filter.id_venta_cobro) > 0"
            : "HAVING saldo_pendiente > 0";

        // Para el filtro de medio_pago se usa LEFT JOIN adicional con alias
        $joinMedioPago = !empty($filtros['medio_pago_id'])
            ? "LEFT JOIN venta_cobro vc_filter ON vc_filter.id_venta = v.id"
            : "";

        $sql = "SELECT v.id, v.fecha, v.id_cliente, v.descripcion_cliente,
                       v.simbolo,
                       cl.nombre_cliente AS cliente_nombre,
                       e.nombre AS equipo_nombre,
                       (SELECT COALESCE(SUM(total), 0) FROM articulo_venta WHERE id_venta = v.id) AS total_venta,
                       (SELECT COALESCE(SUM(monto), 0) FROM venta_cobro WHERE id_venta = v.id) AS total_cobrado,
                       ((SELECT COALESCE(SUM(total), 0) FROM articulo_venta WHERE id_venta = v.id) - 
                        (SELECT COALESCE(SUM(monto), 0) FROM venta_cobro WHERE id_venta = v.id)) AS saldo_pendiente
                FROM venta v
                LEFT JOIN cliente cl ON v.id_cliente = cl.id
                LEFT JOIN equipo e ON v.id_equipo = e.id
                LEFT JOIN estado_venta ev ON v.id_estado_venta = ev.id
                {$joinMedioPago}
                WHERE (ev.descripcion LIKE '%Cerrada%' OR ev.descripcion LIKE '%Cerrado%')
                " . ($where ? " AND " . implode(' AND ', $conditions) : "") . "
                GROUP BY v.id, v.fecha, v.id_cliente, v.descripcion_cliente, v.simbolo,
                         cl.nombre_cliente, e.nombre
                {$havingMedioPago}
                ORDER BY v.fecha DESC, v.id DESC";

        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene un cobro por ID
     */
    public function getById(int $id): ?array
    {
        $sql = "SELECT c.id, c.cliente_id,
                       cl.nombre_cliente AS cliente_nombre
                FROM {$this->table} c
                INNER JOIN cliente cl ON c.cliente_id = cl.id
                WHERE c.id = :id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Obtiene cobros por cliente
     */
    public function getByCliente(int $idCliente): array
    {
        $sql = "SELECT id, cliente_id FROM {$this->table} WHERE cliente_id = :cliente_id ORDER BY id DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':cliente_id', $idCliente, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene las ventas asociadas a un cobro
     */
    public function getVentas(int $idCobro): array
    {
        $sql = "SELECT vc.id_venta_cobro, vc.id_venta, vc.id_medio_pago, vc.monto,
                       v.fecha AS venta_fecha, v.descripcion_cliente,
                       mc.descripcion AS medio_pago_descripcion
                FROM venta_cobro vc
                INNER JOIN venta v ON vc.id_venta = v.id
                INNER JOIN medio_cobro mc ON vc.id_medio_pago = mc.id
                WHERE vc.id_cobro = :id_cobro
                ORDER BY vc.id_venta_cobro ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id_cobro', $idCobro, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Crea un nuevo cobro
     */
    public function create(int $id, int $clienteId): bool
    {
        $sql = "INSERT INTO {$this->table} (id, cliente_id) VALUES (:id, :cliente_id)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':cliente_id', $clienteId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Actualiza un cobro
     */
    public function update(int $id, int $clienteId): bool
    {
        $sql = "UPDATE {$this->table} SET cliente_id = :cliente_id WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':cliente_id', $clienteId, PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Elimina un cobro (BORRADO LÓGICO)
     */
    public function delete(int $id): bool
    {
        $sql = "UPDATE {$this->table} SET activo = 0 WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Reporte de cobros de un día específico.
     * Devuelve filas agrupadas por usuario + medio de cobro para luego
     * armar la vista en el frontend.
     */
    public function getReporteDia(string $fecha): array
    {
        $sql = "SELECT
                    COALESCE(c.id_usuario, 0)          AS id_usuario,
                    COALESCE(u.nombre, 'Sin usuario')  AS nombre_usuario,
                    mc.id                              AS id_medio_pago,
                    mc.descripcion                     AS medio_pago,
                    SUM(vc.monto)                      AS total
                FROM {$this->table} c
                LEFT JOIN usuario u ON c.id_usuario = u.id
                INNER JOIN venta_cobro vc ON vc.id_cobro = c.id
                INNER JOIN medio_cobro mc ON vc.id_medio_pago = mc.id
                WHERE DATE(c.fecha) = :fecha AND c.activo = 1
                GROUP BY
                    COALESCE(c.id_usuario, 0),
                    COALESCE(u.nombre, 'Sin usuario'),
                    mc.id,
                    mc.descripcion
                ORDER BY nombre_usuario, medio_pago";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':fecha', $fecha);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
