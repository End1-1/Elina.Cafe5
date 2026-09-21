import axios from 'axios'

// Same-origin relative API — works for Vite dev (proxy) and any host (/man/, prod domain).
export const API_URL = '/engine/v2'

const api = axios.create({
  baseURL: API_URL,
  withCredentials: true,
  headers: {
    'Content-Type': 'application/json'
  }
})

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token')
  if (token) {
    config.headers.authorization = `bearer ${token}`
  }
  return config
})

export default api
