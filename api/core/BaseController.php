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
        error_log("[Controller Error] " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
        error_log("[Stack Trace] " . $e->getTraceAsString());
        
        $response = [
            'message' => $message,
            'error' => $e->getMessage()
        ];
        
        $this->respond(500, $response);
    }
}
