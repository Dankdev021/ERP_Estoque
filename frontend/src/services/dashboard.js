import { api } from './api'

export async function obterTotais() {
  return api.get('/api/dashboard')
}
