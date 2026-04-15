/**
 * Detecta si la aplicación está ejecutándose en localhost
 * @returns {boolean}
 */
export function isLocalhost() {
  if (typeof window === 'undefined') return false;
  
  const hostname = window.location.hostname;
  return hostname === 'localhost' || hostname === '127.0.0.1' || hostname.startsWith('192.168.');
}

/**
 * Obtiene la URL base de la API
 * @returns {string}
 */
export function getApiBaseUrl() {
  return import.meta.env.VITE_API_URL || 'http://localhost/Il-Calcio-Camp/api';
}

/**
 * Obtiene el ambiente actual
 * @returns {string} - 'development', 'production', 'testing'
 */
export function getEnvironment() {
  return import.meta.env.MODE || 'development';
}
