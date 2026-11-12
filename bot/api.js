'use strict'

// Klien API SIMAIK (read-only) untuk bot.
class SimaikApi {
  constructor({ apiBase, apiToken, fetchImpl = globalThis.fetch }) {
    this.base = String(apiBase || '').replace(/\/+$/, '')
    this.token = apiToken || ''
    this.fetch = fetchImpl
  }

  async get(path, params = {}) {
    const url = new URL(`${this.base}/${String(path).replace(/^\/+/, '')}`)
    for (const [k, v] of Object.entries(params)) {
      if (v !== undefined && v !== null && v !== '') url.searchParams.set(k, v)
    }
    const headers = { Accept: 'application/json' }
    if (this.token) headers.Authorization = `Bearer ${this.token}`
    const res = await this.fetch(url, { headers })
    const body = await res.json().catch(() => ({}))
    if (!res.ok) {
      const msg = body && body.message ? body.message : `HTTP ${res.status}`
      throw new Error(`API ${path}: ${msg}`)
    }
    return body
  }

  // Ambil array dari berbagai bentuk response API.
  async list(path, params) {
    const body = await this.get(path, params)
    if (Array.isArray(body)) return body
    if (Array.isArray(body.data)) return body.data
    return []
  }

  statistik() { return this.get('kegiatan/statistik') }
  dashboardStats() { return this.get('dashboard/stats') }
  kegiatanTerbaru(limit = 10) { return this.list('kegiatan/all', { per_page: limit }) }
  kegiatan(id) { return this.get(`kegiatan/${id}`) }
  cariPeserta(nama) { return this.list('peserta', { search: nama, per_page: 10 }) }
  peserta(id) { return this.get(`peserta/${id}/kegiatan`) }
}

module.exports = { SimaikApi }
