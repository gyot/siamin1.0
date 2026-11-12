'use strict'

// Klien Telegram Bot API (long polling) tanpa dependensi.
const API = 'https://api.telegram.org'

function splitMessage(text, limit = 4096) {
  const parts = []
  let rest = String(text ?? '')
  while (rest.length > limit) {
    let cut = rest.lastIndexOf('\n', limit)
    if (cut < limit * 0.5) cut = limit
    parts.push(rest.slice(0, cut))
    rest = rest.slice(cut).replace(/^\n/, '')
  }
  if (rest) parts.push(rest)
  return parts.length ? parts : ['']
}

class TelegramClient {
  constructor(token, fetchImpl = globalThis.fetch) {
    this.token = token
    this.fetch = fetchImpl
    this.offset = 0
  }

  async call(method, params = {}) {
    const res = await this.fetch(`${API}/bot${this.token}/${method}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(params),
    })
    const body = await res.json()
    if (!body.ok) throw new Error(`Telegram ${method} gagal: ${body.description}`)
    return body.result
  }

  async getUpdates(timeout = 50) {
    const result = await this.call('getUpdates', {
      offset: this.offset,
      timeout,
      allowed_updates: ['message'],
    })
    for (const u of result) this.offset = u.update_id + 1
    return result
  }

  async sendMessage(chatId, text) {
    const sent = []
    for (const part of splitMessage(text)) {
      sent.push(await this.call('sendMessage', { chat_id: chatId, text: part }))
    }
    return sent
  }
}

module.exports = { TelegramClient, splitMessage }
