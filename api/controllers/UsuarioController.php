<?php 
include_once __DIR__ . '/../core/Database.php';
include_once __DIR__ . '/../models/Usuario.php';

class UsuarioController {
    
    public function getAll(): void {
        $database = new Database();
        $db = $database->connect();

        $usuario = new Usuario($db);
        $result = $usuario->getAll();
        
        if ($result) {
            http_response_code(200);
            echo json_encode($result);
        } else {
            http_response_code(404);
            echo json_encode(['message' => 'No se encontraron gestionadores.']);
        }
    }
}