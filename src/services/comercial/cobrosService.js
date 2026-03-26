import api from '../api';

const cobrosService = {
  // Todos los cobros (sin filtro de sección)
  getCobros: () => api.get('/cobros').then(r => r.data),

  // Cobros sin cliente asociado
  getCobrosSinCliente: (params = {}) =>
    api.get('/cobros', { params: { action: 'sin-cliente', ...params } }).then(r => r.data),

  // Cobros con cliente asociado
  getCobrosConCliente: (params = {}) =>
    api.get('/cobros', { params: { action: 'con-cliente', ...params } }).then(r => r.data),

  // Ventas con saldo pendiente de cobro
  getVentasPendientes: (params = {}) =>
    api.get('/cobros', { params: { action: 'ventas-pendientes', ...params } }).then(r => r.data),

  // Registrar un cobro para una venta específica
  registrarCobroVenta: (data) =>
    api.post('/cobros', { action: 'registrar-pago', ...data }).then(r => r.data),

  // Reporte de cobros de un día (default: hoy), agrupado por usuario y medio de cobro
  getReporteDia: (params = {}) =>
    api.get('/cobros', { params: { action: 'reporte-dia', ...params } }).then(r => r.data),
};

export default cobrosService;
