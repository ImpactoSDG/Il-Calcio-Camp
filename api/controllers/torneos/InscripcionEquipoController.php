<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseController.php';
require_once __DIR__ . '/../../models/torneos/InscripcionEquipo.php';
require_once __DIR__ . '/../../models/torneos/InscripcionJugador.php';
require_once __DIR__ . '/../../models/torneos/Equipo.php';
require_once __DIR__ . '/../../models/torneos/Jugador.php';
require_once __DIR__ . '/../../models/auth/UsuarioWeb.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailerException;

class InscripcionEquipoController extends BaseController
{
    private InscripcionEquipo $model;
    private InscripcionJugador $jugadorModel;

    public function __construct(PDO $db)
    {
        parent::__construct($db);
        $this->model = new InscripcionEquipo($this->db);
        $this->jugadorModel = new InscripcionJugador($this->db);
    }

    public function getAll(): void
    {
        try {
            $idTorneo = isset($_GET['id_torneo']) ? (int)$_GET['id_torneo'] : null;
            $idEstado = isset($_GET['id_estado']) ? (int)$_GET['id_estado'] : null;
            $result = $this->model->getAll($idTorneo, $idEstado);
            $this->respond(200, $result);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al obtener inscripciones');
        }
    }

    public function getById(): void
    {
        try {
            $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
            if (!$id) {
                $this->respond(400, ['message' => 'ID requerido.']);
            }

            $inscripcion = $this->model->getById((int)$id);
            if (!$inscripcion) {
                $this->respond(404, ['message' => 'Inscripción no encontrada.']);
            }

            $inscripcion['jugadores'] = $this->jugadorModel->getByInscripcion((int)$id);
            $this->respond(200, $inscripcion);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al obtener inscripción');
        }
    }

    public function aprobar(): void
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $id = isset($data['id']) ? (int)$data['id'] : null;
            $emailBody = isset($data['email_body']) ? trim((string)$data['email_body']) : '';

            if (!$id) {
                $this->respond(400, ['message' => 'ID requerido.']);
            }

            $inscripcion = $this->model->getById((int)$id);
            if (!$inscripcion) {
                $this->respond(404, ['message' => 'Inscripción no encontrada.']);
            }
            if ((int)$inscripcion['id_estado'] === InscripcionEquipo::ESTADO_APROBADA) {
                $this->respond(409, ['message' => 'La inscripción ya fue aprobada.']);
            }

            $jugadores = $this->jugadorModel->getByInscripcion((int)$id);

            $this->db->beginTransaction();

            // 1. Crear equipo
            $equipoModel = new Equipo($this->db);
            $idEquipo = $equipoModel->create(
                $inscripcion['nombre_equipo'],
                (int)($data['id_disciplina'] ?? 1),
                true,
                null,
                1
            );

            if (!$idEquipo) {
                $this->db->rollBack();
                $this->respond(500, ['message' => 'Error al crear el equipo.']);
            }

            // Vincular delegado web al equipo
            $idSolicitante = (int)$inscripcion['id_usuario_web_solicitante'];
            $stmt = $this->db->prepare(
                'UPDATE equipo SET id_usuario_web_delegado = :id_uw WHERE id = :id'
            );
            $stmt->bindValue(':id_uw', $idSolicitante, PDO::PARAM_INT);
            $stmt->bindValue(':id', $idEquipo, PDO::PARAM_INT);
            $stmt->execute();

            // 2. Inscribir equipo en el torneo (equipo_torneo)
            $stmt = $this->db->prepare(
                'INSERT INTO equipo_torneo (id_equipo, id_torneo, id_estado_inscripcion, fecha_inscripcion)
                 VALUES (:ie, :it, 1, CURDATE())'
            );
            $stmt->bindValue(':ie', $idEquipo, PDO::PARAM_INT);
            $stmt->bindValue(':it', (int)$inscripcion['id_torneo'], PDO::PARAM_INT);
            $stmt->execute();

            // 3. Procesar jugadores
            $jugadorModel = new Jugador($this->db);
            $usuarioWebModel = new UsuarioWeb($this->db);

            foreach ($jugadores as $ij) {
                $jugadorExistente = $jugadorModel->getByDni((string)$ij['dni']);

                if ($jugadorExistente) {
                    $idJugador = (int)$jugadorExistente['id'];
                } else {
                    $stmtJ = $this->db->prepare(
                        'INSERT INTO jugador (nombre, apellido, dni, fecha_nac, fecha_alta, activo)
                         VALUES (:nombre, :apellido, :dni, :fecha_nac, :fecha_alta, 1)'
                    );
                    $stmtJ->bindValue(':nombre',     (string)$ij['nombre']);
                    $stmtJ->bindValue(':apellido',   (string)$ij['apellido']);
                    $stmtJ->bindValue(':dni',        (string)$ij['dni']);
                    $stmtJ->bindValue(':fecha_nac',  $ij['fecha_nac'] ?: null);
                    $stmtJ->bindValue(':fecha_alta', date('Y-m-d'));
                    if (!$stmtJ->execute()) {
                        $this->db->rollBack();
                        $this->respond(500, ['message' => 'Error al crear jugador: ' . $ij['nombre'] . ' ' . $ij['apellido']]);
                    }
                    $idJugador = (int)$this->db->lastInsertId();
                }

                $stmt = $this->db->prepare(
                    'INSERT INTO jugador_equipo (id_jugador, id_equipo, fecha_desde) VALUES (:ij, :ie, NOW())'
                );
                $stmt->bindValue(':ij', $idJugador, PDO::PARAM_INT);
                $stmt->bindValue(':ie', $idEquipo, PDO::PARAM_INT);
                $stmt->execute();

                $emailJugador = $ij['email'] ?? null;
                if ($emailJugador) {
                    $uw = $usuarioWebModel->getByEmail((string)$emailJugador);
                    if ($uw) {
                        $usuarioWebModel->vincularJugador((int)$uw['id'], $idJugador);
                    } else {
                        $tempPassword = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
                        $idNuevoUw = $usuarioWebModel->create((string)$emailJugador, $tempPassword);
                        if ($idNuevoUw) {
                            $usuarioWebModel->vincularJugador($idNuevoUw, $idJugador);
                        }
                    }
                }
            }

            // 4. Marcar inscripción como aprobada
            $this->model->cambiarEstado((int)$id, InscripcionEquipo::ESTADO_APROBADA);

            $this->db->commit();

            // Enviar email de aprobación si se provee el cuerpo
            if ($emailBody !== '' && ($inscripcion['email_solicitante'] ?? null)) {
                $this->enviarEmail(
                    $inscripcion['email_solicitante'],
                    'Inscripción aprobada - Il Calcio Camp',
                    $emailBody
                );
            }

            $this->respond(200, [
                'message' => 'Inscripción aprobada. Equipo, jugadores y equipo_torneo creados.',
                'id_equipo' => $idEquipo,
            ]);
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->handleError($e, 'Error al aprobar inscripción');
        }
    }

    public function observar(): void
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $id = isset($data['id']) ? (int)$data['id'] : null;
            $observacion = isset($data['observacion']) ? trim((string)$data['observacion']) : '';
            $emailBody = isset($data['email_body']) ? trim((string)$data['email_body']) : '';

            if (!$id) {
                $this->respond(400, ['message' => 'ID requerido.']);
            }
            if ($observacion === '') {
                $this->respond(400, ['message' => 'La observación es requerida.']);
            }

            $inscripcion = $this->model->getById((int)$id);
            if (!$inscripcion) {
                $this->respond(404, ['message' => 'Inscripción no encontrada.']);
            }
            if (in_array((int)$inscripcion['id_estado'], [InscripcionEquipo::ESTADO_APROBADA, InscripcionEquipo::ESTADO_RECHAZADA])) {
                $this->respond(409, ['message' => 'No se puede observar una inscripción ya ' . strtolower($inscripcion['estado']) . '.']);
            }

            // Guardar el cuerpo completo del email en observacion_admin
            $textoAGuardar = $emailBody !== '' ? $emailBody : $observacion;
            $this->model->cambiarEstado((int)$id, InscripcionEquipo::ESTADO_OBSERVADA, $textoAGuardar);

            if ($emailBody !== '' && ($inscripcion['email_solicitante'] ?? null)) {
                $this->enviarEmail(
                    $inscripcion['email_solicitante'],
                    'Inscripción en revisión - Il Calcio Camp',
                    $emailBody
                );
            }

            $this->respond(200, ['message' => 'Inscripción marcada como observada.']);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al observar inscripción');
        }
    }

    public function rechazar(): void
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $id = isset($data['id']) ? (int)$data['id'] : null;
            $observacion = isset($data['observacion']) ? trim((string)$data['observacion']) : '';
            $emailBody = isset($data['email_body']) ? trim((string)$data['email_body']) : '';

            if (!$id) {
                $this->respond(400, ['message' => 'ID requerido.']);
            }
            if ($observacion === '') {
                $this->respond(400, ['message' => 'El motivo de rechazo es requerido.']);
            }

            $inscripcion = $this->model->getById((int)$id);
            if (!$inscripcion) {
                $this->respond(404, ['message' => 'Inscripción no encontrada.']);
            }
            if ((int)$inscripcion['id_estado'] === InscripcionEquipo::ESTADO_APROBADA) {
                $this->respond(409, ['message' => 'No se puede rechazar una inscripción ya aprobada.']);
            }

            // Guardar el cuerpo completo del email en observacion_admin
            $textoAGuardar = $emailBody !== '' ? $emailBody : $observacion;
            $this->model->cambiarEstado((int)$id, InscripcionEquipo::ESTADO_RECHAZADA, $textoAGuardar);

            if ($emailBody !== '' && ($inscripcion['email_solicitante'] ?? null)) {
                $this->enviarEmail(
                    $inscripcion['email_solicitante'],
                    'Estado de inscripción - Il Calcio Camp',
                    $emailBody
                );
            }

            $this->respond(200, ['message' => 'Inscripción rechazada.']);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al rechazar inscripción');
        }
    }

    private function enviarEmail(string $destinatario, string $asunto, string $cuerpo): void
    {
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = $_ENV['SMTP_HOST'] ?? 'mail.impactosdg.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $_ENV['SMTP_USER'] ?? 'ilcalciocamp@impactosdg.com';
            $mail->Password   = $_ENV['SMTP_PASS'] ?? '';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = (int)($_ENV['SMTP_PORT'] ?? 465);
            $mail->CharSet    = 'UTF-8';
            $mail->setFrom($mail->Username, 'Il Calcio Camp');
            $mail->addAddress($destinatario);
            $mail->isHTML(false);
            $mail->Subject = $asunto;
            $mail->Body    = $cuerpo;
            $mail->send();
        } catch (Throwable $e) {
            // No bloquear la respuesta HTTP si falla el envío
        }
    }
}
