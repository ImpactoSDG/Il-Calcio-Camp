<?php

declare(strict_types=1);

require_once __DIR__ . '/../../models/comercial/ReporteVenta.php';

class ReporteVentaController extends BaseController
{
    private ReporteVenta $model;

    public function __construct(PDO $db)
    {
        parent::__construct($db);
        $this->model = new ReporteVenta($db);
    }

    /**
     * GET /reportes-venta?desde=YYYY-MM-DD&hasta=YYYY-MM-DD
     * Retorna resumen + productos más vendidos del período.
     */
    public function getReporte(): void
    {
        try {
            $hoy  = date('Y-m-d');
            $desde = $_GET['desde'] ?? $hoy;
            $hasta = $_GET['hasta'] ?? $hoy;

            if (!$this->esFechaValida($desde) || !$this->esFechaValida($hasta)) {
                $this->respond(400, ['message' => 'Fechas inválidas. Formato esperado: YYYY-MM-DD.']);
                return;
            }

            if ($desde > $hasta) {
                $this->respond(400, ['message' => 'La fecha "desde" no puede ser posterior a "hasta".']);
                return;
            }

            $resumen   = $this->model->getResumen($desde, $hasta);
            $productos = $this->model->getProductosMasVendidos($desde, $hasta);

            $this->respond(200, [
                'resumen'                => $resumen,
                'productos_mas_vendidos' => $productos,
                'filtros'                => ['desde' => $desde, 'hasta' => $hasta],
            ]);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al obtener el reporte de ventas.');
        }
    }

    private function esFechaValida(string $fecha): bool
    {
        $d = DateTime::createFromFormat('Y-m-d', $fecha);
        return $d && $d->format('Y-m-d') === $fecha;
    }
}
