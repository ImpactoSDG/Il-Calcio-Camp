<?php
include_once __DIR__ . '/../core/Database.php';
include_once __DIR__ . '/../models/Usuario.php';

class UsuarioController
{
    private PDO $db;
    private Usuario $usuarioModel;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
        $this->usuarioModel = new Usuario($this->db);
    }

    public function getAll(): void
    {
        $result = $this->usuarioModel->getAll();
        if ($result) {
            http_response_code(200);
            echo json_encode($result);
        } else {
            http_response_code(404);
            echo json_encode(['message' => 'No se encontraron usuarios.']);
        }
    }

    public function getById(): void
    {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['message' => 'ID requerido.']);
            return;
        }

        $result = $this->usuarioModel->getById((int)$id);
        if ($result) {
            echo json_encode($result);
        } else {
            http_response_code(404);
            echo json_encode(['message' => 'Usuario no encontrado.']);
        }
    }

    public function store(): void
    {
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['nombre']) || empty($data['email']) || empty($data['contrasena']) || empty($data['id_rol'])) {
            http_response_code(400);
            echo json_encode(['message' => 'Datos incompletos.']);
            return;
        }

        if ($this->usuarioModel->emailExists($data['email'])) {
            http_response_code(400);
            echo json_encode(['message' => 'El email ya está registrado.']);
            return;
        }

        $hash = password_hash($data['contrasena'], PASSWORD_BCRYPT);
        $id = $this->usuarioModel->register($data['nombre'], $data['email'], $hash, (int)$data['id_rol']);

        if ($id) {
            http_response_code(201);
            echo json_encode(['message' => 'Usuario creado.', 'id' => $id]);
        } else {
            http_response_code(500);
            echo json_encode(['message' => 'Error al crear usuario.']);
        }
    }

    public function update(): void
    {
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['id']) || empty($data['nombre']) || empty($data['email']) || empty($data['id_rol'])) {
            http_response_code(400);
            echo json_encode(['message' => 'Datos insuficientes para actualizar.']);
            return;
        }

        if ($this->usuarioModel->emailExists($data['email'], (int)$data['id'])) {
            http_response_code(400);
            echo json_encode(['message' => 'El email ya está en uso por otro usuario.']);
            return;
        }

        if ($this->usuarioModel->update((int)$data['id'], $data['nombre'], $data['email'], (int)$data['id_rol'])) {
            echo json_encode(['message' => 'Usuario actualizado.']);
        } else {
            http_response_code(500);
            echo json_encode(['message' => 'Error al actualizar usuario.']);
        }
    }

    public function updatePassword(): void
    {
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['id']) || empty($data['contrasena'])) {
            http_response_code(400);
            echo json_encode(['message' => 'ID y nueva contraseña requeridos.']);
            return;
        }

        $hash = password_hash($data['contrasena'], PASSWORD_BCRYPT);
        if ($this->usuarioModel->updatePassword((int)$data['id'], $hash)) {
            echo json_encode(['message' => 'Contraseña actualizada.']);
        } else {
            http_response_code(500);
            echo json_encode(['message' => 'Error al actualizar contraseña.']);
        }
    }

    public function delete(): void
    {
        $data = json_decode(file_get_contents("php://input"), true);
        $id = $data['id'] ?? null;

        if (!$id) {
            http_response_code(400);
            echo json_encode(['message' => 'ID requerido.']);
            return;
        }

        if ($this->usuarioModel->delete((int)$id)) {
            echo json_encode(['message' => 'Usuario eliminado.']);
        } else {
            http_response_code(500);
            echo json_encode(['message' => 'Error al eliminar usuario.']);
        }
    }

    public function getGestionPermisos(): void
    {
        $usuarios = $this->usuarioModel->getAll();
        $modulos = $this->usuarioModel->getListaModulosCompleta();
        $permisos = $this->usuarioModel->getMapaPermisos();

        if ($usuarios && $modulos) {
            echo json_encode([
                'usuarios' => $usuarios,
                'modulos' => $modulos,
                'permisos' => $permisos
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['message' => 'Error al cargar matriz de permisos.']);
        }
    }

    public function togglePermiso(): void
    {
        $data = json_decode(file_get_contents("php://input"), true);

        if (!isset($data['id_usuario'], $data['id_modulo'], $data['estado'])) {
            http_response_code(400);
            echo json_encode(['message' => 'Datos de permiso incompletos.']);
            return;
        }

        if ($this->usuarioModel->togglePermiso((int)$data['id_usuario'], (int)$data['id_modulo'], (bool)$data['estado'])) {
            echo json_encode(['message' => 'Permiso actualizado.']);
        } else {
            http_response_code(500);
            echo json_encode(['message' => 'Error al procesar permiso.']);
        }
    }
}