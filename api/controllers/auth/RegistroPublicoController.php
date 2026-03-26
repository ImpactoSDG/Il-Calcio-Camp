<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseController.php';
require_once __DIR__ . '/../../models/torneos/Equipo.php';
require_once __DIR__ . '/../../models/torneos/Jugador.php';

class RegistroPublicoController extends BaseController
{
    private Equipo $equipoModel;
    private Jugador $jugadorModel;

    public function __construct(PDO $db)
    {
        parent::__construct($db);
        $this->equipoModel = new Equipo($this->db);
        $this->jugadorModel = new Jugador($this->db);
    }

    /**
     * Registra un equipo desde el portal público.
     * El equipo se crea con confirmar = 0 (pendiente de aprobación).
     * Cada jugador se valida por DNI: si ya existe se reutiliza, si no se crea.
     */
    public function registrar(): void
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);

            $nombre    = isset($data['nombre'])    ? trim((string)$data['nombre'])    : '';
            $disciplina = isset($data['disciplina']) ? trim((string)$data['disciplina']) : '';
            $jugadores  = $data['jugadores'] ?? [];

            if ($nombre === '' || $disciplina === '') {
                $this->respond(400, ['message' => 'El nombre del equipo y la disciplina son obligatorios.']);
            }

            if (!is_array($jugadores) || count($jugadores) === 0) {
                $this->respond(400, ['message' => 'Debe registrar al menos un jugador.']);
            }

            // Validar jugadores antes de persistir
            foreach ($jugadores as $index => $jugador) {
                $num = $index + 1;
                $fnombre   = isset($jugador['nombre'])   ? trim((string)$jugador['nombre'])   : '';
                $fapellido = isset($jugador['apellido']) ? trim((string)$jugador['apellido']) : '';
                $fdni      = isset($jugador['dni'])      ? trim((string)$jugador['dni'])      : '';

                if ($fnombre === '' || $fapellido === '') {
                    $this->respond(400, ['message' => "El jugador #{$num} debe tener nombre y apellido."]);
                }

                if ($fdni === '') {
                    $this->respond(400, ['message' => "El DNI del jugador #{$num} ({$fnombre} {$fapellido}) es obligatorio."]);
                }

                if (!preg_match('/^\d{7,9}$/', $fdni)) {
                    $this->respond(400, ['message' => "El DNI del jugador #{$num} ({$fnombre} {$fapellido}) debe contener entre 7 y 9 dígitos numéricos."]);
                }
            }

            // Verificar capitanes (exactamente uno)
            $cantCapitanes = count(array_filter($jugadores, fn($j) => !empty($j['capitan'])));
            if ($cantCapitanes > 1) {
                $this->respond(400, ['message' => 'Solo puede haber un capitán por equipo.']);
            }

            // Crear el equipo con confirmar = 0
            $idEquipo = $this->equipoModel->create($nombre, $disciplina, true, null, 0);
            if (!$idEquipo) {
                $this->respond(500, ['message' => 'No se pudo registrar el equipo. Intente nuevamente.']);
            }

            $jugadoresRegistrados = [];

            foreach ($jugadores as $jugador) {
                $fnombre   = trim((string)($jugador['nombre']   ?? ''));
                $fapellido = trim((string)($jugador['apellido'] ?? ''));
                $fdni      = trim((string)($jugador['dni']      ?? ''));
                $capitan   = !empty($jugador['capitan']);
                $arquero   = !empty($jugador['arquero']);

                // Verificar si ya existe un jugador con ese DNI
                $existente = $this->jugadorModel->getByDni($fdni);

                if ($existente) {
                    // Reutilizar jugador existente: asignarlo al nuevo equipo
                    $this->jugadorModel->update(
                        (int)$existente['id'],
                        $existente['nombre'],
                        $existente['apellido'],
                        $existente['dni'],
                        $existente['fecha_nac'] ?? null,
                        $existente['fecha_alta'] ?? null,
                        (bool)(int)($existente['activo'] ?? 1),
                        $idEquipo,
                        $capitan,
                        $arquero
                    );
                    $jugadoresRegistrados[] = [
                        'id'       => (int)$existente['id'],
                        'accion'   => 'reutilizado',
                        'nombre'   => $existente['nombre'],
                        'apellido' => $existente['apellido'],
                    ];
                } else {
                    // Crear nuevo jugador
                    $nuevoId = $this->jugadorModel->create(
                        $fnombre,
                        $fapellido,
                        $fdni,
                        null,
                        date('Y-m-d'),
                        true,
                        $idEquipo,
                        $capitan,
                        $arquero
                    );
                    $jugadoresRegistrados[] = [
                        'id'       => $nuevoId,
                        'accion'   => 'creado',
                        'nombre'   => $fnombre,
                        'apellido' => $fapellido,
                    ];
                }
            }

            $this->respond(201, [
                'message'   => 'Equipo registrado correctamente. Está pendiente de aprobación por los administradores.',
                'id_equipo' => $idEquipo,
                'jugadores' => $jugadoresRegistrados,
            ]);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error en el registro público del equipo');
        }
    }
}
