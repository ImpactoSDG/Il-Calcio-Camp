<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseController.php';
require_once __DIR__ . '/../../models/inventario/Articulo.php';

class ArticuloController extends BaseController
{
    private Articulo $model;

    public function __construct(PDO $db)
    {
        parent::__construct($db);
        $this->model = new Articulo($this->db);
    }

    /**
     * Obtiene todos los artículos, activos o por categoría
     */
    public function getAll(): void
    {
        try {
            $activos = $_GET['activos'] ?? null;
            $categoria = $_GET['categoria'] ?? null;
            
            if ($categoria) {
                $result = $this->model->getByCategoria((int)$categoria);
            } elseif ($activos === '1' || $activos === 'true') {
                $result = $this->model->getActivos();
            } else {
                $result = $this->model->getAll();
            }
            
            $this->respond(200, $result);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al obtener artículos');
        }
    }

    /**
     * Obtiene un artículo por ID o código de barras
     */
    public function getById(): void
    {
        try {
            $id = $_GET['id'] ?? null;
            $codBarra = $_GET['cod_barra'] ?? null;

            if ($codBarra) {
                $result = $this->model->getByCodBarra($codBarra);
            } elseif ($id) {
                $result = $this->model->getById((int)$id);
            } else {
                $this->respond(400, ['message' => 'ID o código de barras requerido.']);
            }

            $result ? $this->respond(200, $result) : $this->respond(404, ['message' => 'Artículo no encontrado.']);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al obtener artículo');
        }
    }

    /**
     * Sube una imagen para un artículo
     */
    public function uploadImage(): void
    {
        try {
            if (!isset($_FILES['imagen']) || !isset($_POST['id'])) {
                $this->respond(400, ['message' => 'Imagen e ID de artículo requeridos.']);
                return;
            }

            $id = (int)$_POST['id'];
            $file = $_FILES['imagen'];
            $nombreArticulo = $_POST['nombre'] ?? 'articulo';

            // Validar formato
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
            $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if (!in_array($fileExtension, $allowedExtensions)) {
                $this->respond(400, ['message' => 'Formato de imagen no permitido. Use jpg, jpeg, png o webp.']);
                return;
            }

            // Nombre de archivo limpio: id_nombre_timestamp.extension para evitar sobreescritura y cache
            $cleanName = preg_replace('/[^a-zA-Z0-9]/', '_', $nombreArticulo);
            $fileName = $id . '_' . $cleanName . '_' . time() . '.' . $fileExtension;
            $uploadPath = __DIR__ . '/../../uploads/' . $fileName;

            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                $urlImagen = 'uploads/' . $fileName;
                
                // Actualizar en base de datos
                if ($this->model->updateImagen($id, $urlImagen)) {
                    $this->respond(200, [
                        'message' => 'Imagen subida exitosamente.',
                        'url_imagen' => $urlImagen
                    ]);
                } else {
                    $this->respond(500, ['message' => 'Error al guardar la ruta en la base de datos.']);
                }
            } else {
                $this->respond(500, ['message' => 'Error al mover el archivo subido.']);
            }
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al subir imagen');
        }
    }

    /**
     * Crea un nuevo artículo
     */
    public function store(): void
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);

            if (empty($data['nombre'])) {
                $this->respond(400, ['message' => 'Nombre del artículo requerido.']);
                return;
            }

            // Convertir strings vacíos o formateados a tipos PHP correctos (float/int/bool/null)
            $precioActual = ($data['precio_actual'] !== null && $data['precio_actual'] !== '') ? (float)$data['precio_actual'] : null;
            $costoActual = ($data['costo_actual'] !== null && $data['costo_actual'] !== '') ? (float)$data['costo_actual'] : null;
            $codBarra = !empty($data['cod_barra']) ? (string)$data['cod_barra'] : null;
            $idCategoria = !empty($data['id_categoria_articulo']) ? (int)$data['id_categoria_articulo'] : null;
            $activo = isset($data['activo']) ? (bool)$data['activo'] : true;
            $urlImagen = !empty($data['url_imagen']) ? (string)$data['url_imagen'] : null;
            $ROP = isset($data['ROP']) && $data['ROP'] !== '' ? (int)$data['ROP'] : 1;

            $id = $this->model->create(
                $data['nombre'],
                $precioActual,
                $costoActual,
                $codBarra,
                $idCategoria,
                $activo,
                $urlImagen,
                $ROP
            );

            if ($id) {
                $this->respond(201, ['message' => 'Artículo creado exitosamente.', 'id' => (int)$id]);
            } else {
                $this->respond(500, ['message' => 'Error al crear artículo.']);
            }
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al crear artículo');
        }
    }

    /**
     * Actualiza un artículo existente
     */
    public function update(): void
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);

            if (empty($data['id']) || empty($data['nombre'])) {
                $this->respond(400, ['message' => 'ID y nombre requeridos.']);
                return;
            }

            // Convertir strings vacíos o "null" de frontend a nulos reales de PHP para los tipos nullable
            $precioActual = ($data['precio_actual'] !== null && $data['precio_actual'] !== '') ? (float)$data['precio_actual'] : null;
            $costoActual = ($data['costo_actual'] !== null && $data['costo_actual'] !== '') ? (float)$data['costo_actual'] : null;
            $codBarra = !empty($data['cod_barra']) ? (string)$data['cod_barra'] : null;
            $idCategoria = !empty($data['id_categoria_articulo']) ? (int)$data['id_categoria_articulo'] : null;
            $activo = isset($data['activo']) ? (bool)$data['activo'] : true;
            $urlImagen = !empty($data['url_imagen']) ? (string)$data['url_imagen'] : null;
            $ROP = isset($data['ROP']) && $data['ROP'] !== '' ? (int)$data['ROP'] : 1;

            if ($this->model->update(
                (int)$data['id'],
                $data['nombre'],
                $precioActual,
                $costoActual,
                $codBarra,
                $idCategoria,
                $activo,
                $urlImagen,
                $ROP
            )) {
                $this->respond(200, ['message' => 'Artículo actualizado exitosamente.']);
            } else {
                $this->respond(500, ['message' => 'Error al actualizar artículo.']);
            }
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al actualizar artículo');
        }
    }

    /**
     * Elimina un artículo
     */
    public function delete(): void
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);
            $id = $data['id'] ?? null;

            if (!$id) $this->respond(400, ['message' => 'ID requerido.']);

            if ($this->model->delete((int)$id)) {
                $this->respond(200, ['message' => 'Artículo eliminado del sistema.']);
            } else {
                $this->respond(500, ['message' => 'Error al eliminar artículo.']);
            }
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al eliminar artículo');
        }
    }

    /**
     * Actualización masiva de precios o estados para múltiples artículos.
     * Body (Precios): { campo: 'precio_actual'|'costo_actual', precios: { [id]: valor } }
     * Body (Estado):  { action: 'bulk-status', ids: [], activo: boolean }
     */
    public function bulkUpdate(): void
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);
            $action = $data['action'] ?? null;

            // Caso 1: Actualizar estado (activar/desactivar) en lote
            if ($action === 'bulk-status') {
                $ids    = $data['ids']    ?? [];
                $activo = $data['activo'] ?? false;

                if (empty($ids) || !is_array($ids)) {
                    $this->respond(400, ['message' => 'Se requieren IDs de artículos.']);
                    return;
                }

                $affected = $this->model->bulkUpdateStatus($ids, (bool)$activo);
                $this->respond(200, ['message' => "{$affected} artículo(s) actualizado(s).", 'affected' => $affected]);
                return;
            }

            // Caso 2: Actualizar precios en lote
            $campo  = $data['campo']   ?? null;
            $precios = $data['precios'] ?? [];

            $camposPermitidos = ['precio_actual', 'costo_actual'];
            if (!in_array($campo, $camposPermitidos, true)) {
                $this->respond(400, ['message' => 'Campo inválido o acción no especificada.']);
                return;
            }

            if (empty($precios) || !is_array($precios)) {
                $this->respond(400, ['message' => 'Se requiere al menos un precio.']);
                return;
            }

            // Validar que todos los valores sean numéricos y no negativos
            foreach ($precios as $id => $valor) {
                if (!is_numeric($valor) || (float)$valor < 0) {
                    $this->respond(400, ['message' => "Valor inválido para el artículo ID {$id}."]);
                    return;
                }
            }

            $ids = array_keys($precios);
            $affected = $this->model->bulkUpdatePrecios($ids, $campo, $precios);

            $this->respond(200, ['message' => "{$affected} artículo(s) actualizado(s).", 'affected' => $affected]);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error en actualización masiva');
        }
    }
}
