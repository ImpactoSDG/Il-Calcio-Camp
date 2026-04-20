import api from '@/services/api';

const reportesVentaService = {
  /**
   * Obtiene el reporte de ventas para el período indicado.
   * @param {string} desde - Fecha inicio (YYYY-MM-DD)
   * @param {string} hasta - Fecha fin (YYYY-MM-DD)
   */
  getReporte: (desde, hasta) =>
    api.get('/reportes-venta', { params: { desde, hasta } }).then(r => r.data),
};

export default reportesVentaService;
