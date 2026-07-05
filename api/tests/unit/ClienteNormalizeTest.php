<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../models/comercial/Cliente.php';

/**
 * Test puro (sin base de datos) de la normalización de cuit_dni.
 * Feedback instantáneo del Bug 1: el string vacío debe convertirse en NULL
 * antes de tocar la columna entera cuit_dni.
 */
final class ClienteNormalizeTest extends TestCase
{
    public function testStringVacioSeConvierteEnNull(): void
    {
        $this->assertNull(Cliente::normalizarCuitDni(''));
    }

    public function testNullSigueSiendoNull(): void
    {
        $this->assertNull(Cliente::normalizarCuitDni(null));
    }

    public function testCuitValidoSeConvierteEnEntero(): void
    {
        $resultado = Cliente::normalizarCuitDni('20346693364');
        $this->assertSame(20346693364, $resultado);
    }

    public function testEnteroSeMantiene(): void
    {
        $this->assertSame(12345678, Cliente::normalizarCuitDni(12345678));
    }
}
