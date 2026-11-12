'use strict'

// Bot Telegram SIMAIK — long polling tanpa dependensi.
const config = require('./config')
const { TelegramClient } = require('./telegram')
const { SimaikApi } = require('./api')
const { handleText, isAuthorized } = require('./handler')

function buildDeps(fetchImpl = globalThis.fetch) {
  return {
    api: new SimaikApi({
      apiBase: config.apiBase,
      apiToken: config.apiToken,
      fetchImpl,
    }),
  }
}

async function handleMessage(msg, tg, deps) {
  const chatId = msg.chat && msg.chat.id
  const text = msg.text || ''
  if (!chatId || !text) return

  const isCmd = text.trim().startsWith('/')
  if (isCmd && !isAuthorized(chatId, config.adminChatIds)) {
    if (/^\/(start|help)/i.test(text)) {
      await tg.sendMessage(chatId,
        `Halo! Anda terdaftar dengan chat ID: *${chatId}*\n` +
        'Minta admin menambahkan ID ini ke ADMIN_CHAT_IDS untuk memakai bot.')
    }
    return
  }

  try {
    const reply = await handleText(text, deps)
    if (reply) await tg.sendMessage(chatId, reply)
  } catch (err) {
    await tg.sendMessage(chatId, `⚠️ Terjadi kesalahan: ${err.message}`).catch(() => {})
  }
}

// Pemantau kegiatan baru -> notifikasi ke admin.
function startWatcher(tg, deps) {
  if (!config.watchIntervalSec) return null
  let lastSeenId = 0

  const tick = async () => {
    try {
      const list = await deps.api.kegiatanTerbaru(10)
      const ids = list.map(k => Number(k.id_kegiatan) || 0).filter(Boolean)
      const maxId = ids.length ? Math.max(...ids) : 0
      if (lastSeenId === 0) {
        lastSeenId = maxId // baseline pertama: jangan spam notifikasi lama
        return
      }
      if (maxId > lastSeenId) {
        const baru = list.filter(k => Number(k.id_kegiatan) > lastSeenId)
        for (const k of baru) {
          const teks = `🆕 *Kegiatan baru terdaftar!*\n\n${barisKegiatanSafe(k)}\n\nBuka aplikasi SIMAIK untuk detail.`
          for (const id of config.adminChatIds) {
            await tg.sendMessage(id, teks).catch(() => {})
          }
        }
        lastSeenId = maxId
      }
    } catch (_) {
      /* diamkan gangguan sementara, coba lagi di tick berikutnya */
    }
  }

  const timer = setInterval(tick, config.watchIntervalSec * 1000)
  tick()
  return timer
}

function barisKegiatanSafe(k) {
  return `📁 \\#${k.id_kegiatan} *${k.nama_kegiatan}*\n📅 ${k.tanggal_mulai || '-'} s.d. ${k.tanggal_selesai || '-'}`
}

async function main() {
  if (!config.telegramToken) {
    console.error('TELEGRAM_BOT_TOKEN belum diisi di bot/.env — bot tidak jalan.')
    process.exit(1)
  }
  const tg = new TelegramClient(config.telegramToken)
  const deps = buildDeps()
  console.log(`Bot SIMAIK berjalan. API: ${config.apiBase}`)
  startWatcher(tg, deps)

  for (;;) {
    try {
      const updates = await tg.getUpdates(config.pollTimeoutSec)
      for (const u of updates) {
        if (u.message) await handleMessage(u.message, tg, deps)
      }
    } catch (err) {
      console.error('Loop error:', err.message)
      await new Promise(r => setTimeout(r, 3000))
    }
  }
}

if (require.main === module) main()

module.exports = { handleMessage, buildDeps, startWatcher }
