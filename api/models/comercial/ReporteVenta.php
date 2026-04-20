<?php

declare(strict_types=1);

class ReporteVenta
{
    private PDO $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    /**
     * Retorna el resumen del período: monto total, ticket promedio y cantidad de ventas.
     * Solo considera ventas activas (activo = 1).
     */
    public function getResumen(string $desde, string $hasta): array
    {
        $sql = "SELECT
                    COUNT(sub.id)                          AS cantidad_ventas,
                    COALESCE(SUM(sub.total_venta), 0)      AS monto_total,
                    COALESCE(AVG(sub.total_venta), 0)      AS ticket_promedio
                FROM (
                    SELECT v.id,
                           COALESCE(SUM(av.total), 0) AS total_venta
                    FROM venta v
                    LEFT JOIN articulo_venta av ON av.id_venta = v.id
                    WHERE v.activo = 1
                      AND DATE(v.fecha) BETWEEN :desde AND :hasta
                    GROUP BY v.id
                ) AS sub";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':desde', $desde);
        $stmt->bindValue(':hasta', $hasta);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [
            'cantidad_ventas' => 0,
            'monto_total'     => 0,
            'ticket_promedio' => 0,
        ];
    }

    /**
     * Retorna los artículos más vendidos en el período, ordenados por cantidad descendente.
     * Solo considera ventas activas (activo = 1).
     */
    public function getProductosMasVendidos(string $desde, string $hasta, int $limite = 15): array
    {
        $sql = "SELECT
                    a.id,
                    a.nombre                       AS nombre_articulo,
                    COALESCE(SUM(av.cantidad), 0)  AS cantidad_vendida,
                    COALESCE(SUM(av.total), 0)     AS monto_total
                FROM articulo_venta av
                INNER JOIN articulo a  ON av.id_articulo = a.id
                INNER JOIN venta v     ON av.id_venta    = v.id
                WHERE v.activo = 1
                  AND DATE(v.fecha) BETWEEN :desde AND :hasta
                GROUP BY a.id, a.nombre
                ORDER BY cantidad_vendida DESC
                LIMIT :limite";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':desde', $desde);
        $stmt->bindValue(':hasta', $hasta);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
