<?php

declare(strict_types=1);

/**
 * SimboloDiaController
 *
 * Devuelve el símbolo anti-falsificación correspondiente al día actual.
 * El símbolo rota diariamente de forma determinista usando el día del año.
 *
 * GET /simbolo-dia
 * Respuesta: { "simbolo": "%" }
 */
class SimboloDiaController
{
    /**
     * Caracteres especiales disponibles como símbolos del día.
     */
    public const SIMBOLOS = ['!', '#', '$', '%', '&', '/'];

    /**
     * Calcula y devuelve el símbolo del día en formato JSON.
     */
    public function obtener(): void
    {
        $simbolo = self::calcularSimboloDia();
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['simbolo' => $simbolo]);
        exit;
    }

    /**
     * Calcula el símbolo correspondiente a hoy.
     * Usa el día del año (0-364) módulo la cantidad de símbolos disponibles.
     */
    public static function calcularSimboloDia(): string
    {
        $tz         = new DateTimeZone('America/Argentina/Buenos_Aires');
        $ahora      = new DateTime('now', $tz);
        $diaDelAnio = (int) $ahora->format('z');
        return self::SIMBOLOS[$diaDelAnio % count(self::SIMBOLOS)];
    }

    /**
     * Devuelve el carácter símbolo del día.
     * Mantiene el nombre por compatibilidad con VentaController.
     */
    public static function obtenerArchivoSimboloDia(): string
    {
        return self::calcularSimboloDia();
    }
}
