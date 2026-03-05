<?php

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../core/JwtHandler.php';
require_once __DIR__ . '/../core/BaseController.php';

class AuthController extends BaseController
{
    private Usuario $usuarioModel;

    public function __construct(PDO $db)
    {
        parent::__construct($db);
        $this->usuarioModel = new Usuario($this->db);
    }

    public function login(): void
    {
        header('Content-Type: application/json; charset=UTF-8');

        $data = json_decode(file_get_contents("php://input"));

        if (empty($data->email) || empty($data->contrasena)) {
            $this->respond(400, ['message' => 'Faltan datos: email y contraseña son requeridos.']);
        }

        $email = $data->email;
        $contrasena = $data->contrasena;

        try {
            $usuario = $this->usuarioModel->getByEmail($email);

            if (!$usuario || !password_verify($contrasena, $usuario['contrasena'])) {
                $this->respond(401, ['message' => 'Credenciales inválidas. Verifique su email y contraseña.']);
            }

            $usuarioCompleto = $this->usuarioModel->getByNameOrEmail($email);

            $modulos = $this->usuarioModel->getModulos($usuarioCompleto['id']);
            $usuarioCompleto['modulos'] = $modulos;
            
            unset($usuarioCompleto['contrasena']);

            $token = JwtHandler::encode([
                'id' => $usuarioCompleto['id'],
                'email' => $usuarioCompleto['email']
            ]);

            $this->respond(200, [
                'message' => 'Inicio de sesión exitoso.',
                'usuario' => $usuarioCompleto,
                'token' => $token
            ]);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al procesar el inicio de sesión.');
        }
    }

    public function register(): void
    {
        header('Content-Type: application/json; charset=UTF-8');

        $data = json_decode(file_get_contents("php://input"));

        if (empty($data->nombre) || empty($data->email) || empty($data->contrasena) || empty($data->id_rol)) {
            $this->respond(400, ['message' => 'Faltan datos: nombre, email, contraseña e id_rol son requeridos.']);
        }

        $nombre = $data->nombre;
        $email = $data->email;
        $contrasena = $data->contrasena;
        $idRol = (int)$data->id_rol;

        if ($this->usuarioModel->getByEmail($email)) {
            $this->respond(409, ['message' => 'El email ya está en uso.']);
        }
        
        $contrasena_hash = password_hash($contrasena, PASSWORD_DEFAULT);
        $newId = $this->usuarioModel->register($nombre, $email, $contrasena_hash, $idRol);

        if ($newId) {
            $this->respond(201, [
                'message' => 'Registro de usuario exitoso.',
                'id' => $newId,
                'nombre' => $nombre,
                'email' => $email
            ]);
        } else {
            $this->respond(500, ['message' => 'Error al intentar registrar el usuario.']);
        }
    }

    public function getRoles(): void
    {
        $roles = $this->usuarioModel->getRoles();
        $this->respond(200, $roles);
    }
}