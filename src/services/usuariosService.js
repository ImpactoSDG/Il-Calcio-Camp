import api from './api';

const usuariosService = {
  getAll: async () => {
    const response = await api.get('/usuarios');
    return response.data;
  },

  getById: async (id) => {
    const response = await api.get(`/usuarios?id=${id}`);
    return response.data;
  },

  create: async (usuarioData) => {
    const response = await api.post('/usuarios', usuarioData);
    return response.data;
  },

  update: async (userData) => {
    const response = await api.put('/usuarios', userData);
    return response.data;
  },
  delete: async (id) => {
    const response = await api.delete('/usuarios', { data: { id } });
    return response.data;
  },

  getRoles: async () => {
    const response = await api.get('/roles');
    return response.data;
  },

  updatePassword: async (id, nuevaContrasena) => {
    const response = await api.post('/usuarios?action=password', {
      id: id,
      contrasena: nuevaContrasena
    });
    return response.data;
  },

  refreshModulos: async (userId) => {
    const response = await api.get(`/refresh-modulos?id=${userId}`);
    return response.data;
  },
  toggleFavorito: async (id_usuario, id_modulo, estado) => {
    const response = await api.post('/toggle-favorito', { id_usuario, id_modulo, estado });
    return response.data;
  },

  reorderModulos: async (id_usuario, ordenes) => {
    const response = await api.post('/reorder-modulos', { id_usuario, ordenes });
    return response.data;
  }
};

export default usuariosService;