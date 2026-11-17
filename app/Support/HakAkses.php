<?php

namespace App\Support;

use App\Models\User;
use App\Models\UserAksesMenu;
use App\Models\UserPeran;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Hak akses menu (user privilege) — sumber tunggal aturan akses SIMAIK.
 *
 * - Data disimpan di tabel milik SIMAIK (user_peran, user_akses_menu),
 *   TIDAK pernah menambah kolom ke `users` (view baca-saja milik KANDI).
 * - `users` dan `pegawai` adalah VIEW baca-saja di produksi: tidak ada
 *   foreign key ke dua tabel itu dari tabel hak akses.
 * - User TANPA konfigurasi mendapat menu DEFAULT; super admin lolos semua.
 */
class HakAkses
{
    /** NIP user yang dijadikan super admin oleh seeder. */
    public const NIP_SUPER_ADMIN = '199502102025211047';

    /** Semua kunci menu yang dikenal aplikasi (sama dengan MENU_OPTIONS di siamin-ui). */
    public const MENU_KEYS = [
        'dashboard',
        'kegiatan',
        'pengajuan-sertifikat',
        'proses-sertifikat',
        'pegawai-user',
        'daftar-sekolah',
        'daftar-peserta',
        'penugasan',
        'paket-soal',
        'laporan-kegiatan',
        'api-token',
        'hak-akses',
    ];

    /** Menu untuk user yang belum dikonfigurasikan hak aksesnya. */
    public const DEFAULT_MENUS = [
        'dashboard',
        'kegiatan',
        'pegawai-user',
        'penugasan',
        'laporan-kegiatan',
    ];

    /**
     * id_user dari objek yang sedang login, atau null bila bukan akun tabel
     * users (mis. AkunPeserta yang tidak punya kolom id_user).
     */
    public static function idUser($user): ?int
    {
        $id = $user?->getAttribute('id_user');

        return $id === null ? null : (int) $id;
    }

    /** Apakah user ini super admin (baris di user_peran)? */
    public static function isSuperAdmin($user): bool
    {
        $id = static::idUser($user);

        if ($id === null) {
            return false;
        }

        return UserPeran::query()
            ->where('id_user', $id)
            ->where('peran', 'super_admin')
            ->exists();
    }

    /**
     * Daftar menu efektif user: semua menu untuk super admin, menu yang
     * dikonfigurasi bila ada baris user_akses_menu, selain itu menu DEFAULT.
     *
     * @return list<string>
     */
    public static function menusFor($user): array
    {
        $id = static::idUser($user);

        if ($id !== null && static::isSuperAdmin($user)) {
            return static::MENU_KEYS;
        }

        $menus = $id === null
            ? []
            : UserAksesMenu::query()->where('id_user', $id)->orderBy('id')->pluck('menu')->all();

        return $menus !== [] ? array_values(array_unique($menus)) : static::DEFAULT_MENUS;
    }

    /** Apakah user berhak atas minimal satu dari kunci menu yang diberikan? */
    public static function punyaMenu($user, string ...$menus): bool
    {
        if ($user === null) {
            return false;
        }

        if (static::isSuperAdmin($user)) {
            return true;
        }

        return array_intersect($menus, static::menusFor($user)) !== [];
    }

    /**
     * Cari user berdasarkan NIP. Kolom `users.nip` dipakai bila ada (view
     * produksi); kalau tidak ada, dicocokkan lewat nip pegawai terkait.
     */
    public static function cariUserByNip(string $nip): ?User
    {
        if (Schema::hasColumn('users', 'nip')) {
            $user = User::query()->where('nip', $nip)->first();
            if ($user !== null) {
                return $user;
            }
        }

        return User::query()
            ->whereHas('pegawai', fn ($q) => $q->where('nip', $nip))
            ->first();
    }

    /**
     * Jadikan user dengan NIP tertentu sebagai super admin (idempoten).
     * Aman dilewati bila user belum ada di tabel users (view KANDI).
     */
    public static function seedSuperAdmin(): ?UserPeran
    {
        $user = static::cariUserByNip(static::NIP_SUPER_ADMIN);

        if ($user === null) {
            return null;
        }

        return UserPeran::query()->updateOrCreate(
            ['id_user' => static::idUser($user)],
            ['peran' => 'super_admin'],
        );
    }

    /**
     * Ganti SELURUH menu user (replace). Array kosong = kembali ke menu
     * DEFAULT (tidak ada baris konfigurasi).
     *
     * @param  list<string>  $menus
     */
    public static function replaceMenus(int $idUser, array $menus): void
    {
        DB::transaction(function () use ($idUser, $menus) {
            UserAksesMenu::query()->where('id_user', $idUser)->delete();

            foreach (array_values(array_unique($menus)) as $menu) {
                UserAksesMenu::query()->create(['id_user' => $idUser, 'menu' => $menu]);
            }
        });
    }
}
