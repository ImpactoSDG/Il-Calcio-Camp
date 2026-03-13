<?php

declare(strict_types=1);

/**
 * SimboloDiaController
 *
 * Devuelve el símbolo anti-falsificación correspondiente al día actual.
 * El símbolo rota automáticamente en base a los archivos PNG presentes
 * en /public/simbolos/, sin necesidad de configuración adicional.
 *
 * GET /simbolo-dia
 * Respuesta: { "simbolo": "estrella.png", "nombre": "estrella" }
 */
class SimboloDiaController
{
    /**
     * Lista canónica y ordenada de símbolos disponibles.
     * Al agregar una nueva imagen a /public/simbolos/, basta con añadir
     * su nombre (sin extensión) aquí para incorporarla a la rotación.
     */
    public const SIMBOLOS = [
        'estrella',
        'circulo',
        'triangulo',
        'diamante',
        'cuadrado',
        'cruz',
        'corazon',
        'luna',
        'sol',
        'flecha',
    ];

    /**
     * Calcula y devuelve el símbolo del día en formato JSON.
     */
    public function obtener(): void
    {
        $simbolo = self::calcularSimboloDia();
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'simbolo' => $simbolo . '.png',
            'nombre'  => $simbolo,
        ]);
        exit;
    }

    /**
     * Calcula el nombre (sin extensión) del símbolo correspondiente a hoy.
     * Usa el día del año (0-364) módulo la cantidad de símbolos disponibles.
     * La zona horaria es la misma que se usa en el resto del sistema.
     */
    public static function calcularSimboloDia(): string
    {
        $tz         = new DateTimeZone('America/Argentina/Buenos_Aires');
        $ahora      = new DateTime('now', $tz);
        $diaDelAnio = (int) $ahora->format('z'); // 0 = 1 ene, 364 = 31 dic
        return self::SIMBOLOS[$diaDelAnio % count(self::SIMBOLOS)];
    }

    /**
     * Devuelve el nombre del archivo PNG del símbolo del día (con extensión).
     * Útil para ser llamado desde otros controladores (ej.: VentaController).
     */
    public static function obtenerArchivoSimboloDia(): string
    {
        return self::calcularSimboloDia() . '.png';
    }
}
