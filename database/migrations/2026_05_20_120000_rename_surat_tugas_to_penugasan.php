<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function dropForeignIfExists(string $tableName, string $columnName): void
    {
        if (!Schema::hasTable($tableName) || !Schema::hasColumn($tableName, $columnName)) {
            return;
        }

        $foreignKeys = DB::select("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = ?
            AND COLUMN_NAME = ?
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ", [$tableName, $columnName]);

        foreach ($foreignKeys as $fk) {
            DB::statement("ALTER TABLE `{$tableName}` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
        }
    }

    public function up(): void
    {
        $this->dropForeignIfExists('surat_tugas_pegawai', 'id_surat_tugas');
        $this->dropForeignIfExists('penugasan_pegawai', 'id_penugasan');
        $this->dropForeignIfExists('penugasan_pegawai', 'id_kegiatan');
        $this->dropForeignIfExists('surat_tugas', 'id_kegiatan');
        $this->dropForeignIfExists('surat_tugas', 'id_penandatangan');
        $this->dropForeignIfExists('penugasan', 'id_kegiatan');

        if (Schema::hasTable('surat_tugas_pegawai') && !Schema::hasTable('penugasan_pegawai')) {
            Schema::rename('surat_tugas_pegawai', 'penugasan_pegawai');
        }

        if (Schema::hasTable('penugasan_pegawai') && !Schema::hasColumn('penugasan_pegawai', 'id_kegiatan')) {
            Schema::table('penugasan_pegawai', function (Blueprint $table) {
                $table->unsignedBigInteger('id_kegiatan')->nullable()->after('id');
            });
        }

        if (Schema::hasTable('penugasan_pegawai') && Schema::hasColumn('penugasan_pegawai', 'id_surat_tugas') && Schema::hasTable('surat_tugas')) {
            DB::statement('
                UPDATE penugasan_pegawai pp
                JOIN surat_tugas st ON st.id_surat_tugas = pp.id_surat_tugas
                SET pp.id_kegiatan = st.id_kegiatan
            ');
        }

        if (Schema::hasTable('penugasan_pegawai') && Schema::hasColumn('penugasan_pegawai', 'id_penugasan') && Schema::hasTable('penugasan')) {
            DB::statement('
                UPDATE penugasan_pegawai pp
                JOIN penugasan p ON p.id_penugasan = pp.id_penugasan
                SET pp.id_kegiatan = p.id_kegiatan
            ');
        }

        if (Schema::hasTable('penugasan_pegawai')) {
            Schema::table('penugasan_pegawai', function (Blueprint $table) {
                if (Schema::hasColumn('penugasan_pegawai', 'id_surat_tugas')) {
                    $table->dropColumn('id_surat_tugas');
                }

                if (Schema::hasColumn('penugasan_pegawai', 'id_penugasan')) {
                    $table->dropColumn('id_penugasan');
                }
            });

            Schema::table('penugasan_pegawai', function (Blueprint $table) {
                $table->foreign('id_kegiatan')
                    ->references('id_kegiatan')
                    ->on('kegiatan')
                    ->cascadeOnDelete();
                $table->index(['id_kegiatan', 'id_pegawai'], 'penugasan_pegawai_kegiatan_pegawai_index');
            });
        }

        Schema::dropIfExists('penugasan');
        Schema::dropIfExists('surat_tugas');
    }

    public function down(): void
    {
        $this->dropForeignIfExists('penugasan_pegawai', 'id_kegiatan');

        if (!Schema::hasTable('surat_tugas')) {
            Schema::create('surat_tugas', function (Blueprint $table) {
                $table->id('id_surat_tugas');
                $table->foreignId('id_kegiatan')->nullable()->constrained('kegiatan', 'id_kegiatan')->nullOnDelete();
                $table->string('nomor_surat', 100)->nullable();
                $table->date('tanggal_surat')->nullable();
                $table->foreignId('id_penandatangan')->nullable()->constrained('pegawai', 'id_pegawai')->nullOnDelete();
                $table->enum('status', ['draft', 'diterbitkan', 'dibatalkan'])->default('draft');
                $table->string('file_surat', 255)->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('penugasan_pegawai') && Schema::hasColumn('penugasan_pegawai', 'id_kegiatan')) {
            Schema::table('penugasan_pegawai', function (Blueprint $table) {
                $table->dropIndex('penugasan_pegawai_kegiatan_pegawai_index');
            });

            DB::statement('
                INSERT INTO surat_tugas (id_kegiatan, created_at, updated_at)
                SELECT DISTINCT id_kegiatan, NOW(), NOW()
                FROM penugasan_pegawai
                WHERE id_kegiatan IS NOT NULL
                AND id_kegiatan NOT IN (
                    SELECT id_kegiatan FROM surat_tugas WHERE id_kegiatan IS NOT NULL
                )
            ');

            Schema::table('penugasan_pegawai', function (Blueprint $table) {
                $table->unsignedBigInteger('id_surat_tugas')->nullable()->after('id');
            });

            DB::statement('
                UPDATE penugasan_pegawai pp
                JOIN surat_tugas st ON st.id_kegiatan = pp.id_kegiatan
                SET pp.id_surat_tugas = st.id_surat_tugas
            ');

            Schema::table('penugasan_pegawai', function (Blueprint $table) {
                $table->dropColumn('id_kegiatan');
            });

            Schema::table('penugasan_pegawai', function (Blueprint $table) {
                $table->foreign('id_surat_tugas')
                    ->references('id_surat_tugas')
                    ->on('surat_tugas')
                    ->cascadeOnDelete();
            });
        }

        if (Schema::hasTable('penugasan_pegawai') && !Schema::hasTable('surat_tugas_pegawai')) {
            Schema::rename('penugasan_pegawai', 'surat_tugas_pegawai');
        }
    }
};
