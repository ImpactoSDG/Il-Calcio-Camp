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

  update: async (usuarioData) => {
    const response = await api.put('/usuarios', usuarioData);
    return response.data;
  },

  delete: async (id) => {
    const response = await api.delete('/usuarios', { data: { id } });
    return response.data;
  },

  updatePassword: async (id, nuevaContrasena) => {
    const response = await api.post('/usuarios?action=password', {
      id: id,
      contrasena: nuevaContrasena
    });
    return response.data;
  }
};

export default usuariosService;