<?php

declare(strict_types=1);

require_once __DIR__ . '/../../models/comercial/Venta.php';

/**
 * Tests de integración del modelo Venta contra la base de test.
 *
 * Regresión del Bug 2 (lado backend): al guardar una venta con un cliente rápido
 * (payload "nuevo_cliente"), el cliente debe crearse dentro de la transacción y
 * la venta debe quedar con un id_cliente real (FK satisfecha). También verifica
 * que el cuit_dni del cliente rápido se persista (passthrough que agregamos).
 *
 * Las ventas se crean en estado PAUSA para no depender de stock/lotes ni cobros:
 * así el test se enfoca en la creación del cliente + cabecera + artículos.
 */
final class VentaTest extends DatabaseTestCase
{
    private function datosVentaConClienteRapido(int $idArticulo, int $estadoId, ?int $condicionId): array
    {
        return [
            'fecha' => date('Y-m-d'),
            'id_estado_venta' => $estadoId,
            // Igualar estado y "pausa" fuerza esPausa = true → sin descuento de stock ni cobro.
            'id_estado_pausa' => $estadoId,
            'id_estado_cerrada' => -1,
            'simbolo' => 'test.png',
            'descripcion_cliente' => 'Venta test cliente rápido',
            'id_equipo' => null,
            'id_cliente' => null,
            'nuevo_cliente' => [
                'nombre_cliente' => 'Cliente Rápido Test',
                'id_condicion_iva_receptor' => $condicionId,
                'cuit_dni' => '20345678901',
                'telefono' => '',
            ],
        ];
    }

    public function testCreaVentaConClienteRapidoYSatisfaceLaFk(): void
    {
        $estadoId = $this->primerId('estado_venta');
        if ($estadoId === null) {
            $this->markTestSkipped('La base de test no tiene estados de venta cargados.');
        }
        $condicionId = $this->primerId('condicion_iva_receptor');
        $idArticulo = $this->crearArticuloDeTest();

        $data = $this->datosVentaConClienteRapido($idArticulo, $estadoId, $condicionId);
        $articulos = [
            ['id_articulo' => $idArticulo, 'cantidad' => 2, 'precio_unitario' => 100, 'iva' => 0, 'total' => 200],
        ];

        $model = new Venta($this->pdo);
        $resultado = $model->createWithDetails($data, $articulos, 0);

        // Registrar para limpieza antes de aserciones (aunque fallen).
        if (!empty($resultado['id'])) {
            $this->ventasCreadas[] = (int) $resultado['id'];
        }
        if (!empty($resultado['id_cliente'])) {
            $this->clientesCreados[] = (int) $resultado['id_cliente'];
        }

        $this->assertTrue($resultado['success'], 'La venta con cliente rápido debe crearse sin error de FK.');

        $idClienteVenta = $this->pdo
            ->query('SELECT id_cliente FROM venta WHERE id = ' . (int) $resultado['id'])
            ->fetchColumn();

        $this->assertNotEmpty($idClienteVenta, 'La venta debe quedar con un id_cliente real.');
        $this->assertEquals((int) $resultado['id_cliente'], (int) $idClienteVenta);

        // Passthrough del cuit_dni del cliente rápido.
        $cuitCliente = $this->pdo
            ->query('SELECT cuit_dni FROM cliente WHERE id = ' . (int) $resultado['id_cliente'])
            ->fetchColumn();
        $this->assertEquals(20345678901, $cuitCliente);
    }

    public function testActualizaVentaCreandoClienteRapido(): void
    {
        $estadoId = $this->primerId('estado_venta');
        if ($estadoId === null) {
            $this->markTestSkipped('La base de test no tiene estados de venta cargados.');
        }
        $condicionId = $this->primerId('condicion_iva_receptor');
        $idArticulo = $this->crearArticuloDeTest();

        $model = new Venta($this->pdo);

        // 1. Crear una venta base sin cliente.
        $dataBase = [
            'fecha' => date('Y-m-d'),
            'id_estado_venta' => $estadoId,
            'id_estado_pausa' => $estadoId,
            'id_estado_cerrada' => -1,
            'simbolo' => 'test.png',
            'descripcion_cliente' => 'Venta base',
            'id_equipo' => null,
            'id_cliente' => null,
        ];
        $articulos = [
            ['id_articulo' => $idArticulo, 'cantidad' => 1, 'precio_unitario' => 100, 'iva' => 0, 'total' => 100],
        ];
        $creacion = $model->createWithDetails($dataBase, $articulos, 0);
        $this->assertTrue($creacion['success']);
        $idVenta = (int) $creacion['id'];
        $this->ventasCreadas[] = $idVenta;

        // 2. Editar la venta agregando un cliente rápido (payload nuevo_cliente).
        $dataUpdate = $this->datosVentaConClienteRapido($idArticulo, $estadoId, $condicionId);
        $dataUpdate['id'] = $idVenta;
        $resultado = $model->updateWithDetails($dataUpdate, $articulos, 0);

        $this->assertTrue($resultado['success'], 'La edición con cliente rápido debe persistir el cliente.');

        $idClienteVenta = $this->pdo
            ->query('SELECT id_cliente FROM venta WHERE id = ' . $idVenta)
            ->fetchColumn();

        $this->assertNotEmpty($idClienteVenta, 'Tras editar, la venta debe tener un id_cliente real (no null).');
        $this->clientesCreados[] = (int) $idClienteVenta;
    }
}
