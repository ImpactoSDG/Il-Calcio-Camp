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
   * Obtiene los datos de una factura ya emitida (incluye datos de venta y cliente).
   * GET /facturas/{id}
   */
  getFactura: (idFactura) =>
    api.get(`/facturas/${idFactura}`).then(r => r.data),

  /**
   * Actualiza datos editables del receptor (dirección, localidad, provincia, descripción).
   * PUT /facturas/{id}  { direccion, localidad, provincia, descripcion }
   */
  actualizarReceptor: (idFactura, datos) =>
    api.put(`/facturas/${idFactura}`, datos).then(r => r.data),
};

export default facturaService;
