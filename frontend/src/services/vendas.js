import { api } from './api'

export function listarVendas(page = 1, busca = '') {
  const termo = encodeURIComponent(busca.trim())
  return api.get(`/api/vendas?page=${page}&busca=${termo}`)
}

export function registrarVenda(payload) {
  return api.post('/api/vendas', payload)
}

export function cancelarVenda(numeroVenda) {
  return api.post(`/api/vendas/${numeroVenda}/cancelar`, {})
}
