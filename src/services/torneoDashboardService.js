import api from './api'

const torneoDashboardService = {
  getDashboard: (idTorneo) => api.get('/dashboard-torneo', { params: { id_torneo: idTorneo } }).then(r => r.data),
}

export default torneoDashboardService
