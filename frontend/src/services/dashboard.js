import { listarCompras } from './compras'
import { listarProdutos } from './produtos'
import { listarVendas } from './vendas'

export async function obterTotais() {
  const [produtosRes, comprasRes, vendasRes] = await Promise.all([
    listarProdutos(1),
    listarCompras(1),
    listarVendas(1),
  ])
  return {
    produtos: produtosRes.meta?.total ?? 0,
    compras: comprasRes.meta?.total ?? 0,
    vendas: vendasRes.meta?.total ?? 0,
  }
}
