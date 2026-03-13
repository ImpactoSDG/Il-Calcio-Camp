import api from './api'

const planTorneoService = {
  simular: (data) => api.post('/planificacion-torneo', data).then(r => r.data),
  confirmar: (data) => api.post('/planificacion-torneo/confirmar', data).then(r => r.data),
  getConfirmada: (idTorneo) => api.get('/planificacion-torneo/confirmada', { params: { id_torneo: idTorneo } }).then(r => r.data),
  getDetalleGestion: (idTorneo) => api.get('/planificacion-torneo/detalle', { params: { id_torneo: idTorneo } }).then(r => r.data),
  getEquiposDisponibles: (idTorneo) => api.get('/planificacion-torneo/equipos-disponibles', { params: { id_torneo: idTorneo } }).then(r => r.data),
  subirComprobante: (formData) => api.post('/planificacion-torneo/subir-comprobante', formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
  }).then(r => r.data),
  inscribirEquipos: (data) => api.post('/planificacion-torneo/inscribir-equipos', data).then(r => r.data),
  eliminarInscripcion: (data) => api.post('/planificacion-torneo/eliminar-inscripcion', data).then(r => r.data),
  asignarEquipos: (data) => api.post('/planificacion-torneo/asignar-equipos', data).then(r => r.data),
}

export default planTorneoService
