<?php

declare(strict_types=1);

class ArticuloResumenVenta
{
    private PDO $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    /**
     * Devuelve, para cada artículo activo:
     *   - cantidad_vendida : suma de ventas cuya fecha esté dentro del período [desde, hasta].
     *   - stock_disponible : total de ingresos acumulados hasta "hasta"
     *                        menos total de ventas acumuladas hasta "hasta".
     *
     * El stock es siempre acumulativo histórico (no solo del período).
     */
    public function getResumenPorPeriodo(string $fechaDesde, string $fechaHasta): array
    {
        $sql = "SELECT
                    a.id,
                    a.nombre,
                    a.url_imagen,
                    COALESCE((
                        SELECT SUM(av.cantidad)
                        FROM articulo_venta av
                        INNER JOIN venta v ON av.id_venta = v.id
                        WHERE av.id_articulo = a.id
                          AND v.fecha BETWEEN :fecha_desde AND :fecha_hasta_periodo
                    ), 0) AS cantidad_vendida,
                    COALESCE((
                        SELECT SUM(ia.cantidad)
                        FROM ingreso_articulo ia
                        LEFT JOIN pedido_proveedor pp ON ia.id_pedido_proveedor = pp.id_pedido_proveedor
                        WHERE ia.id_articulo = a.id
                          AND ia.fecha_ingreso <= :fecha_hasta_ingresos
                          AND (ia.id_pedido_proveedor IS NULL OR pp.estado = 'recibido')
                    ), 0)
                    -
                    COALESCE((
                        SELECT SUM(av2.cantidad)
                        FROM articulo_venta av2
                        INNER JOIN venta v2 ON av2.id_venta = v2.id
                        WHERE av2.id_articulo = a.id
                          AND v2.fecha <= :fecha_hasta_ventas
                    ), 0) AS stock_disponible
                FROM articulo a
                WHERE a.activo = 1
                ORDER BY a.nombre ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':fecha_desde',         $fechaDesde);
        $stmt->bindValue(':fecha_hasta_periodo',  $fechaHasta);
        $stmt->bindValue(':fecha_hasta_ingresos', $fechaHasta);
        $stmt->bindValue(':fecha_hasta_ventas',   $fechaHasta);
        $stmt->execute();

        return array_map(function (array $row): array {
            $row['cantidad_vendida']  = (float) $row['cantidad_vendida'];
            $row['stock_disponible']  = (float) $row['stock_disponible'];
            return $row;
        }, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }
}
