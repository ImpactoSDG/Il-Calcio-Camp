import api from './api';

const eventosService = {
  getEventos: () => api.get('/eventos').then(r => r.data),
  getEventoById: (id) => api.get(`/eventos/${id}`).then(r => r.data),
  crearEvento: (data) => api.post('/eventos', data).then(r => r.data),
  actualizarEvento: (data) => api.put('/eventos', data).then(r => r.data),
  eliminarEvento: (id) => api.delete('/eventos', { data: { id } }).then(r => r.data),
};

export default eventosService;