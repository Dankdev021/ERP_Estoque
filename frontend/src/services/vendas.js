import { api } from './api'

export function listarVendas(page = 1) {
  return api.get(`/api/vendas?page=${page}`)
}

export function registrarVenda(payload) {
  return api.post('/api/vendas', payload)
}

export function cancelarVenda(numeroVenda) {
  return api.post(`/api/vendas/${numeroVenda}/cancelar`, {})
}
