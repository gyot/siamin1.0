<?php

namespace Tests\Feature;

use App\Http\Middleware\AksesMenu;
use App\Models\User;
use App\Models\UserAksesMenu;
use App\Models\UserPeran;
use App\Support\HakAkses;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Proteksi API per menu (middleware AksesMenu) — peta route -> kunci menu
 * di routes/api.php. Untuk tiap menu:
 * - user TANPA hak menu itu -> 403 JSON
 * - user DENGAN hak menu itu -> lolos (200)
 * - super admin -> lolos semua
 * Route publik tidak disentuh (diuji terpisah oleh test yang lama).
 */
class AksesMenuRouteProtectionTest extends TestCase
{
    use RefreshDatabase;

    private static int $nomor = 0;

    protected function setUp(): void
    {
        parent::setUp();

        // `daftar_sekolah` di produksi adalah VIEW MySQL (GROUP_CONCAT ... SEPARATOR)
        // yang tidak dibuat di SQLite. Test ini TIDAK bergantung pada view itu:
        // sediakan tabel + satu baris data minimal yang bisa dibuat di SQLite,
        // lalu probe GET /api/v1/sekolah benar-benar jalan sampai 200.
        if (DB::getDriverName() !== 'mysql' && ! Schema::hasTable('daftar_sekolah')) {
            Schema::create('daftar_sekolah', function (Blueprint $table) {
                $table->string('npsn')->nullable();
                $table->string('nama_sekolah')->nullable();
                $table->string('kab_kota')->nullable();
                $table->string('provinsi')->nullable();
                $table->integer('jumlah_peserta')->default(0);
                $table->integer('jumlah_kegiatan')->default(0);
            });
        }

        DB::table('daftar_sekolah')->insert([
            'npsn' => '50200001',
            'nama_sekolah' => 'SMA Contoh SQLite',
            'kab_kota' => 'Mataram',
            'provinsi' => 'NTB',
            'jumlah_peserta' => 1,
            'jumlah_kegiatan' => 1,
        ]);
    }

    /**
     * Probe per kunci menu: [menu, method, uri, payload].
     * Semua dipilih agar hasil sukses = 200 (list/kosong).
     *
     * @return array<string, array{0: string, 1: string, 2: string, 3: ?array}>
     */
    public static function routeProvider(): array
    {
        return [
            'kegiatan' => ['kegiatan', 'GET', '/api/v1/kegiatan/tim-saya', null],
            'pengajuan-sertifikat' => ['pengajuan-sertifikat', 'GET', '/api/v1/sertifikat-batch/pengajuan', null],
            'proses-sertifikat' => ['proses-sertifikat', 'GET', '/api/v1/sertifikat-batch/proses', null],
            'pegawai-user' => ['pegawai-user', 'GET', '/api/v1/users', null],
            'daftar-sekolah' => ['daftar-sekolah', 'GET', '/api/v1/sekolah', null],
            'daftar-peserta' => ['daftar-peserta', 'GET', '/api/v1/peserta-all', null],
            'penugasan' => ['penugasan', 'GET', '/api/v1/penugasan-pegawai', null],
            'paket-soal' => ['paket-soal', 'GET', '/api/v1/test/paket-all', null],
            'laporan-kegiatan' => ['laporan-kegiatan', 'GET', '/api/v1/laporan/1/draft', null],
            'api-token' => ['api-token', 'GET', '/api/v1/api-tokens', null],
            'hak-akses' => ['hak-akses', 'PUT', '/api/v1/hak-akses/1', ['menus' => ['kegiatan']]],
        ];
    }

    /** User dengan role admin (agar gerbang role lama tidak ikut menghalangi). */
    private function buatUser(string $email): User
    {
        self::$nomor++;

        return User::forceCreate([
            'email' => $email . self::$nomor . '@contoh.test',
            'user' => $email . self::$nomor,
            'password' => 'rahasia',
            'role' => 'admin',
            'status' => 'aktif',
        ]);
    }

    /**
     * Kirim request probe. Return status code.
     */
    private function kirim(string $method, string $uri, ?array $payload): int
    {
        $response = match ($method) {
            'GET' => $this->getJson($uri),
            'PUT' => $this->putJson($uri, $payload ?? []),
            'POST' => $this->postJson($uri, $payload ?? []),
            'PATCH' => $this->patchJson($uri, $payload ?? []),
            'DELETE' => $this->deleteJson($uri),
        };

        return $response->getStatusCode();
    }

    /**
     * @dataProvider routeProvider
     */
    public function test_route_ditolak_403_tanpa_hak_menu(string $menu, string $method, string $uri, ?array $payload): void
    {
        $user = $this->buatUser('tanpa');
        // Konfigurasi eksplisit TANPA menu yang diuji (menu default tidak berlaku
        // lagi begitu ada baris konfigurasi). 'dashboard' tidak punya route probe.
        UserAksesMenu::forceCreate(['id_user' => $user->id_user, 'menu' => 'dashboard']);
        $this->actingAs($user, 'sanctum');

        $this->assertSame(403, $this->kirim($method, $uri, $payload), "Menu {$menu}: seharusnya 403 tanpa hak.");
    }

    /**
     * @dataProvider routeProvider
     */
    public function test_route_dilewati_200_dengan_hak_menu(string $menu, string $method, string $uri, ?array $payload): void
    {
        // User pertama = target PUT /hak-akses/1 pada probe (selalu ada).
        $this->buatUser('target');
        $user = $this->buatUser('punya');
        UserAksesMenu::forceCreate(['id_user' => $user->id_user, 'menu' => $menu]);

        if ($menu === 'hak-akses') {
            // Gerbang tambahan controller: kelola hak akses khusus super admin.
            UserPeran::forceCreate(['id_user' => $user->id_user, 'peran' => 'super_admin']);
        }

        $this->actingAs($user, 'sanctum');

        $this->assertSame(200, $this->kirim($method, $uri, $payload), "Menu {$menu}: seharusnya 200 dengan hak.");
    }

    /**
     * @dataProvider routeProvider
     */
    public function test_super_admin_lolos_semua_route(string $menu, string $method, string $uri, ?array $payload): void
    {
        // User pertama = target PUT /hak-akses/1 pada probe (selalu ada).
        $this->buatUser('target');
        $user = $this->buatUser('super');
        UserPeran::forceCreate(['id_user' => $user->id_user, 'peran' => 'super_admin']);
        $this->actingAs($user, 'sanctum');

        $this->assertSame(200, $this->kirim($method, $uri, $payload), "Menu {$menu}: super admin seharusnya lolos.");
    }

    /**
     * Kunci 'dashboard' didukung middleware (menu default), tetapi route
     * dashboard/stats sengaja dibiarkan PUBLIK sehingga tidak ada route yang
     * diproteksi kunci ini — perilaku middleware diuji langsung.
     */
    public function test_kunci_dashboard_didukung_middleware(): void
    {
        $middleware = new AksesMenu();
        $lanjut = fn () => response()->json(['ok' => true]);

        $request = Request::create('/api/v1/probe', 'GET');

        // Tanpa hak dashboard -> 403.
        $tanpaHak = $this->buatUser('dash1');
        UserAksesMenu::forceCreate(['id_user' => $tanpaHak->id_user, 'menu' => 'kegiatan']);
        $request->setUserResolver(fn () => $tanpaHak);
        $this->assertSame(403, $middleware->handle($request, $lanjut, 'dashboard')->getStatusCode());

        // Dengan hak dashboard -> lolos.
        $punyaHak = $this->buatUser('dash2');
        UserAksesMenu::forceCreate(['id_user' => $punyaHak->id_user, 'menu' => 'dashboard']);
        $request->setUserResolver(fn () => $punyaHak);
        $this->assertSame(200, $middleware->handle($request, $lanjut, 'dashboard')->getStatusCode());

        // Super admin -> lolos tanpa konfigurasi menu sama sekali.
        $super = $this->buatUser('dash3');
        UserPeran::forceCreate(['id_user' => $super->id_user, 'peran' => 'super_admin']);
        $request->setUserResolver(fn () => $super);
        $this->assertSame(200, $middleware->handle($request, $lanjut, 'dashboard')->getStatusCode());
    }

    /** Route publik tidak boleh ikut terkunci oleh AksesMenu. */
    public function test_route_publik_tetap_terbuka(): void
    {
        $this->getJson('/api/v1/kegiatan')->assertStatus(200);
        $this->getJson('/api/v1/dashboard/stats')->assertStatus(200);
        $this->getJson('/api/v1/sertifikat-batch')->assertStatus(200);
    }

    /** /me dan /hak-akses/saya tetap untuk semua user login (tanpa menu khusus). */
    public function test_me_dan_hak_akses_saya_terbuka_untuk_semua_user_login(): void
    {
        $user = $this->buatUser('bebas');
        UserAksesMenu::forceCreate(['id_user' => $user->id_user, 'menu' => 'dashboard']);
        $this->actingAs($user, 'sanctum');

        $this->getJson('/api/v1/me')->assertStatus(200);
        $this->getJson('/api/v1/hak-akses/saya')->assertStatus(200);
    }
}
