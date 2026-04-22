import { api } from './api'

export function listarCompras(page = 1) {
  return api.get(`/api/compras?page=${page}`)
}

export function registrarCompra(payload) {
  return api.post('/api/compras', payload)
}
