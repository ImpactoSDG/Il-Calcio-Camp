import api from './api';

const moduloService = {
  update: async (moduloData) => {
    const response = await api.put('/modulos', moduloData);
    return response.data;
  }
};

export default moduloService;