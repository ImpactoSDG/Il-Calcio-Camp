import axios from 'axios'

// Instancia de axios SIN interceptor de autenticación, para endpoints públicos
const publicApi = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
})

export default publicApi
