<?php

declare(strict_types=1);

require_once __DIR__ . '/../../models/comercial/Cliente.php';

/**
 * Tests de integración del modelo Cliente contra la base de test.
 * Regresión fiel del Bug 1: crear un cliente con cuit_dni vacío no debe romper
 * el INSERT y debe quedar guardado como NULL (columna entera en modo estricto).
 */
final class ClienteTest extends DatabaseTestCase
{
    public function testCrearClienteConCuitVacioLoGuardaComoNull(): void
    {
        $model = new Cliente($this->pdo);

        // Antes del fix, esto lanzaba: "Incorrect integer value: '' for column 'cuit_dni'".
        $idCliente = $model->create(
            null,
            'Cliente Cuit Vacío Test',
            $this->primerId('condicion_iva_receptor'),
            null,
            null,
            '',   // cuit_dni vacío
            null
        );

        $this->assertIsInt($idCliente);
        $this->assertGreaterThan(0, $idCliente);
        $this->clientesCreados[] = $idCliente;

        $cuitGuardado = $this->pdo
            ->query('SELECT cuit_dni FROM cliente WHERE id = ' . $idCliente)
            ->fetchColumn();

        $this->assertNull($cuitGuardado, 'El cuit_dni vacío debe guardarse como NULL.');
    }

    public function testCrearClienteConCuitValidoLoPersiste(): void
    {
        $model = new Cliente($this->pdo);

        $idCliente = $model->create(
            null,
            'Cliente Cuit Válido Test',
            $this->primerId('condicion_iva_receptor'),
            null,
            null,
            '20346693364',
            null
        );

        $this->assertIsInt($idCliente);
        $this->clientesCreados[] = $idCliente;

        $cuitGuardado = $this->pdo
            ->query('SELECT cuit_dni FROM cliente WHERE id = ' . $idCliente)
            ->fetchColumn();

        $this->assertEquals(20346693364, $cuitGuardado);
    }
}
