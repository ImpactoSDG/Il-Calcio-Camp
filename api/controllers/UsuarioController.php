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
            echo json_encode(['message' => 'No se encontraron usuarios.']);
        }
    }

    /**
     * Devuelve toda la información para armar la matriz de la imagen
     */
    public function getGestionPermisos(): void {
        $database = new Database();
        $db = $database->connect();
        $usuarioModel = new Usuario($db);

        // 1. Obtener todos los usuarios (columnas)
        $usuarios = $usuarioModel->getAll();
        
        // 2. Obtener todos los módulos (filas)
        $modulos = $usuarioModel->getListaModulosCompleta();

        // 3. Obtener el mapa de permisos actuales (checks)
        $permisos = $usuarioModel->getMapaPermisos();

        if ($usuarios && $modulos) {
            http_response_code(200);
            echo json_encode([
                'usuarios' => $usuarios,
                'modulos' => $modulos,
                'permisos' => $permisos
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['message' => 'Error al cargar los datos de permisos.']);
        }
    }
}