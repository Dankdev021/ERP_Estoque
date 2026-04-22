const API_URL = (import.meta.env.VITE_API_URL || 'http://localhost:8000').replace(/\/$/, '')

async function request(path, options = {}) {
  const response = await fetch(`${API_URL}${path}`, {
    headers: {
      'Content-Type': 'application/json',
      ...(options.headers || {}),
    },
    ...options,
  })

  let body = null
  try {
    body = await response.json()
  } catch {
    body = null
  }

  if (!response.ok) {
    const message = body?.message || 'Erro ao processar requisição.'
    const error = new Error(message)
    error.status = response.status
    error.data = body
    throw error
  }

  return body
}

export const api = {
  get(path) {
    return request(path)
  },
  post(path, data) {
    return request(path, { method: 'POST', body: JSON.stringify(data) })
  },
  put(path, data) {
    return request(path, { method: 'PUT', body: JSON.stringify(data) })
  },
  patch(path, data) {
    return request(path, { method: 'PATCH', body: JSON.stringify(data) })
  },
  delete(path) {
    return request(path, { method: 'DELETE' })
  },
}
