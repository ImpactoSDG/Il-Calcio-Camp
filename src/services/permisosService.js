import api from './api';

const permisosService = {
  getMatrizPermisos: async () => {
    try {
      const response = await api.get('/gestion-permisos');
      return response.data;
    } catch (error) {
      console.error("Error al obtener la matriz de permisos:", error);
      throw error;
    }
  },

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