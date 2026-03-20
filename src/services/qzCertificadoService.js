import api from './api';

const qzCertificadoService = {
  /**
   * Obtiene la lista de todas las máquinas registradas (sin contenido de archivos).
   */
  getAll: async () => {
    const { data } = await api.get('/qz-certificados');
    return data;
  },

  /**
   * Obtiene el contenido del certificado y la clave privada para una machine_id.
   * Retorna { machine_id, nombre_maquina, cert, pk } o lanza error si no existe.
   */
  getContent: async (machineId) => {
    const { data } = await api.get('/qz-certificados', { params: { machine_id: machineId } });
    return data;
  },

  /**
   * Sube (o reemplaza) los archivos de certificado para una máquina.
   * @param {string} machineId  - UUID de la máquina
   * @param {string} nombreMaquina - Etiqueta legible
   * @param {File} certFile  - Archivo digital-certificate.txt
   * @param {File} pkFile    - Archivo private-key.pem
   */
  upload: async (machineId, nombreMaquina, certFile, pkFile) => {
    const formData = new FormData();
    formData.append('machine_id',     machineId);
    formData.append('nombre_maquina', nombreMaquina);
    if (certFile) formData.append('cert_file', certFile);
    if (pkFile)   formData.append('pk_file',   pkFile);

    const { data } = await api.post('/qz-certificados', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    return data;
  },

  /**
   * Elimina los certificados de una máquina por id de registro.
   */
  delete: async (id) => {
    const { data } = await api.delete('/qz-certificados', { data: { id } });
    return data;
  },
};

export default qzCertificadoService;
