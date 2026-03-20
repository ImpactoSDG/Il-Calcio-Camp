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

  // --- Disciplinas (/disciplinas) --- solo lectura
  getDisciplinas: () => api.get('/disciplinas').then(r => r.data),

  // --- Estados de Evento (/estados-evento) --- solo lectura
  getEstadosEvento: () => api.get('/estados-evento').then(r => r.data),

  // --- Canchas (/canchas) ---
  getCanchas: () => api.get('/canchas').then(r => r.data),
  crearCancha: (data) => api.post('/canchas', data).then(r => r.data),
  actualizarCancha: (data) => api.put('/canchas', data).then(r => r.data),
  eliminarCancha: (id) => api.delete('/canchas', { data: { id } }).then(r => r.data),

  // --- Torneos (/torneos) --- solo lectura
  getTorneos: () => api.get('/torneos').then(r => r.data),
  eliminarTorneo: (id, motivoBaja = null) => api.delete('/torneos', {
    data: {
      id,
      motivo_baja: motivoBaja,
    },
  }).then(r => r.data),

  // --- Equipos (/equipos) ---
  getEquipos: () => api.get('/equipos').then(r => r.data),
  getEquiposPendientes: () => api.get('/equipos?pendientes=1').then(r => r.data),
  confirmarEquipo: (id) => api.post('/equipos/confirmar', { id }).then(r => r.data),
  subirEscudoEquipo: (formData) => api.post('/equipos/subir-escudo', formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
  }).then(r => r.data),
  crearEquipo: (data) => api.post('/equipos', data).then(r => r.data),
  actualizarEquipo: (data) => api.put('/equipos', data).then(r => r.data),
  eliminarEquipo: (id) => api.delete('/equipos', { data: { id } }).then(r => r.data),
  bajaLogicaEquipo: (id) => api.post('/equipos/baja-logica', { id }).then(r => r.data),

  // --- Jugadores (/jugadores) ---
  getJugadores: () => api.get('/jugadores').then(r => r.data),
  getJugadoresByEquipo: (idEquipo) => api.get(`/jugadores?equipo=${idEquipo}`).then(r => r.data),
  crearJugador: (data) => api.post('/jugadores', data).then(r => r.data),
  actualizarJugador: (data) => api.put('/jugadores', data).then(r => r.data),
  eliminarJugador: (id) => api.delete('/jugadores', { data: { id } }).then(r => r.data),

  // --- Arbitros (/arbitros) ---
  getArbitros: () => api.get('/arbitros').then(r => r.data),
  crearArbitro: (data) => api.post('/arbitros', data).then(r => r.data),
  actualizarArbitro: (data) => api.put('/arbitros', data).then(r => r.data),
  eliminarArbitro: (id) => api.delete('/arbitros', { data: { id } }).then(r => r.data),
};

export default datosMaestrosService;
