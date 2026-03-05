<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/BaseController.php';
require_once __DIR__ . '/../models/Articulo.php';

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
     * Crea un nuevo artículo
     */
    public function store(): void
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);

            if (empty($data['nombre'])) {
                $this->respond(400, ['message' => 'Nombre del artículo requerido.']);
            }

            $precioActual = $data['precio_actual'] ?? null;
            $costoActual = $data['costo_actual'] ?? null;
            $codBarra = $data['cod_barra'] ?? null;
            $idCategoria = $data['id_categoria_articulo'] ?? null;
            $activo = $data['activo'] ?? true;

            $id = $this->model->create(
                $data['nombre'],
                $precioActual,
                $costoActual,
                $codBarra,
                $idCategoria,
                (bool)$activo
            );

            if ($id) {
                $this->respond(201, ['message' => 'Artículo creado exitosamente.', 'id' => $id]);
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
            }

            $precioActual = $data['precio_actual'] ?? null;
            $costoActual = $data['costo_actual'] ?? null;
            $codBarra = $data['cod_barra'] ?? null;
            $idCategoria = $data['id_categoria_articulo'] ?? null;
            $activo = $data['activo'] ?? true;

            if ($this->model->update(
                (int)$data['id'],
                $data['nombre'],
                $precioActual,
                $costoActual,
                $codBarra,
                $idCategoria,
                (bool)$activo
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
                $this->respond(200, ['message' => 'Artículo eliminado exitosamente.']);
            } else {
                $this->respond(500, ['message' => 'Error al eliminar artículo.']);
            }
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al eliminar artículo');
        }
    }
}
