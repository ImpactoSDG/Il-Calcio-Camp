<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../models/Configuracion.php';

class ConfiguracionController
{
    private PDO $db;
    private Configuracion $configModel;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
        $this->configModel = new Configuracion($this->db);
    }

    private function respond(int $status_code, array $data): void
    {
        http_response_code($status_code);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($data);
        exit;
    }

    public function getAll(): void
    {
        $result = $this->configModel->getAll();
        $this->respond(200, $result);
    }

    public function getById(): void
    {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            $this->respond(400, ['message' => 'ID de configuración requerido.']);
        }

        $result = $this->configModel->getById((int)$id);
        if ($result) {
            $this->respond(200, $result);
        } else {
            $this->respond(404, ['message' => 'Configuración no encontrada.']);
        }
    }

    public function store(): void
    {
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['clave']) || empty($data['valor'])) {
            $this->respond(400, ['message' => 'Clave y valor son requeridos.']);
        }

        //Validar si la clave ya existe para evitar duplicados
        if ($this->configModel->getValorPorClave($data['clave'])) {
            $this->respond(400, ['message' => 'La clave de configuración ya existe.']);
        }

        $success = $this->configModel->create(
            $data['clave'],
            $data['valor'],
            $data['descripcion'] ?? null
        );

        if ($success) {
            $this->respond(201, ['message' => 'Configuración creada con éxito.']);
        } else {
            $this->respond(500, ['message' => 'Error al crear la configuración.']);
        }
    }

    public function update(): void
    {
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['id']) || empty($data['valor'])) {
            $this->respond(400, ['message' => 'ID y valor son requeridos para actualizar.']);
        }

        $success = $this->configModel->update(
            (int)$data['id'],
            $data['valor'],
            $data['descripcion'] ?? null
        );

        if ($success) {
            $this->respond(200, ['message' => 'Configuración actualizada con éxito.']);
        } else {
            $this->respond(500, ['message' => 'Error al actualizar la configuración.']);
        }
    }

    public function delete(): void
    {
        $data = json_decode(file_get_contents("php://input"), true);
        $id = $data['id'] ?? null;

        if (!$id) {
            $this->respond(400, ['message' => 'ID requerido para eliminar.']);
        }

        if ($this->configModel->delete((int)$id)) {
            $this->respond(200, ['message' => 'Configuración eliminada.']);
        } else {
            $this->respond(500, ['message' => 'Error al eliminar la configuración.']);
        }
    }
}