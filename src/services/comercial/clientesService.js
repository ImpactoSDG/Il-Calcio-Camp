import api from '../api';

const clientesService = {
  // --- Clientes (/clientes) ---
  getClientes: () => api.get('/clientes').then(r => r.data),
  getClienteById: (id) => api.get(`/clientes/${id}`).then(r => r.data),
  getMovimientos: (id) => api.get(`/clientes/${id}?action=movimientos`).then(r => r.data),
  crearCliente: (data) => api.post('/clientes', data).then(r => r.data),
  actualizarCliente: (data) => api.put('/clientes', data).then(r => r.data),
  eliminarCliente: (id) => api.delete('/clientes', { data: { id } }).then(r => r.data),
  setActivo: (id, activo) => api.post('/clientes/activo', { id, activo }).then(r => r.data),

  // --- Clientes-Equipos (/clientes-equipos) ---
  getClienteEquipos: () => api.get('/clientes-equipos').then(r => r.data),
  getClienteEquiposByEquipo: (idEquipo) => api.get(`/clientes-equipos?equipo=${idEquipo}`).then(r => r.data),
  crearClienteEquipo: (data) => api.post('/clientes-equipos', data).then(r => r.data),
  eliminarClienteEquipo: (id) => api.delete('/clientes-equipos', { data: { id_cliente_equipo: id } }).then(r => r.data),
};

export default clientesService;
