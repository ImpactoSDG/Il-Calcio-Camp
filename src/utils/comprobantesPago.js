// Los comprobantes de pago los sube el portal de inscripciones a su propio hosting,
// no al backend de este sistema, así que la URL se arma contra ese host fijo.
const COMPROBANTES_PAGO_BASE_URL = 'https://impactosdg.com/ilcalciocamp/comprobantes_pago/'

export const resolveComprobantePagoUrl = (nombreArchivo) => {
  if (!nombreArchivo) return ''
  return `${COMPROBANTES_PAGO_BASE_URL}${nombreArchivo}`
}
