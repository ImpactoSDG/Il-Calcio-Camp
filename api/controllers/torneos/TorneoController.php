<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseController.php';
require_once __DIR__ . '/../../models/torneos/Torneo.php';

class TorneoController extends BaseController
{
    private Torneo $model;

    public function __construct(PDO $db)
    {
        parent::__construct($db);
        $this->model = new Torneo($this->db);
    }

    public function getAll(): void
    {
        try {
            $this->respond(200, $this->model->getAll());
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al obtener torneos');
        }
    }

    public function getById(): void
    {
        try {
            $id = $_GET['id'] ?? null;
            if (!$id) {
                $this->respond(400, ['message' => 'ID requerido.']);
            }
            $result = $this->model->getById((int)$id);
            $result ? $this->respond(200, $result) : $this->respond(404, ['message' => 'Torneo no encontrado.']);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al obtener torneo');
        }
    }

    public function getDashboard(): void
    {
        try {
            $idTorneo = isset($_GET['id_torneo']) ? (int)$_GET['id_torneo'] : 0;
            if ($idTorneo <= 0) {
                $this->respond(400, ['message' => 'id_torneo es obligatorio.']);
            }

            $result = $this->model->getDashboardById($idTorneo);
            $result ? $this->respond(200, $result) : $this->respond(404, ['message' => 'Torneo no encontrado.']);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al obtener dashboard del torneo');
        }
    }

    public function delete(): void
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $id = $this->resolveDeleteId($data);
            $force = $this->resolveForceDelete($data);
            $motivo = is_array($data) && isset($data['motivo_baja']) ? trim((string)$data['motivo_baja']) : null;

            if ($id === null) {
                $this->respond(400, ['message' => 'ID de torneo requerido para eliminar.']);
            }

            if (!$this->model->exists($id, true)) {
                $this->respond(404, ['message' => 'Torneo no encontrado.']);
            }

            if ($force) {
                $deleted = $this->model->deleteCascade($id);
                $this->respond(200, [
                    'message' => 'Torneo y dependencias eliminados definitivamente.',
                    'id_torneo' => $id,
                    'mode' => 'hard',
                    'deleted' => $deleted,
                ]);
            }

            $deletedBy = $this->resolveDeletedBy();
            $ok = $this->model->softDelete($id, $deletedBy, $motivo);

            if (!$ok) {
                $this->respond(409, ['message' => 'No se pudo eliminar el torneo (puede estar ya eliminado).']);
            }

            $this->respond(200, [
                'message' => 'Torneo eliminado lógicamente correctamente.',
                'id_torneo' => $id,
                'mode' => 'soft',
            ]);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al eliminar torneo');
        }
    }

    private function resolveDeleteId(?array $data): ?int
    {
        if (isset($_GET['id']) && (int)$_GET['id'] > 0) {
            return (int)$_GET['id'];
        }

        if (is_array($data) && isset($data['id']) && (int)$data['id'] > 0) {
            return (int)$data['id'];
        }

        return null;
    }

    private function resolveForceDelete(?array $data): bool
    {
        if (isset($_GET['force'])) {
            return in_array(strtolower((string)$_GET['force']), ['1', 'true', 'yes'], true);
        }

        if (is_array($data) && isset($data['force'])) {
            $force = $data['force'];
            if (is_bool($force)) {
                return $force;
            }
            return in_array(strtolower((string)$force), ['1', 'true', 'yes'], true);
        }

        return false;
    }

    private function resolveDeletedBy(): ?int
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
        if (!str_starts_with($authHeader, 'Bearer ')) {
            return null;
        }

        $token = substr($authHeader, 7);
        $payload = JwtHandler::decode($token);
        if (!is_array($payload)) {
            return null;
        }

        $id = isset($payload['id']) ? (int)$payload['id'] : 0;
        return $id > 0 ? $id : null;
    }
}