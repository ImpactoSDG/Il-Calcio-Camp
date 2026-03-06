import api from './api';

const configuracionService = {
  getAll: async () => {
    const response = await api.get('/configuraciones');
    return response.data;
  },

  getSimbolo: async () => {
    const response = await api.get('/configuraciones');
    const configs = response.data;
    const entry = configs.find(c => c.clave === 'simbolo_dia');
    return entry ? entry.valor : '$';
  },

  getById: async (id) => {
    const response = await api.get(`/configuraciones?id=${id}`);
    return response.data;
  },

  create: async (configData) => {
    const response = await api.post('/configuraciones', configData);
    return response.data;
  },

  update: async (configData) => {
    const response = await api.put('/configuraciones', configData);
    return response.data;
  },

  delete: async (id) => {
    const response = await api.delete('/configuraciones', { data: { id } });
    return response.data;
  }
};

export default configuracionService;