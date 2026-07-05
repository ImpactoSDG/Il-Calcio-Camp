<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Clase base para los tests de integración que tocan la base de datos.
 *
 * Estrategia de aislamiento: LIMPIEZA SELECTIVA POR ID (no truncado).
 * Cada test registra los ids que crea; en tearDown se borran solo esas filas,
 * en orden seguro para las foreign keys. Esto permite correr los tests sobre
 * una copia local de la base de producción sin destruir sus datos.
 */
abstract class DatabaseTestCase extends TestCase
{
    protected PDO $pdo;

    /** Ids creados durante el test, para limpieza en tearDown. */
    protected array $ventasCreadas = [];
    protected array $clientesCreados = [];
    protected array $articulosCreados = [];

    protected function setUp(): void
    {
        $this->pdo = TestDatabase::pdo();
    }

    protected function tearDown(): void
    {
        // Orden: primero ventas (y sus dependientes), luego artículos y clientes.
        foreach ($this->ventasCreadas as $idVenta) {
            $this->eliminarVentaCompleta((int) $idVenta);
        }
        foreach ($this->articulosCreados as $idArticulo) {
            $this->pdo->exec('DELETE FROM articulo WHERE id = ' . (int) $idArticulo);
        }
        foreach ($this->clientesCreados as $idCliente) {
            $this->eliminarCliente((int) $idCliente);
        }

        $this->ventasCreadas = [];
        $this->articulosCreados = [];
        $this->clientesCreados = [];
    }

    /**
     * Devuelve el primer id disponible de una tabla de catálogo, o null si está vacía.
     * Útil para no hardcodear ids de estados/condiciones en los tests.
     */
    protected function primerId(string $tabla, string $columna = 'id'): ?int
    {
        $valor = $this->pdo->query("SELECT {$columna} FROM {$tabla} LIMIT 1")->fetchColumn();
        return $valor === false ? null : (int) $valor;
    }

    /**
     * Crea un artículo mínimo para usarlo en ventas de test. Se limpia en tearDown.
     */
    protected function crearArticuloDeTest(string $nombre = 'ARTICULO TEST PHPUNIT'): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO articulo (nombre, precio_actual, costo_actual, activo) VALUES (:n, 100, 50, 1)'
        );
        $stmt->execute([':n' => $nombre]);
        $id = (int) $this->pdo->lastInsertId();
        $this->articulosCreados[] = $id;
        return $id;
    }

    /**
     * Borra un cliente y sus asociaciones de equipo.
     */
    protected function eliminarCliente(int $id): void
    {
        $this->pdo->exec('DELETE FROM cliente_equipo WHERE id_cliente = ' . $id);
        $this->pdo->exec('DELETE FROM cliente WHERE id = ' . $id);
    }

    /**
     * Borra una venta y todas sus filas dependientes en orden seguro para las FK:
     * relaciones de stock → venta_cobro → cobro → articulo_venta → venta.
     */
    protected function eliminarVentaCompleta(int $idVenta): void
    {
        $cobros = $this->pdo
            ->query('SELECT id_cobro FROM venta_cobro WHERE id_venta = ' . $idVenta)
            ->fetchAll(PDO::FETCH_COLUMN);

        $this->pdo->exec(
            'DELETE FROM articulo_venta_ingreso_articulo
             WHERE articulo_venta_id_articulo_venta IN
                   (SELECT id_articulo_venta FROM articulo_venta WHERE id_venta = ' . $idVenta . ')'
        );
        $this->pdo->exec('DELETE FROM venta_cobro WHERE id_venta = ' . $idVenta);
        foreach ($cobros as $idCobro) {
            $this->pdo->exec('DELETE FROM cobro WHERE id = ' . (int) $idCobro);
        }
        $this->pdo->exec('DELETE FROM articulo_venta WHERE id_venta = ' . $idVenta);
        $this->pdo->exec('DELETE FROM venta WHERE id = ' . $idVenta);
    }
}
