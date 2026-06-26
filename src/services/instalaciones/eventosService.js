import api from '../api';

const eventosService = {
  getEventos: () => api.get('/eventos').then(r => r.data),
  getEventoById: (id) => api.get(`/eventos/${id}`).then(r => r.data),
  crearEvento: (data) => api.post('/eventos', data).then(r => r.data),
  actualizarEvento: (data) => api.put('/eventos', data).then(r => r.data),
  eliminarEvento: (id) => api.delete('/eventos', { data: { id } }).then(r => r.data),
  getTiposEventoPartido: () => api.get('/tipos-evento-partido').then(r => r.data),
  getEventosPartido: (idEvento) => api.get('/eventos-partido', { params: { id_evento: idEvento } }).then(r => r.data),
  getEventosPartidoByTorneo: (idTorneo) => api.get('/eventos-partido', { params: { id_torneo: idTorneo } }).then(r => r.data),
  crearEventoPartido: (data) => api.post('/eventos-partido', data).then(r => r.data),
  eliminarEventoPartido: (id) => api.delete('/eventos-partido', { data: { id } }).then(r => r.data),
};

export default eventosService;