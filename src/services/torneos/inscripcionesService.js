import api from '../api'

const inscripcionesService = {
  getByTorneo: (idTorneo, idEstado = null) => {
    const params = { id_torneo: idTorneo }
    if (idEstado !== null) params.id_estado = idEstado
    return api.get('/inscripciones', { params }).then(r => r.data)
  },
  getById: (id) => api.get(`/inscripciones/${id}`).then(r => r.data),
  aprobar: (id, idDisciplina) => api.post('/inscripciones/aprobar', { id, id_disciplina: idDisciplina }).then(r => r.data),
  observar: (id, observacion) => api.post('/inscripciones/observar', { id, observacion }).then(r => r.data),
  rechazar: (id, observacion) => api.post('/inscripciones/rechazar', { id, observacion }).then(r => r.data),
  getJugadores: (idInscripcion) => api.get('/inscripciones-jugadores', { params: { id_inscripcion: idInscripcion } }).then(r => r.data),
  setEstadoDocumentacion: (idJugador, estado) => api.put('/inscripciones-jugadores', { id: idJugador, estado_documentacion: estado }).then(r => r.data),
}

export default inscripcionesService
