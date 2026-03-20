<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/BaseController.php';
require_once __DIR__ . '/../models/Equipo.php';
require_once __DIR__ . '/../models/Jugador.php';

class EquipoController extends BaseController
{
    private Equipo $model;

    public function __construct(PDO $db)
    {
        parent::__construct($db);
        $this->model = new Equipo($this->db);
    }

    /**
     * Da de baja un equipo y desasigna jugadores activos
     */
    public function bajaLogica(): void
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);
            $id = $data['id'] ?? null;

            if (!$id) {
                $this->respond(400, ['message' => 'ID requerido.']);
            }

            $equipo = $this->model->getById((int)$id);
            if (!$equipo) {
                $this->respond(404, ['message' => 'Equipo no encontrado.']);
            }

            $stmt = $this->db->prepare(
                "SELECT t.id, t.nombre, et.descripcion
                 FROM equipo_torneo etq
                 INNER JOIN torneo t ON t.id = etq.id_torneo
                 LEFT JOIN estado_torneo et ON et.id = t.id_estado_torneo
                 WHERE etq.id_equipo = :id_equipo
                   AND (t.id_estado_torneo IS NULL OR et.activo = 1)
                 LIMIT 1"
            );
            $stmt->bindValue(':id_equipo', (int)$id, PDO::PARAM_INT);
            $stmt->execute();
            $torneoActivo = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($torneoActivo) {
                $nombreTorneo = trim((string)($torneoActivo['nombre'] ?? ''));
                $estadoTorneo = trim((string)($torneoActivo['descripcion'] ?? ''));
                $detalle = $nombreTorneo !== ''
                    ? " (" . $nombreTorneo . (
                        $estadoTorneo !== '' ? " - " . $estadoTorneo : ""
                    ) . ")"
                    : '';
                $this->respond(409, [
                    'message' => 'No se puede dar de baja: el equipo está inscripto en un torneo activo' . $detalle . '.',
                ]);
            }

            $actualizado = $this->model->setActivo((int)$equipo['id'], false);

            if (!$actualizado) {
                $this->respond(500, ['message' => 'No se pudo dar de baja el equipo.']);
            }

            $jugadorModel = new Jugador($this->db);
            $jugadores = $jugadorModel->getByEquipo((int)$id);
            $desasignados = 0;

            foreach ($jugadores as $jugador) {
                $ok = $jugadorModel->update(
                    (int)$jugador['id'],
                    $jugador['nombre'] ?? '',
                    $jugador['apellido'] ?? '',
                    $jugador['dni'] ?? null,
                    $jugador['fecha_nac'] ?? null,
                    $jugador['fecha_alta'] ?? null,
                    $jugador['activo'] !== null ? (bool)(int)$jugador['activo'] : true,
                    null,
                    false
                );
                if ($ok) {
                    $desasignados++;
                }
            }

            $this->respond(200, [
                'message' => 'Equipo dado de baja y jugadores desasignados.',
                'jugadores_desasignados' => $desasignados,
            ]);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al dar de baja el equipo');
        }
    }

    /**
     * Obtiene todos los equipos o solo los activos, o filtra por disciplina/pendientes
     */
    public function getAll(): void
    {
        try {
            $activos = $_GET['activos'] ?? null;
            $disciplina = $_GET['disciplina'] ?? null;
            $pendientes = $_GET['pendientes'] ?? null;
            $idDisciplina = isset($_GET['id_disciplina']) ? (int)$_GET['id_disciplina'] : 0;

            if ($pendientes === '1' || $pendientes === 'true') {
                $result = $this->model->getPendientesConfirmacion();
            } elseif ($idDisciplina > 0) {
                $result = $this->model->getByDisciplinaId($idDisciplina);
            } elseif ($disciplina) {
                $result = $this->model->getByDisciplina($disciplina);
            } elseif ($activos === '1' || $activos === 'true') {
                $result = $this->model->getActivos();
            } else {
                $result = $this->model->getAll();
            }

            $this->respond(200, $result);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al obtener equipos');
        }
    }

    /**
     * Obtiene un equipo por ID
     */
    public function getById(): void
    {
        try {
            $id = $_GET['id'] ?? null;
            if (!$id) {
                $this->respond(400, ['message' => 'ID requerido.']);
            }

            $result = $this->model->getById((int)$id);
            $result ? $this->respond(200, $result) : $this->respond(404, ['message' => 'Equipo no encontrado.']);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al obtener equipo');
        }
    }

    /**
     * Crea un nuevo equipo
     */
    public function store(): void
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);

            if (empty($data['nombre']) || empty($data['id_disciplina']) || (int)$data['id_disciplina'] <= 0) {
                $this->respond(400, ['message' => 'Nombre e id_disciplina requeridos.']);
            }

            $activo = $data['activo'] ?? true;
            $escudo = isset($data['escudo']) ? trim((string)$data['escudo']) : null;
            if ($escudo === '') {
                $escudo = null;
            }
            $confirmar = isset($data['confirmar']) ? (int)$data['confirmar'] : 1;

            $id = $this->model->create(
                (string)$data['nombre'],
                (int)$data['id_disciplina'],
                (bool)$activo,
                $escudo,
                $confirmar
            );

            if ($id) {
                $this->respond(201, ['message' => 'Equipo creado exitosamente.', 'id' => $id]);
            } else {
                $this->respond(500, ['message' => 'Error al crear equipo.']);
            }
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al crear equipo');
        }
    }

    /**
     * Actualiza un equipo existente
     */
    public function update(): void
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);

            if (empty($data['id']) || empty($data['nombre']) || empty($data['id_disciplina']) || (int)$data['id_disciplina'] <= 0 || !isset($data['activo'])) {
                $this->respond(400, ['message' => 'ID, nombre, id_disciplina y estado activo requeridos.']);
            }

            $escudo = isset($data['escudo']) ? trim((string)$data['escudo']) : null;
            if ($escudo === '') {
                $escudo = null;
            }

            if ($this->model->update((int)$data['id'], (string)$data['nombre'], (int)$data['id_disciplina'], (bool)$data['activo'], $escudo)) {
                $this->respond(200, ['message' => 'Equipo actualizado exitosamente.']);
            } else {
                $this->respond(500, ['message' => 'Error al actualizar equipo.']);
            }
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al actualizar equipo');
        }
    }

    public function subirEscudo(): void
    {
        try {
            if (!isset($_FILES['escudo'])) {
                $this->respond(400, ['message' => 'Debes adjuntar un archivo en el campo escudo.']);
            }

            $file = $_FILES['escudo'];
            if (!is_array($file) || !isset($file['error'])) {
                $this->respond(400, ['message' => 'Archivo de escudo inválido.']);
            }

            if ((int)$file['error'] !== UPLOAD_ERR_OK) {
                $this->respond(400, ['message' => 'Error al subir el escudo. Código: ' . (string)$file['error']]);
            }

            $maxBytes = 5 * 1024 * 1024;
            $size = (int)($file['size'] ?? 0);
            if ($size <= 0 || $size > $maxBytes) {
                $this->respond(422, ['message' => 'El escudo debe pesar entre 1 byte y 5 MB.']);
            }

            $tmpPath = (string)($file['tmp_name'] ?? '');
            if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
                $this->respond(400, ['message' => 'No se encontró el archivo temporal del escudo.']);
            }

            $allowedMimeToExt = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
                'image/svg+xml' => 'svg',
            ];

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = $finfo ? (string)finfo_file($finfo, $tmpPath) : '';
            if ($finfo) {
                finfo_close($finfo);
            }

            if (!isset($allowedMimeToExt[$mime])) {
                $this->respond(422, ['message' => 'Formato no permitido. Usa JPG, PNG, WEBP o SVG.']);
            }

            $uploadsDir = $this->resolveEscudosDirectory();
            $originalName = (string)($file['name'] ?? 'escudo');
            $safeBase = $this->sanitizeFileBaseName(pathinfo($originalName, PATHINFO_FILENAME));
            $ext = $allowedMimeToExt[$mime];
            $fileName = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '_' . $safeBase . '.' . $ext;
            $fullPath = rtrim($uploadsDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $fileName;

            if (!move_uploaded_file($tmpPath, $fullPath)) {
                $this->respond(500, ['message' => 'No se pudo guardar el escudo en el servidor.']);
            }

            $webPath = $this->buildEscudosPublicPath($fileName);
            $this->respond(201, [
                'message' => 'Escudo subido correctamente.',
                'escudo' => $webPath,
                'file_name' => $fileName,
                'mime_type' => $mime,
                'size_bytes' => $size,
            ]);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al subir escudo');
        }
    }

    private function resolveEscudosDirectory(): string
    {
        $configured = trim((string)($_ENV['UPLOADS_ESCUDOS_DIR'] ?? getenv('UPLOADS_ESCUDOS_DIR') ?? ''));
        $candidates = [];
        if ($configured !== '') {
            $candidates[] = $configured;
        }
        $candidates[] = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'escudos';

        foreach ($candidates as $candidate) {
            $dir = rtrim($candidate, "\\/");
            if ($dir === '') {
                continue;
            }
            if (is_dir($dir) || @mkdir($dir, 0775, true)) {
                return $dir;
            }
        }

        $this->respond(500, ['message' => 'No se pudo resolver el directorio de uploads/escudos en el proyecto. Configura UPLOADS_ESCUDOS_DIR.']);
    }

    private function sanitizeFileBaseName(string $baseName): string
    {
        $sanitized = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $baseName);
        $sanitized = trim((string)$sanitized, '_');
        if ($sanitized === '') {
            $sanitized = 'escudo';
        }
        return substr($sanitized, 0, 60);
    }

    private function buildEscudosPublicPath(string $fileName): string
    {
        $scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
        $apiDir = rtrim((string)dirname($scriptName), '/');
        $baseDir = preg_replace('#/api$#', '', $apiDir) ?? '';
        $baseDir = rtrim($baseDir, '/');

        if ($baseDir === '' || $baseDir === '.') {
            return '/uploads/escudos/' . $fileName;
        }

        return $baseDir . '/uploads/escudos/' . $fileName;
    }

    /**
     * Confirma un equipo pendiente de aprobacion
     */
    public function confirmarEquipo(): void
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $id = $data['id'] ?? null;

            if (!$id) {
                $this->respond(400, ['message' => 'ID requerido.']);
            }

            $equipo = $this->model->getById((int)$id);
            if (!$equipo) {
                $this->respond(404, ['message' => 'Equipo no encontrado.']);
            }

            if ((int)$equipo['confirmar'] === 1) {
                $this->respond(200, ['message' => 'El equipo ya estaba confirmado.']);
            }

            if ($this->model->confirmar((int)$id)) {
                $this->respond(200, ['message' => 'Equipo confirmado exitosamente.']);
            } else {
                $this->respond(500, ['message' => 'Error al confirmar el equipo.']);
            }
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al confirmar equipo');
        }
    }

    /**
     * Elimina un equipo
     */
    public function delete(): void
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);
            $id = $data['id'] ?? null;

            if (!$id) {
                $this->respond(400, ['message' => 'ID requerido.']);
            }

            if ($this->model->delete((int)$id)) {
                $this->respond(200, ['message' => 'Equipo eliminado exitosamente.']);
            } else {
                $this->respond(500, ['message' => 'Error al eliminar equipo.']);
            }
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al eliminar equipo');
        }
    }
}
