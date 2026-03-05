import api from './api';

const articulosService = {
  // --- Artículos (/articulos) ---
  getArticulos: () => api.get('/articulos').then(r => r.data),
  getAllActivos: () => api.get('/articulos?activos=1').then(r => r.data),
  getArticuloById: (id) => api.get(`/articulos/${id}`).then(r => r.data),
  crearArticulo: (data) => api.post('/articulos', data).then(r => r.data),
  actualizarArticulo: (data) => api.put('/articulos', data).then(r => r.data),
  eliminarArticulo: (id) => api.delete('/articulos', { data: { id } }).then(r => r.data),

  // --- Ingresos de Artículo (/ingresos-articulo) ---
  getIngresos: () => api.get('/ingresos-articulo').then(r => r.data),
  getIngresoById: (id) => api.get(`/ingresos-articulo/${id}`).then(r => r.data),
  crearIngreso: (data) => api.post('/ingresos-articulo', data).then(r => r.data),
  actualizarIngreso: (data) => api.put('/ingresos-articulo', data).then(r => r.data),
  eliminarIngreso: (id) => api.delete('/ingresos-articulo', { data: { id } }).then(r => r.data),
};

export default articulosService;
