import api from './api';

const datosMaestrosService = {
  // --- Categorías de Artículo (/categorias-articulo) ---
  getCategorias: () => api.get('/categorias-articulo').then(r => r.data),
  crearCategoria: (data) => api.post('/categorias-articulo', data).then(r => r.data),
  actualizarCategoria: (data) => api.put('/categorias-articulo', data).then(r => r.data),
  eliminarCategoria: (id) => api.delete('/categorias-articulo', { data: { id } }).then(r => r.data),

  // --- Condiciones IVA (/condiciones-iva) --- solo lectura
  getCondicionesIva: () => api.get('/condiciones-iva').then(r => r.data),

  // --- Estados de Venta (/estados-venta) --- solo lectura
  getEstadosVenta: () => api.get('/estados-venta').then(r => r.data),

  // --- Medios de Cobro (/medios-cobro) --- solo lectura
  getMediosCobro: () => api.get('/medios-cobro').then(r => r.data),

  // --- Provincias (/provincias) --- solo lectura
  getProvincias: () => api.get('/provincias').then(r => r.data),

  // --- Equipos (/equipos) ---
  getEquipos: () => api.get('/equipos').then(r => r.data),
  crearEquipo: (data) => api.post('/equipos', data).then(r => r.data),
  actualizarEquipo: (data) => api.put('/equipos', data).then(r => r.data),
  eliminarEquipo: (id) => api.delete('/equipos', { data: { id } }).then(r => r.data),
};

export default datosMaestrosService;
