<?php

declare(strict_types=1);

include_once __DIR__ . '/../core/Database.php';
include_once __DIR__ . '/../models/Usuario.php';
include_once __DIR__ . '/../models/Modulo.php';

class UsuarioController
{
    private PDO $db;
    private Usuario $usuarioModel;
    private Modulo $moduloModel;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
        $this->usuarioModel = new Usuario($this->db);
        $this->moduloModel = new Modulo($this->db);
    }

    private function respond(int $code, array $data): void
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    public function getAll(): void
    {
        $result = $this->usuarioModel->getAll();
        $result ? $this->respond(200, $result) : $this->respond(404, ['message' => 'No se encontraron usuarios.']);
    }

    public function getById(): void
    {
        $id = $_GET['id'] ?? null;
        if (!$id) $this->respond(400, ['message' => 'ID requerido.']);

        $result = $this->usuarioModel->getById((int)$id);
        $result ? $this->respond(200, $result) : $this->respond(404, ['message' => 'Usuario no encontrado.']);
    }

    public function store(): void
    {
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['nombre']) || empty($data['email']) || empty($data['contrasena']) || empty($data['id_rol'])) {
            $this->respond(400, ['message' => 'Datos incompletos.']);
        }

        if ($this->usuarioModel->emailExists($data['email'])) {
            $this->respond(400, ['message' => 'El email ya está registrado.']);
        }

        $hash = password_hash($data['contrasena'], PASSWORD_BCRYPT);
        $id = $this->usuarioModel->register($data['nombre'], $data['email'], $hash, (int)$data['id_rol']);

        if ($id) {
            $this->respond(201, ['message' => 'Usuario creado.', 'id' => $id]);
        } else {
            $this->respond(500, ['message' => 'Error al crear usuario.']);
        }
    }

    public function update(): void
    {
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['id']) || empty($data['nombre']) || empty($data['email']) || empty($data['id_rol'])) {
            $this->respond(400, ['message' => 'Datos insuficientes.']);
        }

        if ($this->usuarioModel->emailExists($data['email'], (int)$data['id'])) {
            $this->respond(400, ['message' => 'El email ya está en uso por otro usuario.']);
        }

        if ($this->usuarioModel->update((int)$data['id'], $data['nombre'], $data['email'], (int)$data['id_rol'])) {
            $this->respond(200, ['message' => 'Usuario actualizado.']);
        } else {
            $this->respond(500, ['message' => 'Error al actualizar usuario.']);
        }
    }

    public function updatePassword(): void
    {
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['id']) || empty($data['contrasena'])) {
            $this->respond(400, ['message' => 'ID y nueva contraseña requeridos.']);
        }

        $hash = password_hash($data['contrasena'], PASSWORD_BCRYPT);
        if ($this->usuarioModel->updatePassword((int)$data['id'], $hash)) {
            $this->respond(200, ['message' => 'Contraseña actualizada.']);
        } else {
            $this->respond(500, ['message' => 'Error al actualizar contraseña.']);
        }
    }

    public function delete(): void
    {
        $data = json_decode(file_get_contents("php://input"), true);
        $id = $data['id'] ?? null;

        if (!$id) $this->respond(400, ['message' => 'ID requerido.']);

        if ($this->usuarioModel->delete((int)$id)) {
            $this->respond(200, ['message' => 'Usuario eliminado.']);
        } else {
            $this->respond(500, ['message' => 'Error al eliminar usuario.']);
        }
    }

    public function getGestionPermisos(): void
    {
        $usuarios = $this->usuarioModel->getAll();
        $modulos = $this->moduloModel->getListaModulosCompleta();
        $permisos = $this->usuarioModel->getMapaPermisos();

        if ($usuarios && $modulos) {
            $this->respond(200, [
                'usuarios' => $usuarios,
                'modulos' => $modulos,
                'permisos' => $permisos
            ]);
        } else {
            $this->respond(404, ['message' => 'Error al cargar matriz de permisos.']);
        }
    }

    public function togglePermiso(): void
    {
        $data = json_decode(file_get_contents("php://input"), true);

        if (!isset($data['id_usuario'], $data['id_modulo'], $data['estado'])) {
            $this->respond(400, ['message' => 'Datos de permiso incompletos.']);
        }

        if ($this->usuarioModel->togglePermiso((int)$data['id_usuario'], (int)$data['id_modulo'], (bool)$data['estado'])) {
            $this->respond(200, ['message' => 'Permiso actualizado.']);
        } else {
            $this->respond(500, ['message' => 'Error al procesar permiso.']);
        }
    }

    public function refreshModulos(): void
    {
        $id = $_GET['id'] ?? null;
        if (!$id) $this->respond(400, ['message' => 'ID requerido.']);

        $modulos = $this->usuarioModel->getModulos((int)$id);
        $this->respond(200, ['modulos' => $modulos]);
    }
}