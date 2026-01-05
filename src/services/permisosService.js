import api from './api'; // Asumiendo que tu archivo base se llama api.js

const permisosService = {
  /**
   * Obtiene la matriz completa: usuarios, módulos y permisos asignados.
   * Corresponde al endpoint: GET /gestion-permisos
   */
  getMatrizPermisos: async () => {
    try {
      const response = await api.get('/gestion-permisos');
      return response.data;
    } catch (error) {
      console.error("Error al obtener la matriz de permisos:", error);
      throw error;
    }
  },

  /**
   * Activa o desactiva un permiso para un usuario.
   * Corresponde al endpoint: POST /toggle-permiso
   * @param {number} idUsuario 
   * @param {number} idModulo 
   * @param {boolean} estado 
   */
  togglePermiso: async (idUsuario, idModulo, estado) => {
    try {
      const response = await api.post('/toggle-permiso', {
        id_usuario: idUsuario,
        id_modulo: idModulo,
        estado: estado
      });
      return response.data;
    } catch (error) {
      console.error("Error al actualizar el permiso:", error);
      throw error;
    }
  }
};

export default permisosService;