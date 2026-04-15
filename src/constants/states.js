/**
 * Estados de Venta - Sincronizados con la BD
 * NOTA: Estos IDs están hardcodeados según estructura actual de la BD
 * Si los IDs de estado cambian en la BD, actualizar aquí
 */
export const VENTA_STATES = {
  ABIERTA: 2,    // estado_venta.id = 2
  CERRADA: 3,    // estado_venta.id = 3
  PAUSA: 4,      // estado_venta.id = 4
};

export const getStateLabel = (idEstado) => {
  const labels = {
    [VENTA_STATES.ABIERTA]: 'Abierta',
    [VENTA_STATES.CERRADA]: 'Cerrada',
    [VENTA_STATES.PAUSA]: 'Pausa',
  };
  return labels[idEstado] || 'Desconocido';
};
