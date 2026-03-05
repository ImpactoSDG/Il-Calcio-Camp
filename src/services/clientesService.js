import api from './api';

const clientesService = {
  // --- Clientes (/clientes) ---
  getClientes: () => api.get('/clientes').then(r => r.data),
  getClienteById: (id) => api.get(`/clientes/${id}`).then(r => r.data),
  crearCliente: (data) => api.post('/clientes', data).then(r => r.data),
  actualizarCliente: (data) => api.put('/clientes', data).then(r => r.data),
  eliminarCliente: (id) => api.delete('/clientes', { data: { id } }).then(r => r.data),

  // --- Clientes-Equipos (/clientes-equipos) ---
  getClienteEquipos: () => api.get('/clientes-equipos').then(r => r.data),
  crearClienteEquipo: (data) => api.post('/clientes-equipos', data).then(r => r.data),
  eliminarClienteEquipo: (id) => api.delete('/clientes-equipos', { data: { id_cliente_equipo: id } }).then(r => r.data),
};

export default clientesService;
