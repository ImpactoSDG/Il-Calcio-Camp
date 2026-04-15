import Fuse from 'fuse.js';

/**
 * Realiza una búsqueda difusa en una lista de objetos.
 * 
 * @param {Array} list - La lista de objetos donde buscar.
 * @param {string} query - El término de búsqueda.
 * @param {Array} keys - Las propiedades del objeto en las que buscar (ej: ['nombre', 'sku']).
 * @param {Object} options - Opciones adicionales para Fuse.js.
 * @returns {Array} - Los objetos que coinciden con la búsqueda.
 */
export function fusionSearch(list, query, keys, options = {}) {
  if (!query || query.trim() === '' || !list || list.length === 0) {
    return list;
  }

  const defaultOptions = {
    keys,
    threshold: 0.3,
    distance: 100,
    ignoreLocation: true,
    ...options
  };

  const fuse = new Fuse(list, defaultOptions);
  return fuse.search(query).map(result => result.item);
}
