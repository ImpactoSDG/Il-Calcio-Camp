import api from './api';

const proveedoresService = {
  // --- Proveedores (/proveedores) ---
  getProveedores: () => api.get('/proveedores').then(r => r.data),
  getProveedoresActivos: () => api.get('/proveedores?activos=1').then(r => r.data),
  getProveedorById: (id) => api.get(`/proveedores/${id}`).then(r => r.data),
  crearProveedor: (data) => api.post('/proveedores', data).then(r => r.data),
  actualizarProveedor: (data) => api.put('/proveedores', data).then(r => r.data),
  eliminarProveedor: (id) => api.delete('/proveedores', { data: { id } }).then(r => r.data),

  // --- Pedidos a Proveedor (/pedidos-proveedor) ---
  getPedidos: () => api.get('/pedidos-proveedor').then(r => r.data),
  getPedidoById: (id) => api.get(`/pedidos-proveedor/${id}`).then(r => r.data),
  crearPedido: (data) => api.post('/pedidos-proveedor', data).then(r => r.data),
  actualizarPedido: (data) => api.put('/pedidos-proveedor', data).then(r => r.data),
  eliminarPedido: (id) => api.delete('/pedidos-proveedor', { data: { id } }).then(r => r.data),
  /**
   * Acción crítica: cambia el estado del pedido.
   * Si estado === 'recibido', el backend registra los ingresos de artículos (actualiza stock).
   */
  cambiarEstadoPedido: (id, estado) =>
    api.post('/pedidos-proveedor/cambiar-estado', { id_pedido_proveedor: id, estado }).then(r => r.data),

  // --- Pagos a Proveedor (/pagos-proveedor) ---
  getPagos: () => api.get('/pagos-proveedor').then(r => r.data),
  getPagosByProveedor: (idProveedor) => api.get(`/pagos-proveedor?proveedor=${idProveedor}`).then(r => r.data),
  getPagoById: (id) => api.get(`/pagos-proveedor/${id}`).then(r => r.data),
  crearPago: (data) => api.post('/pagos-proveedor', data).then(r => r.data),
  eliminarPago: (id) => api.delete('/pagos-proveedor', { data: { id } }).then(r => r.data),
};

export default proveedoresService;
