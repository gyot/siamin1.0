<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('tpk')) {
            Schema::create('tpk', function (Blueprint $table) {
                $table->id('id_tpk');
                $table->foreignId('id_kegiatan')->constrained('kegiatan', 'id_kegiatan')->onDelete('cascade');
                $table->string('lokasi', 255);
                $table->string('kabupaten_kota', 255)->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasColumn('kegiatan', 'lokasi')) {
            DB::table('kegiatan')
                ->whereNotNull('lokasi')
                ->orderBy('id_kegiatan')
                ->chunkById(100, function ($kegiatanRows) {
                    $now = now();
                    $payload = [];

                    foreach ($kegiatanRows as $kegiatan) {
                        $payload[] = [
                            'id_kegiatan' => $kegiatan->id_kegiatan,
                            'lokasi' => $kegiatan->lokasi,
                            'kabupaten_kota' => property_exists($kegiatan, 'kabupaten_kota')
                                ? $kegiatan->kabupaten_kota
                                : null,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }

                    if (!empty($payload)) {
                        DB::table('tpk')->insert($payload);
                    }
                }, 'id_kegiatan');
        }

        Schema::table('kegiatan', function (Blueprint $table) {
            if (Schema::hasColumn('kegiatan', 'kabupaten_kota')) {
                $table->dropColumn('kabupaten_kota');
            }

            if (Schema::hasColumn('kegiatan', 'lokasi')) {
                $table->dropColumn('lokasi');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kegiatan', function (Blueprint $table) {
            if (!Schema::hasColumn('kegiatan', 'lokasi')) {
                $table->string('lokasi', 255)->nullable()->after('tanggal_selesai');
            }

            if (!Schema::hasColumn('kegiatan', 'kabupaten_kota')) {
                $table->string('kabupaten_kota', 255)->nullable()->after('lokasi');
            }
        });

        if (Schema::hasTable('tpk')) {
            DB::table('tpk')
                ->orderBy('id_tpk')
                ->chunkById(100, function ($tpkRows) {
                    foreach ($tpkRows as $tpk) {
                        $hasLokasi = DB::table('kegiatan')
                            ->where('id_kegiatan', $tpk->id_kegiatan)
                            ->whereNull('lokasi')
                            ->exists();

                        if ($hasLokasi) {
                            DB::table('kegiatan')
                                ->where('id_kegiatan', $tpk->id_kegiatan)
                                ->update([
                                    'lokasi' => $tpk->lokasi,
                                    'kabupaten_kota' => $tpk->kabupaten_kota,
                                ]);
                        }
                    }
                }, 'id_tpk');

            Schema::dropIfExists('tpk');
        }
    }
};
