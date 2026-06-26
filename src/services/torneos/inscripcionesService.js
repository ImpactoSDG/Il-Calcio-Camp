import api from '../api'

const inscripcionesService = {
  getByTorneo: (idTorneo, idEstado = null) => {
    const params = { id_torneo: idTorneo }
    if (idEstado !== null) params.id_estado = idEstado
    return api.get('/inscripciones', { params }).then(r => r.data)
  },
  getById: (id) => api.get(`/inscripciones/${id}`).then(r => r.data),
  aprobar: (id, emailBody) => api.post('/inscripciones/aprobar', { id, email_body: emailBody }).then(r => r.data),
  observar: (id, observacion, emailBody) => api.post('/inscripciones/observar', { id, observacion, email_body: emailBody }).then(r => r.data),
  rechazar: (id, observacion, emailBody) => api.post('/inscripciones/rechazar', { id, observacion, email_body: emailBody }).then(r => r.data),
  getJugadores: (idInscripcion) => api.get('/inscripciones-jugadores', { params: { id_inscripcion: idInscripcion } }).then(r => r.data),
  setEstadoDocumentacion: (idJugador, estado) => api.put('/inscripciones-jugadores', { id: idJugador, estado_documentacion: estado }).then(r => r.data),
}

export default inscripcionesService
