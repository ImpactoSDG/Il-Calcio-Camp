<?php

declare(strict_types=1);

abstract class BaseController
{
    protected PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Envía una respuesta JSON estandarizada.
     */
    protected function respond(int $status_code, array $data): void
    {
        http_response_code($status_code);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($data);
        exit;
    }

    /**
     * Manejador centralizado de errores para controladores.
     */
    protected function handleError(Throwable $e, string $message = 'Error interno del servidor'): void
    {
        // En un entorno real, aquí registraríamos el error en un log: error_log($e->getMessage());
        $response = [
            'message' => $message,
            'error' => ($_ENV['APP_DEBUG'] ?? 'false') === 'true' ? $e->getMessage() : 'Ocurrió un error inesperado.'
        ];
        
        $this->respond(500, $response);
    }
}
