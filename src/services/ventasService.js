import api from './api';

const ventasService = {
  // --- Ventas (/ventas) ---
  getVentas: () => api.get('/ventas').then(r => r.data),
  getVentaById: (id) => api.get(`/ventas/${id}`).then(r => r.data),
  crearVenta: (data) => api.post('/ventas', data).then(r => r.data),
  actualizarVenta: (data) => api.put('/ventas', data).then(r => r.data),
  eliminarVenta: (id) => api.delete('/ventas', { data: { id } }).then(r => r.data),

  // --- Artículos de Venta (/articulos-venta) ---
  getArticulosVenta: () => api.get('/articulos-venta').then(r => r.data),
  getArticulosVentaById: (id) => api.get(`/articulos-venta/${id}`).then(r => r.data),
  crearArticuloVenta: (data) => api.post('/articulos-venta', data).then(r => r.data),
  actualizarArticuloVenta: (data) => api.put('/articulos-venta', data).then(r => r.data),
  eliminarArticuloVenta: (id) => api.delete('/articulos-venta', { data: { id } }).then(r => r.data),
};

export default ventasService;
