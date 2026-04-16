import api from '../api';

const facturaService = {
  /**
   * Emite la factura electrónica AFIP para una venta.
   * POST /facturar-venta  { id_venta }
   * Retorna { success, id_factura, cae, factura, already_issued? }
   */
  facturarVenta: (idVenta) =>
    api.post('/facturar-venta', { id_venta: idVenta }).then(r => r.data),

  /**
   * Obtiene los datos de una factura ya emitida (incluye datos de venta, cliente y emisor).
   * GET /facturas/{id}
   */
  getFactura: (idFactura) =>
    api.get(`/facturas/${idFactura}`).then(r => r.data),

  /**
   * Obtiene la factura asociada a una venta específica.
   * GET /facturas/venta/{idVenta}
   */
  getFacturaPorVenta: (idVenta) =>
    api.get(`/facturas/venta/${idVenta}`).then(r => r.data),

  /**
   * Actualiza datos editables del receptor (dirección, localidad, provincia, descripción).
   * PUT /facturas/{id}  { direccion, localidad, provincia, descripcion }
   */
  actualizarReceptor: (idFactura, datos) =>
    api.put(`/facturas/${idFactura}`, datos).then(r => r.data),

  /**
   * Marca una venta como pendiente_afip tras un fallo al facturar.
   * POST /facturas-marcar-pendiente  { id_venta, error_msg }
   */
  marcarPendienteAfip: (idVenta, errorMsg = '') =>
    api.post('/facturas-marcar-pendiente', { id_venta: idVenta, error_msg: errorMsg }).then(r => r.data),
};

export default facturaService;
