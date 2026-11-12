'use strict'

const { test } = require('node:test')
const assert = require('node:assert/strict')
const { TelegramClient, splitMessage } = require('../telegram')

test('splitMessage memotong di batas 4096', () => {
  const parts = splitMessage('x'.repeat(9000))
  assert.equal(parts.length, 3)
  assert.ok(parts.every(p => p.length <= 4096))
  assert.equal(parts.join(''), 'x'.repeat(9000))
})

test('splitMessage memotong di baris jika memungkinkan', () => {
  const text = 'a'.repeat(4000) + '\n' + 'b'.repeat(100)
  const parts = splitMessage(text)
  assert.equal(parts.length, 2)
  assert.ok(parts[0].endsWith('a'.repeat(1)))
})

test('TelegramClient.getUpdates menaikkan offset', async () => {
  const calls = []
  const fakeFetch = async (url, opts) => {
    calls.push({ url: String(url), body: JSON.parse(opts.body) })
    return {
      ok: true,
      json: async () => ({ ok: true, result: [{ update_id: 7, message: { chat: { id: 1 }, text: '/help' } }] }),
    }
  }
  const tg = new TelegramClient('TOKEN', fakeFetch)
  const updates = await tg.getUpdates(1)
  assert.equal(updates.length, 1)
  assert.equal(tg.offset, 8)
  assert.match(calls[0].url, /botTOKEN\/getUpdates/)
})

test('TelegramClient.sendMessage mengirim per bagian', async () => {
  const sent = []
  const fakeFetch = async (url, opts) => {
    sent.push(JSON.parse(opts.body))
    return { ok: true, json: async () => ({ ok: true, result: { message_id: sent.length } }) }
  }
  const tg = new TelegramClient('TOKEN', fakeFetch)
  await tg.sendMessage(123, 'x'.repeat(5000))
  assert.equal(sent.length, 2)
  assert.equal(sent[0].chat_id, 123)
})

test('TelegramClient melempar error saat API gagal', async () => {
  const fakeFetch = async () => ({ ok: true, json: async () => ({ ok: false, description: 'Bad Request' }) })
  const tg = new TelegramClient('TOKEN', fakeFetch)
  await assert.rejects(() => tg.sendMessage(1, 'x'), /Bad Request/)
})
