/**
 * Utilidades para formateo de datos en la aplicación.
 */

/**
 * Formatea un número con separadores de miles (punto) y decimales (coma).
 * @param {number|string} value - El valor a formatear.
 * @param {number} decimals - Cantidad de decimales (por defecto 0).
 * @param {boolean} hideRoundDecimals - Si es true, oculta .00 si el número es entero.
 * @returns {string} - El string formateado (ej. 1.000).
 */
export const formatNumber = (value, decimals = 0, hideRoundDecimals = false) => {
  if (value === null || value === undefined || value === '') return '';
  
  const number = typeof value === 'string' ? parseFloat(value.replace(',', '.')) : value;
  
  if (isNaN(number)) return '';

  // Si el numero es entero y se solicita ocultar decimales redondos
  if (hideRoundDecimals && Number.isInteger(number)) {
    return new Intl.NumberFormat('de-DE', {
      minimumFractionDigits: 0,
      maximumFractionDigits: 0
    }).format(number);
  }

  return new Intl.NumberFormat('de-DE', {
    minimumFractionDigits: decimals,
    maximumFractionDigits: decimals
  }).format(number);
};

/**
 * Formatea un valor monetario con separadores de miles y hasta 2 decimales,
 * omitiendo los decimales si el valor es entero (ej. 1234 → "1.234", 1234.5 → "1.234,50").
 * @param {number|string} value - El valor a formatear.
 * @returns {string} - El string formateado.
 */
export const formatMoney = (value) => formatNumber(value, 2, true);

/**
 * Limpia un string formateado para obtener un número puro (para la DB).
 * @param {string} value - El valor con puntos/comas (ej. "1.000").
 * @returns {number|null} - El número para guardar (ej. 1000).
 */
export const parseNumber = (value) => {
  if (!value && value !== 0) return null;
  
  // Eliminar puntos de miles y reemplazar coma decimal por punto
  const cleaned = value.toString()
    .replace(/\./g, '')
    .replace(',', '.');
    
  const parsed = parseFloat(cleaned);
  return isNaN(parsed) ? null : parsed;
};
