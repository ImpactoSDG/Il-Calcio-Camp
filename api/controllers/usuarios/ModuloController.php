<?php

declare(strict_types=1);

include_once __DIR__ . '/../../core/Database.php';
include_once __DIR__ . '/../../models/usuarios/Modulo.php';

class ModuloController
{
    private PDO $db;
    private Modulo $moduloModel;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
        $this->moduloModel = new Modulo($this->db);
    }

    private function respond(int $code, array $data): void
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    public function update(): void
    {
        $data = json_decode(file_get_contents("php://input"), true);

        // Validación básica de ID
        if (empty($data['id'])) {
            $this->respond(400, ['message' => 'El ID del módulo es obligatorio para actualizar.']);
        }

        $id = (int)$data['id'];
        $categoria = $data['categoria'] ?? null;
        $orden = isset($data['orden_visualizacion']) ? (int)$data['orden_visualizacion'] : null;
        $icon = $data['icon'] ?? null;
        $bg = $data['bg'] ?? null;

        try {
            $success = $this->moduloModel->updateProperties($id, $categoria, $orden, $icon, $bg);

            if ($success) {
                $this->respond(200, ['message' => 'Propiedades del módulo actualizadas correctamente.']);
            } else {
                $this->respond(500, ['message' => 'No se pudo realizar la actualización en la base de datos.']);
            }
        } catch (Exception $e) {
            $this->respond(500, [
                'message' => 'Error interno del servidor.',
                'error' => $e->getMessage()
            ]);
        }
    }
}