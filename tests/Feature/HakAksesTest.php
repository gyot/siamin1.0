<?php

namespace Tests\Feature;

use App\Models\Pegawai;
use App\Models\User;
use App\Models\UserAksesMenu;
use App\Models\UserPeran;
use App\Support\HakAkses;
use Database\Seeders\SuperAdminSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Fitur Hak Akses Menu (user privilege):
 * - skema tabel user_peran & user_akses_menu
 * - seeder super admin (lookup via NIP, skip aman bila user belum ada)
 * - aturan menu DEFAULT untuk user tanpa konfigurasi
 * - endpoint GET /hak-akses/saya, GET /hak-akses, PUT /hak-akses/{id_user}
 */
class HakAksesTest extends TestCase
{
    use RefreshDatabase;

    private static int $nomor = 0;

    /** Buat user (+pegawai opsional untuk NIP/nama seperti view KANDI). */
    private function buatUser(array $overrides = [], ?string $nip = null, ?string $nama = null): User
    {
        self::$nomor++;
        $pegawai = null;
        if ($nip !== null || $nama !== null) {
            $pegawai = Pegawai::forceCreate([
                'nip' => $nip,
                'nama' => $nama ?? ('Pegawai ' . self::$nomor),
            ]);
        }

        return User::forceCreate(array_merge([
            'email' => 'u' . self::$nomor . '@contoh.test',
            'user' => 'u' . self::$nomor,
            'password' => 'rahasia',
            'role' => 'admin',
            'status' => 'aktif',
            'id_pegawai' => $pegawai?->id_pegawai,
        ], $overrides));
    }

    private function jadikanSuperAdmin(User $user): void
    {
        UserPeran::forceCreate(['id_user' => $user->id_user, 'peran' => 'super_admin']);
    }

    // ===================== SKEMA =====================

    public function test_skema_tabel_user_peran(): void
    {
        $this->assertTrue(Schema::hasTable('user_peran'));

        foreach (['id_user', 'peran', 'created_at', 'updated_at'] as $kolom) {
            $this->assertTrue(Schema::hasColumn('user_peran', $kolom), "Kolom user_peran.{$kolom} tidak ditemukan.");
        }
    }

    public function test_skema_tabel_user_akses_menu(): void
    {
        $this->assertTrue(Schema::hasTable('user_akses_menu'));

        foreach (['id', 'id_user', 'menu', 'created_at', 'updated_at'] as $kolom) {
            $this->assertTrue(Schema::hasColumn('user_akses_menu', $kolom), "Kolom user_akses_menu.{$kolom} tidak ditemukan.");
        }

        $this->assertTrue(
            Schema::hasIndex('user_akses_menu', ['id_user', 'menu']),
            'Index unik gabungan id_user + menu tidak ditemukan.',
        );

        // Unik gabungan id_user + menu: menu sama tidak boleh dobel untuk satu user.
        UserAksesMenu::forceCreate(['id_user' => 5, 'menu' => 'kegiatan']);
        $this->expectException(QueryException::class);
        UserAksesMenu::forceCreate(['id_user' => 5, 'menu' => 'kegiatan']);
    }

    // ===================== SEEDER SUPER ADMIN =====================

    public function test_seeder_mengubah_user_dengan_nip_tersebut_menjadi_super_admin(): void
    {
        $user = $this->buatUser(['email' => 'super@contoh.test', 'user' => 'super'], HakAkses::NIP_SUPER_ADMIN, 'Wahyu');
        $lain = $this->buatUser(['email' => 'lain@contoh.test', 'user' => 'lain'], '199001012020121001', 'Budi');

        (new SuperAdminSeeder())->run();

        $this->assertDatabaseHas('user_peran', ['id_user' => $user->id_user, 'peran' => 'super_admin']);
        $this->assertDatabaseMissing('user_peran', ['id_user' => $lain->id_user]);
        $this->assertTrue(HakAkses::isSuperAdmin($user));
        $this->assertFalse(HakAkses::isSuperAdmin($lain));
    }

    public function test_seeder_lookup_langsung_lewat_kolom_users_nip(): void
    {
        // Jalur view produksi: `users` punya kolom nip sendiri.
        Schema::table('users', function ($table) {
            $table->string('nip', 30)->nullable();
        });

        $user = User::forceCreate([
            'email' => 'langsung@contoh.test',
            'user' => 'langsung',
            'password' => 'rahasia',
            'role' => 'admin',
            'status' => 'aktif',
            'nip' => HakAkses::NIP_SUPER_ADMIN,
        ]);

        (new SuperAdminSeeder())->run();

        $this->assertDatabaseHas('user_peran', ['id_user' => $user->id_user, 'peran' => 'super_admin']);
    }

    public function test_seeder_dilewati_dengan_aman_ketika_user_belum_ada(): void
    {
        // Tidak ada user dengan NIP tersebut (view KANDI belum memuatnya).
        (new SuperAdminSeeder())->run();
        (new SuperAdminSeeder())->run(); // idempoten, tetap tidak error

        $this->assertSame(0, UserPeran::count());
    }

    // ===================== ATURAN MENU DEFAULT =====================

    public function test_user_tanpa_konfigurasi_mendapat_menu_default(): void
    {
        $user = $this->buatUser();

        $this->actingAs($user, 'sanctum');
        $response = $this->getJson('/api/v1/hak-akses/saya');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.super_admin', false)
            ->assertJsonPath('data.menus', HakAkses::DEFAULT_MENUS);
    }

    public function test_user_dengan_konfigurasi_mendapat_menu_yang_disimpan(): void
    {
        $user = $this->buatUser();
        UserAksesMenu::forceCreate(['id_user' => $user->id_user, 'menu' => 'daftar-peserta']);
        UserAksesMenu::forceCreate(['id_user' => $user->id_user, 'menu' => 'paket-soal']);

        $this->actingAs($user, 'sanctum');
        $response = $this->getJson('/api/v1/hak-akses/saya');

        $response->assertStatus(200)
            ->assertJsonPath('data.super_admin', false)
            ->assertJsonPath('data.menus', ['daftar-peserta', 'paket-soal']);
    }

    public function test_super_admin_mendapat_semua_menu(): void
    {
        $user = $this->buatUser();
        $this->jadikanSuperAdmin($user);

        $this->actingAs($user, 'sanctum');
        $response = $this->getJson('/api/v1/hak-akses/saya');

        $response->assertStatus(200)
            ->assertJsonPath('data.super_admin', true)
            ->assertJsonPath('data.menus', HakAkses::MENU_KEYS);
    }

    public function test_hak_akses_saya_membutuhkan_login(): void
    {
        $this->getJson('/api/v1/hak-akses/saya')->assertStatus(401);
    }

    // ===================== ENDPOINT: DAFTAR (GET /hak-akses) =====================

    public function test_daftar_hak_akses_hanya_super_admin(): void
    {
        $this->getJson('/api/v1/hak-akses')->assertStatus(401);

        // User biasa — bahkan yang punya menu hak-akses — tetap ditolak.
        $user = $this->buatUser();
        UserAksesMenu::forceCreate(['id_user' => $user->id_user, 'menu' => 'hak-akses']);
        $this->actingAs($user, 'sanctum');
        $this->getJson('/api/v1/hak-akses')->assertStatus(403);
    }

    public function test_daftar_hak_akses_mengembalikan_semua_user(): void
    {
        $super = $this->buatUser(['email' => 'sa@contoh.test', 'user' => 'sa']);
        $this->jadikanSuperAdmin($super);

        $biasa = $this->buatUser(['email' => 'biasa@contoh.test', 'user' => 'biasa'], '199001012020121002', 'Budi Santoso');
        UserAksesMenu::forceCreate(['id_user' => $biasa->id_user, 'menu' => 'kegiatan']);
        UserAksesMenu::forceCreate(['id_user' => $biasa->id_user, 'menu' => 'penugasan']);

        $tanpaKonfig = $this->buatUser(['email' => 'baru@contoh.test', 'user' => 'baru'], '199001012020121003', 'Citra');

        $this->actingAs($super, 'sanctum');
        $response = $this->getJson('/api/v1/hak-akses');

        $response->assertStatus(200)->assertJsonPath('success', true);
        $data = collect($response->json('data'))->keyBy('id_user');

        $this->assertCount(3, $data);

        $this->assertSame('Budi Santoso', $data[$biasa->id_user]['nama']);
        $this->assertSame('199001012020121002', $data[$biasa->id_user]['nip']);
        $this->assertNull($data[$biasa->id_user]['peran']);
        $this->assertSame(['kegiatan', 'penugasan'], $data[$biasa->id_user]['menus']);
        $this->assertFalse($data[$biasa->id_user]['default']);

        // Tanpa konfigurasi -> menu DEFAULT, default = true.
        $this->assertSame(HakAkses::DEFAULT_MENUS, $data[$tanpaKonfig->id_user]['menus']);
        $this->assertTrue($data[$tanpaKonfig->id_user]['default']);

        // Super admin -> semua menu, default = false.
        $this->assertSame('super_admin', $data[$super->id_user]['peran']);
        $this->assertSame(HakAkses::MENU_KEYS, $data[$super->id_user]['menus']);
        $this->assertFalse($data[$super->id_user]['default']);
    }

    // ===================== ENDPOINT: UBAH (PUT /hak-akses/{id_user}) =====================

    public function test_update_hak_akses_mengganti_menu_dan_peran(): void
    {
        $super = $this->buatUser();
        $this->jadikanSuperAdmin($super);
        $target = $this->buatUser();
        UserAksesMenu::forceCreate(['id_user' => $target->id_user, 'menu' => 'lama']);

        $this->actingAs($super, 'sanctum');
        $response = $this->putJson('/api/v1/hak-akses/' . $target->id_user, [
            'menus' => ['kegiatan', 'daftar-peserta'],
            'peran' => 'admin',
        ]);

        $response->assertStatus(200)->assertJsonPath('success', true);

        // Menu lama DI-REPLACE seluruhnya; peran tersimpan di user_peran.
        $this->assertDatabaseMissing('user_akses_menu', ['id_user' => $target->id_user, 'menu' => 'lama']);
        $this->assertDatabaseHas('user_akses_menu', ['id_user' => $target->id_user, 'menu' => 'kegiatan']);
        $this->assertDatabaseHas('user_akses_menu', ['id_user' => $target->id_user, 'menu' => 'daftar-peserta']);
        $this->assertSame(2, UserAksesMenu::where('id_user', $target->id_user)->count());
        $this->assertDatabaseHas('user_peran', ['id_user' => $target->id_user, 'peran' => 'admin']);

        $this->assertSame(['kegiatan', 'daftar-peserta'], $response->json('data.menus'));
        $this->assertSame('admin', $response->json('data.peran'));
    }

    public function test_update_tanpa_peran_mempertahankan_peran_lama(): void
    {
        $super = $this->buatUser();
        $this->jadikanSuperAdmin($super);
        $target = $this->buatUser();
        UserPeran::forceCreate(['id_user' => $target->id_user, 'peran' => 'admin']);

        $this->actingAs($super, 'sanctum');
        $this->putJson('/api/v1/hak-akses/' . $target->id_user, ['menus' => ['kegiatan']])
            ->assertStatus(200);

        $this->assertDatabaseHas('user_peran', ['id_user' => $target->id_user, 'peran' => 'admin']);
    }

    public function test_update_menu_kosong_mengembalikan_ke_menu_default(): void
    {
        $super = $this->buatUser();
        $this->jadikanSuperAdmin($super);
        $target = $this->buatUser();
        UserAksesMenu::forceCreate(['id_user' => $target->id_user, 'menu' => 'kegiatan']);

        $this->actingAs($super, 'sanctum');
        $this->putJson('/api/v1/hak-akses/' . $target->id_user, ['menus' => []])
            ->assertStatus(200);

        $this->assertSame(0, UserAksesMenu::where('id_user', $target->id_user)->count());

        // Tanpa baris konfigurasi = menu DEFAULT lagi.
        $this->actingAs($target, 'sanctum');
        $this->getJson('/api/v1/hak-akses/saya')
            ->assertJsonPath('data.menus', HakAkses::DEFAULT_MENUS);
    }

    public function test_update_hak_akses_validasi_dan_user_tidak_dikenal(): void
    {
        $super = $this->buatUser();
        $this->jadikanSuperAdmin($super);
        $this->actingAs($super, 'sanctum');

        // menus wajib array.
        $this->putJson('/api/v1/hak-akses/1', ['peran' => 'admin'])->assertStatus(422);
        $this->putJson('/api/v1/hak-akses/1', ['menus' => 'kegiatan'])->assertStatus(422);
        // peran hanya admin / super_admin.
        $this->putJson('/api/v1/hak-akses/1', ['menus' => [], 'peran' => 'operator'])->assertStatus(422);
        // User tidak ada di view users -> 404.
        $this->putJson('/api/v1/hak-akses/99999', ['menus' => ['kegiatan']])->assertStatus(404);
    }

    public function test_update_hak_akses_ditolak_untuk_non_super_admin(): void
    {
        $super = $this->buatUser();
        $this->jadikanSuperAdmin($super);
        $target = $this->buatUser();
        $bukanSuper = $this->buatUser();
        UserAksesMenu::forceCreate(['id_user' => $bukanSuper->id_user, 'menu' => 'hak-akses']);

        $this->actingAs($bukanSuper, 'sanctum');
        $this->putJson('/api/v1/hak-akses/' . $target->id_user, ['menus' => ['kegiatan']])
            ->assertStatus(403);

        $this->assertSame(0, UserAksesMenu::where('id_user', $target->id_user)->count());
    }
}
