<?php
include_once __DIR__ . '/../core/Database.php';
include_once __DIR__ . '/../models/Usuario.php';

class PermisoController {

    public function toggle(): void {
        $data = json_decode(file_get_contents("php://input"));

        if (!isset($data->id_usuario) || !isset($data->id_modulo) || !isset($data->estado)) {
            http_response_code(400);
            echo json_encode(['message' => 'Datos incompletos.']);
            return;
        }

        $database = new Database();
        $db = $database->connect();
        $usuarioModel = new Usuario($db);

        $success = $usuarioModel->togglePermiso(
            (int)$data->id_usuario, 
            (int)$data->id_modulo, 
            (bool)$data->estado
        );

        if ($success) {
            http_response_code(200);
            echo json_encode(['message' => 'Permiso actualizado correctamente.']);
        } else {
            http_response_code(500);
            echo json_encode(['message' => 'Error al actualizar el permiso.']);
        }
    }
}