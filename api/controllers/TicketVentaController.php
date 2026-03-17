<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/SimboloDiaController.php';

use Dompdf\Dompdf;
use Dompdf\Options;

class TicketVentaController
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Genera y descarga el ticket PDF de una venta cerrada.
     * Método: GET /ticket-venta?id={idVenta}
     */
    public function generar(): void
    {
        $idVenta = isset($_GET['id']) ? (int) $_GET['id'] : 0;

        if ($idVenta <= 0) {
            http_response_code(400);
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['message' => 'ID de venta inválido o ausente.']);
            exit;
        }

        $venta      = $this->obtenerVenta($idVenta);
        $articulos  = $this->obtenerArticulos($idVenta);
        $medioCobro = $this->obtenerMedioCobro($idVenta);
        $config     = $this->obtenerConfiguracion();

        if (!$venta) {
            http_response_code(404);
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['message' => 'Venta no encontrada.']);
            exit;
        }

        $html = $this->generarHtml($venta, $articulos, $medioCobro, $config);

        $options = new Options();
        $options->set('defaultFont', 'Helvetica');
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');

        // Ancho de ticket térmico: 80 mm ≈ 226,77 puntos. Alto variable.
        $dompdf->setPaper([0, 0, 226.77, 1400]);
        $dompdf->render();

        if (ob_get_length()) {
            ob_end_clean();
        }

        $nombreArchivo = 'ticket_venta_' . $idVenta . '.pdf';
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $nombreArchivo . '"');
        header('Cache-Control: no-cache, no-store');
        echo $dompdf->output();
        exit;
    }

    // ──────────────────────────────────────────────────────────────────────
    // Consultas a base de datos
    // ──────────────────────────────────────────────────────────────────────

    private function obtenerVenta(int $idVenta): ?array
    {
        $sql = "SELECT v.id, v.fecha, v.descripcion_cliente, v.simbolo, v.tipo_vta,
                       c.nombre_cliente,
                       e.nombre AS equipo_nombre,
                       ev.descripcion AS estado_descripcion
                FROM venta v
                LEFT JOIN cliente c      ON v.id_cliente     = c.id
                LEFT JOIN equipo e       ON v.id_equipo      = e.id
                LEFT JOIN estado_venta ev ON v.id_estado_venta = ev.id
                WHERE v.id = :id
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $idVenta, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function obtenerArticulos(int $idVenta): array
    {
        $sql = "SELECT a.nombre AS articulo_nombre,
                       av.cantidad,
                       av.precio_unitario,
                       av.total
                FROM articulo_venta av
                INNER JOIN articulo a ON av.id_articulo = a.id
                WHERE av.id_venta = :id_venta
                ORDER BY av.id_articulo_venta ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_venta', $idVenta, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function obtenerMedioCobro(int $idVenta): string
    {
        $sql = "SELECT mc.descripcion
                FROM venta_cobro vc
                INNER JOIN medio_cobro mc ON vc.id_medio_pago = mc.id
                WHERE vc.id_venta = :id_venta
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_venta', $idVenta, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['descripcion'] : '—';
    }

    private function obtenerConfiguracion(): array
    {
        $sql = "SELECT clave, valor FROM configuraciones";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $config = [];
        foreach ($rows as $row) {
            $config[$row['clave']] = $row['valor'];
        }
        return $config;
    }

    // ──────────────────────────────────────────────────────────────────────
    // Generación del HTML del ticket
    // ──────────────────────────────────────────────────────────────────────

    private function generarHtml(array $venta, array $articulos, string $medioCobro, array $config): string
    {
        $nombreEmpresa = $config['nombre_empresa'] ?? 'IL CALCIO CAMP';

        // Determinar qué símbolo mostrar: el de la venta o el del día actual
        $simbolo = !empty($venta['simbolo']) ? $venta['simbolo'] : SimboloDiaController::calcularSimboloDia();
        // El signo $ se sigue usando como moneda en los precios
        $moneda = '$';

        $dtz    = new DateTimeZone('America/Argentina/Buenos_Aires');
        $ahora  = (new DateTime('now', $dtz))->format('d/m/Y H:i');
        $fecha  = !empty($venta['fecha'])
            ? date('d/m/Y', strtotime($venta['fecha']))
            : '—';

        $cliente = $venta['nombre_cliente'] ?: ($venta['descripcion_cliente'] ?: '—');
        $equipo  = $venta['equipo_nombre'] ?: '—';

        // Filas de artículos
        $filas = '';
        $total = 0.0;
        foreach ($articulos as $art) {
            $nombre    = htmlspecialchars($art['articulo_nombre'], ENT_QUOTES, 'UTF-8');
            $cantidad  = number_format((float) $art['cantidad'], 2, ',', '.');
            $precioU   = number_format((float) $art['precio_unitario'], 2, ',', '.');
            $subtotal  = (float) $art['total'];
            $total    += $subtotal;
            $subtotalF = number_format($subtotal, 2, ',', '.');

            $filas .= "
            <tr>
                <td colspan='2' class='art-nombre'>{$nombre}</td>
            </tr>
            <tr>
                <td class='art-detalle'>{$cantidad} x {$moneda} {$precioU}</td>
                <td class='art-sub'>{$moneda} {$subtotalF}</td>
            </tr>
            <tr><td colspan='2' class='separador-item'></td></tr>";
        }

        $totalF = number_format($total, 2, ',', '.');

        return "<!DOCTYPE html>
<html lang='es'>
<head>
<meta charset='UTF-8'>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        font-family: Helvetica, Arial, sans-serif;
        font-size: 11px;
        color: #111;
        width: 100%;
        padding: 6px 8px;
    }
    .header {
        text-align: center;
        border-bottom: 2px solid #111;
        padding-bottom: 6px;
        margin-bottom: 8px;
    }
    .empresa {
        font-size: 15px;
        font-weight: bold;
        letter-spacing: 1px;
        text-transform: uppercase;
    }
    .subtitulo {
        font-size: 9px;
        color: #555;
        margin-top: 2px;
    }
    .meta {
        font-size: 9px;
        text-align: center;
        color: #444;
        margin-bottom: 8px;
    }
    .seccion {
        border-top: 1px dashed #111;
        margin: 6px 0;
        padding-top: 6px;
    }
    .dato {
        display: block;
        margin-bottom: 3px;
    }
    .label { font-weight: bold; }
    table.items {
        width: 100%;
        border-collapse: collapse;
        margin-top: 4px;
    }
    .art-nombre {
        font-weight: bold;
        padding-top: 5px;
        padding-bottom: 1px;
        font-size: 10.5px;
    }
    .art-detalle {
        color: #333;
        padding-bottom: 2px;
        font-size: 10px;
    }
    .art-sub {
        text-align: right;
        font-weight: bold;
        padding-bottom: 2px;
        font-size: 10px;
    }
    .separador-item {
        height: 2px;
        border-bottom: 1px dotted #ccc;
    }
    .total-section {
        border-top: 2px solid #111;
        margin-top: 8px;
        padding-top: 6px;
    }
    .total-row {
        width: 100%;
        border-collapse: collapse;
    }
    .total-label {
        font-weight: bold;
        font-size: 12px;
    }
    .total-monto {
        text-align: right;
        font-weight: bold;
        font-size: 13px;
    }
    .footer {
        text-align: center;
        margin-top: 12px;
        font-size: 9px;
        color: #666;
        border-top: 1px dashed #aaa;
        padding-top: 6px;
    }
    .numero-venta {
        text-align: center;
        font-weight: bold;
        font-size: 13px;
        letter-spacing: 1px;
        margin-bottom: 6px;
    }
</style>
</head>
<body>

    <div class='header'>
        <div class='empresa'>{$nombreEmpresa}</div>
    </div>

    <div class='meta'>
        Impreso: {$ahora}
    </div>
    <div class='numero-venta'>
        Venta N° {$venta['id']} &ndash; {$simbolo}
    </div>

    <div class='seccion'>
        <span class='dato'><span class='label'>Fecha:</span> {$fecha}</span>
        <span class='dato'><span class='label'>Cliente:</span> {$cliente}</span>
        <span class='dato'><span class='label'>Equipo:</span> {$equipo}</span>
        <span class='dato'><span class='label'>Medio de pago:</span> {$medioCobro}</span>
    </div>

    <div class='seccion'>
        <table class='items'>
            <tbody>
                {$filas}
            </tbody>
        </table>
    </div>

    <div class='total-section'>
        <table class='total-row'>
            <tr>
                <td class='total-label'>TOTAL</td>
                <td class='total-monto'>{$moneda} {$totalF}</td>
            </tr>
        </table>
    </div>

    <div class='footer'>
        ¡Gracias por tu compra!<br>
        {$nombreEmpresa}
    </div>

</body>
</html>";
    }
}
