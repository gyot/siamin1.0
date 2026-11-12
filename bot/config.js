'use strict'

// Konfigurasi bot dari bot/.env (lihat bot/.env.example)
const fs = require('fs')
const path = require('path')

function loadEnv() {
  const envPath = path.join(__dirname, '.env')
  const env = {}
  if (!fs.existsSync(envPath)) return env
  for (const line of fs.readFileSync(envPath, 'utf8').split('\n')) {
    const m = line.match(/^\s*([A-Z0-9_]+)\s*=\s*(.*)\s*$/)
    if (m) env[m[1]] = m[2].replace(/^["']|["']$/g, '')
  }
  return env
}

const env = loadEnv()

module.exports = {
  telegramToken: process.env.TELEGRAM_BOT_TOKEN || env.TELEGRAM_BOT_TOKEN || '',
  apiBase: (process.env.API_BASE || env.API_BASE || 'https://siamin.gdoank.my.id/api/v1').replace(/\/+$/, ''),
  apiToken: process.env.API_TOKEN || env.API_TOKEN || '',
  // Daftar chat ID Telegram yang diizinkan memakai bot (pisahkan koma).
  // Kosongkan = semua orang boleh (hanya untuk info agregat).
  adminChatIds: (process.env.ADMIN_CHAT_IDS || env.ADMIN_CHAT_IDS || '')
    .split(',').map(s => s.trim()).filter(Boolean),
  // Interval cek kegiatan baru (detik). 0 = mati.
  watchIntervalSec: Number(process.env.WATCH_INTERVAL_SEC || env.WATCH_INTERVAL_SEC || 0),
  pollTimeoutSec: 50,
}
