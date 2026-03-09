import api from './api';

const impresoraTiqueteraService = {
  getAll: async () => {
    const response = await api.get('/impresoras-tiquetera');
    return response.data;
  },

  getDefault: async () => {
    const response = await api.get('/impresoras-tiquetera?default=1');
    return response.data;
  },

  getById: async (id) => {
    const response = await api.get(`/impresoras-tiquetera?id=${id}`);
    return response.data;
  },

  create: async (data) => {
    const response = await api.post('/impresoras-tiquetera', data);
    return response.data;
  },

  update: async (data) => {
    const response = await api.put('/impresoras-tiquetera', data);
    return response.data;
  },

  delete: async (id) => {
    const response = await api.delete('/impresoras-tiquetera', { data: { id } });
    return response.data;
  },
};

export default impresoraTiqueteraService;
