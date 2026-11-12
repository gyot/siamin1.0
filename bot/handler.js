'use strict'

// Logika perintah bot SIMAIK.

const BANTUAN = `🤖 *Bot SIMAIK* — asisten aplikasi SIMAIK

Perintah:
/rekap — ringkasan statistik aplikasi
/kegiatan — daftar kegiatan terbaru
/kegiatan \\<id\\> — detail satu kegiatan
/cari \\<nama\\> — cari peserta (contoh: /cari siti)
/peserta \\<id\\> — detail peserta & kegiatannya
/jadwal — kegiatan yang sedang berjalan
/help — bantuan ini

Kelola data tetap melalui aplikasi SIMAIK.`

const STATUS_ICON = {
  draft: '📝',
  berjalan: '🟢',
  selesai: '✅',
  dibatalkan: '❌',
}

function fmtTanggal(v) {
  if (!v) return '-'
  const d = new Date(v)
  if (Number.isNaN(d.getTime())) return String(v).slice(0, 10)
  return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}

function fmtRentang(a, b) {
  if (!a && !b) return '-'
  return `${fmtTanggal(a)} s.d. ${fmtTanggal(b)}`
}

function isAuthorized(chatId, adminChatIds) {
  if (!adminChatIds || adminChatIds.length === 0) return true
  return adminChatIds.includes(String(chatId))
}

function barisKegiatan(k) {
  const icon = STATUS_ICON[k.status] || '•'
  const peserta = k.total_peserta ? ` | 👥 ${k.total_peserta}` : ''
  return `${icon} \\#${k.id_kegiatan} *${k.nama_kegiatan}*\n     ${fmtRentang(k.tanggal_mulai, k.tanggal_selesai)}${peserta}`
}

function formatDetailKegiatan(k) {
  const lines = [
    `📁 *${k.nama_kegiatan || 'Kegiatan'}* \\(\\#${k.id_kegiatan}\\)`,
    `📅 ${fmtRentang(k.tanggal_mulai, k.tanggal_selesai)}`,
    `📍 ${k.lokasi || k.metode_pelaksanaan || '-'} | ${STATUS_ICON[k.status] || ''} ${k.status || '-'}`,
  ]
  if (k.deskripsi) lines.push('', String(k.deskripsi).slice(0, 500))
  if (k.total_peserta) lines.push('', `👥 Total peserta: ${k.total_peserta}`)
  return lines.join('\n')
}

function formatPeserta(p) {
  const lines = [`👤 *${p.nama_lengkap || '-'}* \\(\\#${p.id_peserta}\\)`]
  if (p.nip) lines.push(`NIP: ${p.nip}`)
  if (p.nama_instansi) lines.push(`🏢 ${p.nama_instansi}`)
  if (p.kab_kota) lines.push(`📍 ${p.kab_kota}${p.provinsi ? ', ' + p.provinsi : ''}`)
  if (p.peran) lines.push(`Peran: ${p.peran}`)
  return lines.join('\n')
}

// Bangun balasan untuk satu pesan chat.
// deps: { api } — objek SimaikApi (atau mock pada test).
async function handleText(text, deps) {
  const raw = String(text || '').trim()
  if (!raw || !raw.startsWith('/')) return null
  const [cmdRaw, ...rest] = raw.split(/\s+/)
  const cmd = cmdRaw.replace(/@.*$/, '').toLowerCase()
  const arg = rest.join(' ').trim()
  const api = deps.api

  switch (cmd) {
    case '/start':
    case '/help':
      return BANTUAN

    case '/rekap':
    case '/statistik': {
      const [stat, dash] = await Promise.all([
        api.statistik(),
        api.dashboardStats().catch(() => ({})),
      ])
      const s = stat || {}
      const d = dash || {}
      return [
        '📊 *Rekap SIMAIK*',
        `📁 Kegiatan: ${d.total_kegiatan ?? s.total_kegiatan ?? '-'}`,
        `🟢 Berjalan: ${d.total_kegiatan_berjalan ?? '-'}`,
        `👥 Peserta: ${d.total_peserta ?? '-'}`,
        `📜 Sertifikat: ${d.total_sertifikat ?? '-'}`,
      ].join('\n')
    }

    case '/kegiatan': {
      if (arg) {
        if (!/^\d+$/.test(arg)) return 'Format: /kegiatan \\<id\\> atau /kegiatan untuk daftar.'
        const k = await api.kegiatan(arg)
        return formatDetailKegiatan(k.data || k)
      }
      const list = await api.kegiatanTerbaru(10)
      if (!list.length) return 'Belum ada kegiatan.'
      return ['🗂️ *Kegiatan Terbaru*', '', ...list.map(barisKegiatan)].join('\n')
    }

    case '/cari': {
      if (!arg) return 'Format: /cari \\<nama\\>\nContoh: /cari siti'
      const hasil = await api.cariPeserta(arg)
      if (!hasil.length) return `Peserta dengan nama "*${arg}*" tidak ditemukan.`
      return [
        `🔍 Hasil pencarian "*${arg}*" \\(${hasil.length}\\)`,
        '',
        ...hasil.map(formatPeserta),
      ].join('\n\n')
    }

    case '/peserta': {
      if (!/^\d+$/.test(arg)) return 'Format: /peserta \\<id\\>'
      const body = await api.peserta(arg)
      const p = body.data || body
      const kep = Array.isArray(p.kegiatan) ? p.kegiatan : []
      const detail = formatPeserta(p)
      if (!kep.length) return detail + '\n\nBelum terdaftar di kegiatan mana pun.'
      return [
        detail,
        '',
        '*Kegiatan yang diikuti:*',
        ...kep.map(barisKegiatan),
      ].join('\n')
    }

    case '/jadwal': {
      const list = await api.kegiatanTerbaru(50)
      // Bandingkan per tanggal (YYYY-MM-DD), bukan timestamp —
      // tanggal_selesai adalah tengah malam yang selalu "lewat" pada hari-H.
      const d = new Date()
      const kini = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
      const berjalan = list.filter(k => {
        const mulai = String(k.tanggal_mulai || '').slice(0, 10)
        const selesai = String(k.tanggal_selesai || '').slice(0, 10)
        if (!mulai) return false
        return mulai <= kini && (!selesai || selesai >= kini)
      })
      if (!berjalan.length) return 'Tidak ada kegiatan yang sedang berjalan saat ini.'
      return ['🗓️ *Kegiatan Berjalan*', '', ...berjalan.map(barisKegiatan)].join('\n')
    }

    default:
      return `Perintah tidak dikenal: ${cmd}\n\n${BANTUAN}`
  }
}

module.exports = { handleText, isAuthorized, BANTUAN, fmtTanggal, barisKegiatan }
