/**
 * Utilidades para el flujo de "cliente rápido" en la carga de ventas.
 */

/**
 * Decide cómo debe tratarse un cliente que llega desde el modal de cliente rápido
 * (QuickClientModal) al asignarlo a una venta.
 *
 * IMPORTANTE: la intención NO se puede deducir del formato del id. Los clientes
 * nuevos SIEMPRE traen un id temporal ('temp-...'), así que basarse en el id
 * hacía que un cliente nuevo se confundiera con una edición (causa del bug de
 * foreign key: el id 'temp-...' terminaba viajando al backend). La decisión se
 * toma a partir del flag explícito `isNew` que emite QuickClientModal.
 *
 * @param {object} cliente - Cliente emitido por QuickClientModal. Se espera al
 *   menos `id` y, opcionalmente, los flags `isNew` / `isUpdate`.
 * @returns {{ payload: object, esNuevo: boolean }}
 *   - `esNuevo`: true si es un cliente nuevo que se creará al guardar la venta.
 *   - `payload`: objeto a emitir en el evento 'client-created'. Para clientes
 *     existentes o editados lleva `isUpdate: true`; para nuevos se conserva tal cual.
 */
export const resolverClienteRapido = (cliente) => {
  if (cliente?.isNew) {
    return { payload: cliente, esNuevo: true };
  }

  return { payload: { ...cliente, isUpdate: true }, esNuevo: false };
};
