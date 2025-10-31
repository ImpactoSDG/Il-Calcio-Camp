<?php

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../models/Usuario.php';

class AuthController
{
    private $db;
    private $usuarioModel;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
        $this->usuarioModel = new Usuario($this->db);
    }

    private function respond($status_code, $data)
    {
        http_response_code($status_code);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($data);
        exit;
    }

    public function login()
    {
        header('Content-Type: application/json; charset=UTF-8');

        $data = json_decode(file_get_contents("php://input"));

        if (empty($data->email) || empty($data->contrasena)) {
            $this->respond(400, ['message' => 'Faltan datos: email y contraseña son requeridos.']);
        }

        $email = $data->email;
        $contrasena = $data->contrasena;

        $usuario = $this->usuarioModel->getByEmail($email);

        if (!$usuario || !password_verify($contrasena, $usuario['contrasena'])) {
            $this->respond(401, ['message' => 'Credenciales inválidas. Verifique su email y contraseña.']);
        }

        $usuarioCompleto = $this->usuarioModel->getByNameOrEmail($email);

        $modulos = $this->usuarioModel->getModulos($usuarioCompleto['id']);
        $usuarioCompleto['modulos'] = $modulos;
        
        unset($usuarioCompleto['contrasena']);

        $this->respond(200, [
            'message' => 'Inicio de sesión exitoso.',
            'usuario' => $usuarioCompleto,
        ]);
    }

    public function register()
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
}