import { api } from './api'

export function listarCompras(page = 1, busca = '') {
  const termo = encodeURIComponent(busca.trim())
  return api.get(`/api/compras?page=${page}&busca=${termo}`)
}

export function registrarCompra(payload) {
  return api.post('/api/compras', payload)
}
