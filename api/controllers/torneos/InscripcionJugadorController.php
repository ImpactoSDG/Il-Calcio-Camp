<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseController.php';
require_once __DIR__ . '/../../models/torneos/InscripcionJugador.php';
require_once __DIR__ . '/../../models/torneos/InscripcionEquipo.php';

class InscripcionJugadorController extends BaseController
{
    private InscripcionJugador $model;
    private InscripcionEquipo $inscripcionModel;

    public function __construct(PDO $db)
    {
        parent::__construct($db);
        $this->model = new InscripcionJugador($this->db);
        $this->inscripcionModel = new InscripcionEquipo($this->db);
    }

    public function getByInscripcion(): void
    {
        try {
            $idInscripcion = isset($_GET['id_inscripcion']) ? (int)$_GET['id_inscripcion'] : null;
            if (!$idInscripcion) {
                $this->respond(400, ['message' => 'id_inscripcion requerido.']);
            }
            $result = $this->model->getByInscripcion((int)$idInscripcion);
            $this->respond(200, $result);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al obtener jugadores de inscripción');
        }
    }

    public function setEstadoDocumentacion(): void
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $id = isset($data['id']) ? (int)$data['id'] : null;
            $estado = isset($data['estado_documentacion']) ? (string)$data['estado_documentacion'] : null;

            if (!$id || !$estado) {
                $this->respond(400, ['message' => 'ID y estado_documentacion requeridos.']);
            }

            $estadosValidos = ['pendiente', 'aprobada', 'rechazada'];
            if (!in_array($estado, $estadosValidos)) {
                $this->respond(400, ['message' => 'estado_documentacion inválido.']);
            }

            $jugador = $this->model->getById((int)$id);
            if (!$jugador) {
                $this->respond(404, ['message' => 'Jugador de inscripción no encontrado.']);
            }

            if ($this->model->setEstadoDocumentacion((int)$id, $estado)) {
                $this->respond(200, ['message' => 'Estado de documentación actualizado.']);
            } else {
                $this->respond(500, ['message' => 'Error al actualizar estado de documentación.']);
            }
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al actualizar documentación');
        }
    }
}
