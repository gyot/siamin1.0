'use strict'

const { test } = require('node:test')
const assert = require('node:assert/strict')
const { handleText, isAuthorized, fmtTanggal } = require('../handler')

function mockApi(over = {}) {
  return {
    statistik: async () => ({ total_kegiatan: 60, total_peserta: 7434 }),
    dashboardStats: async () => ({ total_kegiatan: 60, total_kegiatan_berjalan: 2, total_peserta: 7434, total_sertifikat: 0 }),
    kegiatanTerbaru: async () => ([
      { id_kegiatan: 61, nama_kegiatan: 'Verifikasi Data Pokok', tanggal_mulai: '2026-05-21', tanggal_selesai: '2026-05-22', status: 'draft', total_peserta: 1191 },
      { id_kegiatan: 60, nama_kegiatan: 'Bimtek Kurikulum', tanggal_mulai: '2026-04-01', tanggal_selesai: '2026-04-03', status: 'selesai', total_peserta: 45 },
    ]),
    kegiatan: async (id) => ({ data: { id_kegiatan: Number(id), nama_kegiatan: 'Verifikasi Data Pokok', tanggal_mulai: '2026-05-21', tanggal_selesai: '2026-05-22', status: 'draft', total_peserta: 1191, metode_pelaksanaan: 'daring' } }),
    cariPeserta: async (q) => ([
      { id_peserta: 6, nama_lengkap: 'Siti Aminah', nip: '198001', nama_instansi: 'SMPN 1 Mataram', kab_kota: 'Mataram', peran: 'Peserta' },
    ]),
    peserta: async (id) => ({ data: { id_peserta: Number(id), nama_lengkap: 'Siti Aminah', nama_instansi: 'SMPN 1 Mataram', kegiatan: [{ id_kegiatan: 61, nama_kegiatan: 'Verifikasi Data Pokok', tanggal_mulai: '2026-05-21', tanggal_selesai: '2026-05-22', status: 'draft' }] } }),
    ...over,
  }
}

test('/help menampilkan daftar perintah', async () => {
  const out = await handleText('/help', { api: mockApi() })
  assert.match(out, /Bot SIMAIK/)
  assert.match(out, /\/rekap/)
})

test('/rekap merangkum statistik', async () => {
  const out = await handleText('/rekap', { api: mockApi() })
  assert.match(out, /Kegiatan: 60/)
  assert.match(out, /Peserta: 7434/)
  assert.match(out, /Berjalan: 2/)
})

test('/kegiatan menampilkan daftar terbaru', async () => {
  const out = await handleText('/kegiatan', { api: mockApi() })
  assert.match(out, /Verifikasi Data Pokok/)
  assert.match(out, /Bimtek Kurikulum/)
  assert.match(out, /#61/)
})

test('/kegiatan <id> menampilkan detail', async () => {
  const out = await handleText('/kegiatan 61', { api: mockApi() })
  assert.match(out, /Verifikasi Data Pokok/)
  assert.match(out, /#61/)
  assert.match(out, /1\.191|1191/)
})

test('/kegiatan dengan argumen salah memberi format', async () => {
  const out = await handleText('/kegiatan abc', { api: mockApi() })
  assert.match(out, /Format/)
})

test('/cari mencari peserta', async () => {
  const out = await handleText('/cari siti', { api: mockApi() })
  assert.match(out, /Siti Aminah/)
  assert.match(out, /SMPN 1 Mataram/)
})

test('/cari tanpa argumen memberi contoh', async () => {
  const out = await handleText('/cari', { api: mockApi() })
  assert.match(out, /Format: \/cari/)
})

test('/cari yang tidak ditemukan memberi pesan', async () => {
  const api = mockApi({ cariPeserta: async () => [] })
  const out = await handleText('/cari zzz', { api })
  assert.match(out, /tidak ditemukan/)
})

test('/peserta menampilkan detail + kegiatan', async () => {
  const out = await handleText('/peserta 6', { api: mockApi() })
  assert.match(out, /Siti Aminah/)
  assert.match(out, /Kegiatan yang diikuti/)
  assert.match(out, /Verifikasi Data Pokok/)
})

test('/jadwal menampilkan kegiatan yang sedang berjalan', async () => {
  const d = new Date()
  const kini = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
  const api = mockApi({
    kegiatanTerbaru: async () => ([
      { id_kegiatan: 1, nama_kegiatan: 'Sedang Berjalan', tanggal_mulai: kini, tanggal_selesai: kini, status: 'berjalan' },
      { id_kegiatan: 2, nama_kegiatan: 'Selesai Dulu', tanggal_mulai: '2020-01-01', tanggal_selesai: '2020-01-02', status: 'selesai' },
    ]),
  })
  const out = await handleText('/jadwal', { api })
  assert.match(out, /Sedang Berjalan/)
  assert.doesNotMatch(out, /Selesai Dulu/)
})

test('perintah tak dikenal memberi bantuan', async () => {
  const out = await handleText('/apaini', { api: mockApi() })
  assert.match(out, /tidak dikenal/)
  assert.match(out, /\/rekap/)
})

test('pesan biasa diabaikan', async () => {
  assert.equal(await handleText('halo bot', { api: mockApi() }), null)
})

test('isAuthorized: whitelist kosong = semua boleh', () => {
  assert.equal(isAuthorized('123', []), true)
  assert.equal(isAuthorized('123', ['123']), true)
  assert.equal(isAuthorized('999', ['123']), false)
})

test('fmtTanggal aman untuk input aneh', () => {
  assert.equal(fmtTanggal(null), '-')
  assert.equal(fmtTanggal('bukan-tanggal'), 'bukan-tang')
})
