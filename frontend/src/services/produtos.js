import { api } from './api'

export function listarProdutos(page = 1, busca = '') {
  const termo = encodeURIComponent(busca.trim())
  return api.get(`/api/produtos?page=${page}&busca=${termo}`)
}

export async function listarTodosProdutos() {
  const acumulado = []
  let pagina = 1
  let ultimaPagina = 1

  while (pagina <= ultimaPagina) {
    const resposta = await listarProdutos(pagina)
    acumulado.push(...(resposta.data || []))
    ultimaPagina = resposta?.meta?.last_page || 1
    pagina += 1
  }

  return acumulado
}

export function cadastrarProduto(payload) {
  return api.post('/api/produtos', payload)
}

export function atualizarProduto(id, payload) {
  return api.put(`/api/produtos/${id}`, payload)
}

export function excluirProduto(id) {
  return api.delete(`/api/produtos/${id}`)
}
