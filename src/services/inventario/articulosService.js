import api from '../api';

const articulosService = {
  // --- Artículos (/articulos) ---
  getArticulos: () => api.get('/articulos').then(r => r.data),
  getAllActivos: () => api.get('/articulos?activos=1').then(r => r.data),
  getArticuloById: (id) => api.get(`/articulos/${id}`).then(r => r.data),
  crearArticulo: (data) => api.post('/articulos', data).then(r => r.data),
  actualizarArticulo: (data) => api.put('/articulos', data).then(r => r.data),
  eliminarArticulo: (id) => api.delete('/articulos', { data: { id } }).then(r => r.data),
  subirImagen: (id, nombre, file) => {
    const formData = new FormData();
    formData.append('id', id);
    formData.append('nombre', nombre);
    formData.append('imagen', file);
    return api.post('/articulos/upload-image', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    }).then(r => r.data);
  },
  // campo: 'precio_actual' | 'costo_actual', precios: { [id]: valor }
  bulkUpdatePrecios: (campo, precios) => api.patch('/articulos', { campo, precios }).then(r => r.data),
  // ids: [id1, id2, ...], activo: boolean
  bulkUpdateStatus: (ids, activo) => api.patch('/articulos', { action: 'bulk-status', ids, activo }).then(r => r.data),

  // --- Artículos Vendidos (/articulos-vendidos) ---
  getArticulosVendidos: (fechaDesde, fechaHasta) =>
    api.get('/articulos-vendidos', { params: { fecha_desde: fechaDesde, fecha_hasta: fechaHasta } }).then(r => r.data),

  getDetalleVentaArticulo: (idArticulo, fechaDesde, fechaHasta) =>
    api.get('/articulos-venta', { params: { id_articulo: idArticulo, fecha_desde: fechaDesde, fecha_hasta: fechaHasta } }).then(r => r.data),

  // --- Ingresos de Artículo (/ingresos-articulo) ---
  getIngresos: () => api.get('/ingresos-articulo').then(r => r.data),
  getIngresoById: (id) => api.get(`/ingresos-articulo/${id}`).then(r => r.data),
  crearIngreso: (data) => api.post('/ingresos-articulo', data).then(r => r.data),
  actualizarIngreso: (data) => api.put('/ingresos-articulo', data).then(r => r.data),
  eliminarIngreso: (id) => api.delete('/ingresos-articulo', { data: { id } }).then(r => r.data),
};

export default articulosService;
