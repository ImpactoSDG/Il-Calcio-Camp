<?php

declare(strict_types=1);

require_once __DIR__ . '/../../models/hardware/QzCertificado.php';

class QzCertificadoController
{
    private PDO $db;
    private QzCertificado $model;

    /** Directorio donde se almacenan los archivos de certificados */
    private const CERTS_SUBDIR = 'qz_certs';

    /** Tamaño máximo permitido por archivo: 64 KB */
    private const MAX_FILE_SIZE = 65536;

    /** Tipos MIME aceptados para los archivos de certificado */
    private const ALLOWED_MIME = ['text/plain', 'application/x-pem-file', 'application/octet-stream'];

    public function __construct(PDO $db)
    {
        $this->db    = $db;
        $this->model = new QzCertificado($db);
    }

    private function respond(int $code, array $data): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($data);
        exit;
    }

    // ──────────────────────────────────────────────────────────────
    // GET /qz-certificados                → lista de registros
    // GET /qz-certificados?machine_id=xx  → contenido cert/pk
    // ──────────────────────────────────────────────────────────────
    public function get(): void
    {
        $rawMachineId = $_GET['machine_id'] ?? null;

        if ($rawMachineId !== null) {
            $machineId = $this->sanitizeMachineId((string)$rawMachineId);
            if ($machineId === null) {
                $this->respond(400, ['message' => 'machine_id inválido.']);
            }
            $this->getContent($machineId);
        } else {
            $this->getAll();
        }
    }

    private function getAll(): void
    {
        $records = $this->model->getAll();
        // No exponer rutas de archivo ni contenido sensible en el listado
        $result = array_map(fn($r) => [
            'id'                 => $r['id'],
            'machine_id'         => $r['machine_id'],
            'nombre_maquina'     => $r['nombre_maquina'],
            'fecha_modificacion' => $r['fecha_modificacion'],
        ], $records);
        $this->respond(200, $result);
    }

    private function getContent(string $machineId): void
    {
        $record = $this->model->getByMachineId($machineId);
        if (!$record) {
            $this->respond(404, ['message' => 'No hay certificados registrados para esta máquina.']);
        }

        $certsDir = $this->resolveCertsDirectory();
        $certPath = $certsDir . DIRECTORY_SEPARATOR . $record['cert_filename'];
        $pkPath   = $certsDir . DIRECTORY_SEPARATOR . $record['pk_filename'];

        if (!is_file($certPath) || !is_file($pkPath)) {
            $this->respond(404, ['message' => 'Archivos de certificado no encontrados en el servidor.']);
        }

        $this->respond(200, [
            'machine_id'     => $record['machine_id'],
            'nombre_maquina' => $record['nombre_maquina'],
            'cert'           => file_get_contents($certPath),
            'pk'             => file_get_contents($pkPath),
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    // POST /qz-certificados  (multipart/form-data)
    //   campos:  machine_id, nombre_maquina, cert_file, pk_file
    // ──────────────────────────────────────────────────────────────
    public function upload(): void
    {
        $rawMachineId   = $_POST['machine_id']    ?? '';
        $nombreMaquina  = trim($_POST['nombre_maquina'] ?? '');

        $machineId = $this->sanitizeMachineId($rawMachineId);
        if ($machineId === null) {
            $this->respond(400, ['message' => 'machine_id inválido. Debe ser un UUID válido.']);
        }

        if (empty($nombreMaquina)) {
            $this->respond(400, ['message' => 'El nombre de la máquina es obligatorio.']);
        }

        $certUpload = $_FILES['cert_file'] ?? null;
        $pkUpload   = $_FILES['pk_file']   ?? null;

        // Verificar si ya existe un registro previo
        $existing = $this->model->getByMachineId($machineId);
        $certsDir = $this->resolveCertsDirectory();

        // Procesar archivos solo si se enviaron
        $certFilename = $existing['cert_filename'] ?? null;
        $pkFilename   = $existing['pk_filename']   ?? null;

        if ($certUpload && (int)$certUpload['error'] !== UPLOAD_ERR_NO_FILE) {
            $certFilename = $this->saveUploadedFile($certUpload, $machineId . '_cert', $certsDir);
        }
        if ($pkUpload && (int)$pkUpload['error'] !== UPLOAD_ERR_NO_FILE) {
            $pkFilename = $this->saveUploadedFile($pkUpload, $machineId . '_pk', $certsDir);
        }

        if ($certFilename === null || $pkFilename === null) {
            $this->respond(400, ['message' => 'Se deben subir ambos archivos: certificado y clave privada.']);
        }

        $ok = $this->model->upsert($machineId, $nombreMaquina, $certFilename, $pkFilename);

        if ($ok) {
            $this->respond(200, ['message' => 'Certificados guardados correctamente.']);
        } else {
            $this->respond(500, ['message' => 'Error al guardar en la base de datos.']);
        }
    }

    // ──────────────────────────────────────────────────────────────
    // DELETE /qz-certificados   body: { "id": 5 }
    // ──────────────────────────────────────────────────────────────
    public function delete(): void
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $id   = (int)($data['id'] ?? 0);

        if (!$id) {
            $this->respond(400, ['message' => 'ID requerido.']);
        }

        $record = $this->model->deleteById($id);
        if ($record === null) {
            $this->respond(404, ['message' => 'Certificado no encontrado.']);
        }

        // Eliminar archivos del disco (sin lanzar error si ya no existen)
        $certsDir = $this->resolveCertsDirectory();
        @unlink($certsDir . DIRECTORY_SEPARATOR . $record['cert_filename']);
        @unlink($certsDir . DIRECTORY_SEPARATOR . $record['pk_filename']);

        $this->respond(200, ['message' => 'Certificados eliminados correctamente.']);
    }

    // ──────────────────────────────────────────────────────────────
    // Helpers privados
    // ──────────────────────────────────────────────────────────────

    /**
     * Valida y normaliza el machine_id.
     * Solo admite UUIDs (formato xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx).
     * Devuelve null si el formato no es válido.
     */
    private function sanitizeMachineId(string $raw): ?string
    {
        $clean = strtolower(trim($raw));
        // UUID v4 pattern
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $clean)) {
            return null;
        }
        return $clean;
    }

    /**
     * Guarda un archivo subido, validándolo primero.
     * Lanza respuesta 400/422 directamente si falla.
     * Retorna el nombre de archivo (sin ruta) guardado.
     */
    private function saveUploadedFile(array $file, string $prefix, string $dir): string
    {
        if ((int)$file['error'] !== UPLOAD_ERR_OK) {
            $this->respond(422, ['message' => "Error al subir el archivo: código {$file['error']}"]);
        }

        if ((int)$file['size'] > self::MAX_FILE_SIZE) {
            $this->respond(422, ['message' => 'El archivo supera el tamaño máximo permitido (64 KB).']);
        }

        $tmpPath = $file['tmp_name'] ?? '';
        if (empty($tmpPath) || !is_uploaded_file($tmpPath)) {
            $this->respond(422, ['message' => 'Archivo temporal inválido.']);
        }

        // Verificar que el contenido comience con "-----BEGIN "
        $header = file_get_contents($tmpPath, false, null, 0, 64);
        if ($header === false || strpos($header, '-----BEGIN ') === false) {
            $this->respond(422, ['message' => "El archivo no parece ser un certificado PEM válido ({$file['name']}).'"]);
        }

        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) ?: 'txt';
        $fileName = $prefix . '.' . $ext;
        $fullPath = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $fileName;

        if (!move_uploaded_file($tmpPath, $fullPath)) {
            $this->respond(500, ['message' => "No se pudo guardar el archivo {$file['name']} en el servidor."]);
        }

        return $fileName;
    }

    /**
     * Resuelve el directorio físico donde se guardan los certificados.
     * Crea la carpeta si no existe.
     */
    private function resolveCertsDirectory(): string
    {
        $base     = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'uploads';
        $certsDir = $base . DIRECTORY_SEPARATOR . self::CERTS_SUBDIR;

        if (!is_dir($certsDir) && !mkdir($certsDir, 0755, true)) {
            $this->respond(500, ['message' => 'No se pudo crear el directorio de certificados.']);
        }

        return $certsDir;
    }
}
