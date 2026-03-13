<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/BaseController.php';
require_once __DIR__ . '/../models/PedidoProveedor.php';

class PedidoProveedorController extends BaseController
{
    private PedidoProveedor $model;

    public function __construct(PDO $db)
    {
        parent::__construct($db);
        $this->model = new PedidoProveedor($this->db);
    }

    public function getAll(): void
    {
        try {
            $result = $this->model->getAll();
            $this->respond(200, $result);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al obtener pedidos a proveedores');
        }
    }

    public function getById(): void
    {
        try {
            $id = $_GET['id'] ?? null;
            if (!$id) $this->respond(400, ['message' => 'ID requerido.']);

            $result = $this->model->getById((int)$id);
            $result
                ? $this->respond(200, $result)
                : $this->respond(404, ['message' => 'Pedido no encontrado.']);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al obtener pedido');
        }
    }

    public function store(): void
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);

            if (empty($data['id_proveedor'])) {
                $this->respond(400, ['message' => 'El proveedor es obligatorio.']);
            }
            if (empty($data['items']) || !is_array($data['items'])) {
                $this->respond(400, ['message' => 'El pedido debe incluir al menos un artículo.']);
            }

            $validatedItems = $this->validateItems($data['items']);
            if (!$validatedItems['valid']) {
                $this->respond(400, ['message' => $validatedItems['error']]);
            }

            $id = $this->model->create(
                (int)$data['id_proveedor'],
                $data['fecha_entrega'] ?? null,
                $data['observaciones'] ?? null,
                $data['items']
            );

            $this->respond(201, ['message' => 'Pedido creado exitosamente.', 'id' => $id]);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al crear pedido');
        }
    }

    public function update(): void
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);

            if (empty($data['id_pedido_proveedor'])) $this->respond(400, ['message' => 'ID del pedido requerido.']);
            if (empty($data['id_proveedor'])) $this->respond(400, ['message' => 'El proveedor es obligatorio.']);
            if (empty($data['items']) || !is_array($data['items'])) {
                $this->respond(400, ['message' => 'El pedido debe incluir al menos un artículo.']);
            }

            $validatedItems = $this->validateItems($data['items']);
            if (!$validatedItems['valid']) {
                $this->respond(400, ['message' => $validatedItems['error']]);
            }

            $this->model->update(
                (int)$data['id_pedido_proveedor'],
                (int)$data['id_proveedor'],
                $data['fecha_entrega'] ?? null,
                $data['observaciones'] ?? null,
                $data['items']
            );

            $this->respond(200, ['message' => 'Pedido actualizado correctamente.']);
        } catch (\RuntimeException $e) {
            $this->respond(409, ['message' => $e->getMessage()]);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al actualizar pedido');
        }
    }

    /**
     * Endpoint crítico: cambia el estado del pedido.
     * Si el nuevo estado es 'recibido', ejecuta la lógica transaccional
     * que registra los ingresos de artículos y actualiza el stock.
     */
    public function cambiarEstado(): void
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);
            $id          = $data['id_pedido_proveedor'] ?? null;
            $nuevoEstado = $data['estado'] ?? null;

            if (!$id || !$nuevoEstado) {
                $this->respond(400, ['message' => 'Se requieren id_pedido_proveedor y estado.']);
            }

            $this->model->cambiarEstado((int)$id, $nuevoEstado);

            $msg = $nuevoEstado === 'recibido'
                ? 'Pedido recibido correctamente. El stock ha sido actualizado.'
                : 'Pedido cancelado correctamente.';

            $this->respond(200, ['message' => $msg]);
        } catch (\RuntimeException $e) {
            $this->respond(409, ['message' => $e->getMessage()]);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al cambiar estado del pedido');
        }
    }

    public function delete(): void
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);
            $id = $data['id'] ?? null;
            if (!$id) $this->respond(400, ['message' => 'ID requerido.']);

            $this->model->delete((int)$id);
            $this->respond(200, ['message' => 'Pedido eliminado correctamente.']);
        } catch (\RuntimeException $e) {
            $this->respond(409, ['message' => $e->getMessage()]);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al eliminar pedido');
        }
    }

    private function validateItems(array $items): array
    {
        foreach ($items as $index => $item) {
            if (empty($item['id_articulo'])) {
                return ['valid' => false, 'error' => "El ítem #" . ($index + 1) . " no tiene artículo."];
            }
            if (empty($item['cantidad']) || (float)$item['cantidad'] <= 0) {
                return ['valid' => false, 'error' => "El ítem #" . ($index + 1) . " debe tener una cantidad mayor a cero."];
            }
        }
        return ['valid' => true];
    }
}
