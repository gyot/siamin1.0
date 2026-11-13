<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) sertifikat_batch: alur pengajuan naskah (diajukan -> revisi/ditolak/disetujui -> terbit)
        Schema::table('sertifikat_batch', function (Blueprint $table) {
            $table->timestamp('tanggal_diajukan')->nullable()->after('status');
            $table->timestamp('tanggal_persetujuan')->nullable()->after('tanggal_diajukan');
            $table->timestamp('tanggal_proses')->nullable()->after('tanggal_persetujuan');
            $table->text('pesan_revisi')->nullable()->after('tanggal_proses');
            $table->text('alasan_penolakan')->nullable()->after('pesan_revisi');
            $table->unsignedBigInteger('id_pengaju')->nullable()->after('alasan_penolakan');
        });

        // Enum superset agar nilai lama tetap valid selama pemetaan data.
        Schema::table('sertifikat_batch', function (Blueprint $table) {
            $table->enum('status', ['draft', 'terbit', 'dicabut', 'diajukan', 'revisi', 'ditolak', 'disetujui'])->default('diajukan')->change();
        });

        // Pemetaan data lama: bernomor asli -> terbit; selain itu -> diajukan.
        DB::statement("UPDATE sertifikat_batch SET
            status = CASE
                WHEN status = 'dicabut' THEN 'dicabut'
                WHEN nomor_sertifikat IS NOT NULL AND nomor_sertifikat <> '' AND nomor_sertifikat <> 'draft' THEN 'terbit'
                ELSE 'diajukan'
            END,
            tanggal_diajukan = COALESCE(tanggal_diajukan, created_at),
            tanggal_proses = CASE
                WHEN status = 'dicabut' THEN tanggal_proses
                WHEN nomor_sertifikat IS NOT NULL AND nomor_sertifikat <> '' AND nomor_sertifikat <> 'draft' THEN COALESCE(tanggal_ttd, created_at)
                ELSE tanggal_proses
            END");

        Schema::table('sertifikat_batch', function (Blueprint $table) {
            $table->enum('status', ['diajukan', 'revisi', 'ditolak', 'disetujui', 'terbit', 'dicabut'])->default('diajukan')->change();
        });

        // 2) sertifikat_peserta: alasan pencabutan
        Schema::table('sertifikat_peserta', function (Blueprint $table) {
            $table->text('alasan_cabut')->nullable()->after('status');
        });

        // 3) riwayat: audit trail perubahan status naskah & sertifikat peserta
        Schema::create('riwayat_sertifikat', function (Blueprint $table) {
            $table->id();
            $table->enum('tipe', ['naskah', 'peserta']);
            $table->unsignedBigInteger('id_batch')->nullable();
            $table->unsignedBigInteger('id_target');
            $table->string('aksi', 40);
            $table->string('status_lama', 20)->nullable();
            $table->string('status_baru', 20);
            $table->text('catatan')->nullable();
            $table->unsignedBigInteger('id_pelaku')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['tipe', 'id_target']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_sertifikat');

        Schema::table('sertifikat_peserta', function (Blueprint $table) {
            $table->dropColumn('alasan_cabut');
        });

        Schema::table('sertifikat_batch', function (Blueprint $table) {
            $table->enum('status', ['draft', 'terbit', 'dicabut'])->default('draft')->change();
        });

        Schema::table('sertifikat_batch', function (Blueprint $table) {
            $table->dropColumn([
                'tanggal_diajukan',
                'tanggal_persetujuan',
                'tanggal_proses',
                'pesan_revisi',
                'alasan_penolakan',
                'id_pengaju',
            ]);
        });
    }
};
