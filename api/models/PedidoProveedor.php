<?php

declare(strict_types=1);

class PedidoProveedor
{
    private PDO $conn;
    public string $table = 'pedido_proveedor';
    // Los ítems del pedido se almacenan directamente en ingreso_articulo
    // con confirmado = 0 (pendiente) hasta que el pedido sea recibido.
    private string $ingresoTable = 'ingreso_articulo';

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function getAll(): array
    {
        $sql = "SELECT pp.id_pedido_proveedor, pp.id_proveedor, pp.fecha_pedido,
                       pp.fecha_entrega, pp.estado, pp.observaciones,
                       p.nombre AS proveedor_nombre, p.apellido AS proveedor_apellido,
                       p.nombre_fantasia AS proveedor_fantasia,
                       COUNT(ia.id) AS total_items,
                       COALESCE(SUM(ia.total), 0) AS total_estimado
                FROM {$this->table} pp
                INNER JOIN proveedor p ON pp.id_proveedor = p.id_proveedor
                LEFT JOIN {$this->ingresoTable} ia ON pp.id_pedido_proveedor = ia.id_pedido_proveedor
                GROUP BY pp.id_pedido_proveedor
                ORDER BY pp.fecha_pedido DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $sql = "SELECT pp.id_pedido_proveedor, pp.id_proveedor, pp.fecha_pedido,
                       pp.fecha_entrega, pp.estado, pp.observaciones,
                       p.nombre AS proveedor_nombre, p.apellido AS proveedor_apellido,
                       p.nombre_fantasia AS proveedor_fantasia
                FROM {$this->table} pp
                INNER JOIN proveedor p ON pp.id_proveedor = p.id_proveedor
                WHERE pp.id_pedido_proveedor = :id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$result) return null;

        $result['items'] = $this->getItems($id);
        return $result;
    }

    /**
     * Obtiene los ítems del pedido desde ingreso_articulo.
     * Usa precio_unitario como precio estimado (nombre alias para el frontend).
     */
    public function getItems(int $idPedido): array
    {
        $sql = "SELECT ia.id, ia.id_articulo, ia.cantidad,
                       ia.precio_unitario AS precio_unitario_estimado,
                       ia.total, ia.confirmado,
                       a.nombre AS articulo_nombre, a.cod_barra
                FROM {$this->ingresoTable} ia
                INNER JOIN articulo a ON ia.id_articulo = a.id
                WHERE ia.id_pedido_proveedor = :id_pedido
                ORDER BY a.nombre ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id_pedido', $idPedido, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Crea un pedido en estado 'pendiente' e inserta los ítems en ingreso_articulo
     * con confirmado = 0 (NO cuentan como stock todavía).
     */
    public function create(int $idProveedor, ?string $fechaEntrega, ?string $observaciones, array $items): int
    {
        $this->conn->beginTransaction();
        try {
            $sql = "INSERT INTO {$this->table} (id_proveedor, fecha_entrega, estado, observaciones)
                    VALUES (:id_proveedor, :fecha_entrega, 'pendiente', :observaciones)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':id_proveedor', $idProveedor, PDO::PARAM_INT);
            $stmt->bindValue(':fecha_entrega', $fechaEntrega);
            $stmt->bindValue(':observaciones', $observaciones);
            $stmt->execute();
            $idPedido = (int)$this->conn->lastInsertId();

            $this->insertItems($idPedido, $items);

            $this->conn->commit();
            return $idPedido;
        } catch (Throwable $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    /**
     * Actualiza cabecera e ítems del pedido.
     * Solo permitido si está en estado 'pendiente'.
     * Elimina y re-inserta los ítems en ingreso_articulo (confirmado = 0).
     */
    public function update(int $id, int $idProveedor, ?string $fechaEntrega, ?string $observaciones, array $items): bool
    {
        $current = $this->getById($id);
        if (!$current) {
            throw new \RuntimeException("Pedido con ID {$id} no encontrado.");
        }
        if ($current['estado'] !== 'pendiente') {
            throw new \RuntimeException("Solo se pueden editar pedidos en estado 'pendiente'. Estado actual: '{$current['estado']}'.");
        }

        $this->conn->beginTransaction();
        try {
            $sql = "UPDATE {$this->table}
                    SET id_proveedor = :id_proveedor,
                        fecha_entrega = :fecha_entrega,
                        observaciones = :observaciones
                    WHERE id_pedido_proveedor = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':id_proveedor', $idProveedor, PDO::PARAM_INT);
            $stmt->bindValue(':fecha_entrega', $fechaEntrega);
            $stmt->bindValue(':observaciones', $observaciones);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            // Eliminar solo los registros pendientes (confirmado = 0)
            $stmtDel = $this->conn->prepare(
                "DELETE FROM {$this->ingresoTable} WHERE id_pedido_proveedor = :id AND confirmado = 0"
            );
            $stmtDel->bindValue(':id', $id, PDO::PARAM_INT);
            $stmtDel->execute();

            $this->insertItems($id, $items);

            $this->conn->commit();
            return true;
        } catch (Throwable $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    /**
     * Inserta los ítems del pedido en ingreso_articulo con confirmado = 0.
     * La fecha se registra ya pero no afecta el stock hasta la recepción.
     */
    private function insertItems(int $idPedido, array $items): void
    {
        $sql = "INSERT INTO {$this->ingresoTable}
                    (fecha_ingreso, es_ajuste, cantidad, id_articulo,
                     precio_unitario, total, es_perecedero, id_pedido_proveedor, confirmado)
                VALUES
                    (:fecha_ingreso, 0, :cantidad, :id_articulo,
                     :precio_unitario, :total, 0, :id_pedido_proveedor, 0)";
        $stmt = $this->conn->prepare($sql);
        $fechaHoy = date('Y-m-d');

        foreach ($items as $item) {
            $precioUnitario = (float)($item['precio_unitario_estimado'] ?? 0);
            $cantidad       = (float)$item['cantidad'];
            $total          = round($precioUnitario * $cantidad, 2);

            $stmt->bindValue(':fecha_ingreso', $fechaHoy);
            $stmt->bindValue(':cantidad', $cantidad);
            $stmt->bindValue(':id_articulo', (int)$item['id_articulo'], PDO::PARAM_INT);
            $stmt->bindValue(':precio_unitario', $precioUnitario);
            $stmt->bindValue(':total', $total);
            $stmt->bindValue(':id_pedido_proveedor', $idPedido, PDO::PARAM_INT);
            $stmt->execute();
        }
    }

    /**
     * Cambia el estado del pedido y ejecuta la lógica asociada:
     * - 'recibido': confirma los ingresos (confirmado = 1) → el stock sube.
     * - 'cancelado': elimina los ingresos pendientes (confirmado = 0) → el stock no se ve afectado.
     */
    public function cambiarEstado(int $id, string $nuevoEstado): bool
    {
        $valid = ['recibido', 'cancelado'];
        if (!in_array($nuevoEstado, $valid, true)) {
            throw new \RuntimeException("Estado inválido. Solo se permite 'recibido' o 'cancelado'.");
        }

        $current = $this->getById($id);
        if (!$current) {
            throw new \RuntimeException("Pedido con ID {$id} no encontrado.");
        }
        if ($current['estado'] !== 'pendiente') {
            throw new \RuntimeException("El pedido ya está en estado '{$current['estado']}'. No se puede modificar.");
        }

        return $nuevoEstado === 'recibido'
            ? $this->recibirPedido($id)
            : $this->cancelarPedido($id);
    }

    /**
     * Transacción crítica de recepción:
     * Cambia confirmado = 1 en todos los ingresos del pedido → el stock aumenta.
     * Registra la fecha de entrega real si no tenía una.
     */
    private function recibirPedido(int $id): bool
    {
        // Verificar que tenga ítems antes de confirmar
        $count = $this->conn->prepare(
            "SELECT COUNT(*) FROM {$this->ingresoTable} WHERE id_pedido_proveedor = :id AND confirmado = 0"
        );
        $count->bindValue(':id', $id, PDO::PARAM_INT);
        $count->execute();
        if ((int)$count->fetchColumn() === 0) {
            throw new \RuntimeException("El pedido no tiene ítems pendientes de confirmar.");
        }

        $this->conn->beginTransaction();
        try {
            // Confirmar los ingresos → ahora sí cuentan como stock
            $stmtConfirm = $this->conn->prepare(
                "UPDATE {$this->ingresoTable} SET confirmado = 1 WHERE id_pedido_proveedor = :id AND confirmado = 0"
            );
            $stmtConfirm->bindValue(':id', $id, PDO::PARAM_INT);
            $stmtConfirm->execute();

            // Marcar el pedido como recibido
            $stmtPedido = $this->conn->prepare(
                "UPDATE {$this->table}
                 SET estado = 'recibido',
                     fecha_entrega = COALESCE(fecha_entrega, :fecha)
                 WHERE id_pedido_proveedor = :id"
            );
            $stmtPedido->bindValue(':fecha', date('Y-m-d'));
            $stmtPedido->bindValue(':id', $id, PDO::PARAM_INT);
            $stmtPedido->execute();

            $this->conn->commit();
            return true;
        } catch (Throwable $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    /**
     * Cancela el pedido y elimina los ingresos pendientes (confirmado = 0).
     * El stock nunca fue afectado, por lo que no hay nada que revertir.
     */
    private function cancelarPedido(int $id): bool
    {
        $this->conn->beginTransaction();
        try {
            $stmtDel = $this->conn->prepare(
                "DELETE FROM {$this->ingresoTable} WHERE id_pedido_proveedor = :id AND confirmado = 0"
            );
            $stmtDel->bindValue(':id', $id, PDO::PARAM_INT);
            $stmtDel->execute();

            $stmtPedido = $this->conn->prepare(
                "UPDATE {$this->table} SET estado = 'cancelado' WHERE id_pedido_proveedor = :id"
            );
            $stmtPedido->bindValue(':id', $id, PDO::PARAM_INT);
            $stmtPedido->execute();

            $this->conn->commit();
            return true;
        } catch (Throwable $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    /**
     * Elimina el pedido y sus ítems pendientes.
     * Solo permitido si el pedido NO está en estado 'recibido'
     * (los ya confirmados son ingresos reales de stock y no se pueden borrar desde aquí).
     */
    public function delete(int $id): bool
    {
        $current = $this->getById($id);
        if (!$current) {
            throw new \RuntimeException("Pedido con ID {$id} no encontrado.");
        }
        if ($current['estado'] === 'recibido') {
            throw new \RuntimeException("No se puede eliminar un pedido ya recibido porque tiene ingresos de stock confirmados.");
        }

        $this->conn->beginTransaction();
        try {
            $stmtDel = $this->conn->prepare(
                "DELETE FROM {$this->ingresoTable} WHERE id_pedido_proveedor = :id AND confirmado = 0"
            );
            $stmtDel->bindValue(':id', $id, PDO::PARAM_INT);
            $stmtDel->execute();

            $stmt = $this->conn->prepare(
                "DELETE FROM {$this->table} WHERE id_pedido_proveedor = :id"
            );
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $result = $stmt->execute();

            $this->conn->commit();
            return $result;
        } catch (Throwable $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }
}
